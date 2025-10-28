<?php

namespace App\Jobs\Rips\ImportCsv;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ProcessChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public string $chunkId;
    public string $chunkPath;
    public string $disk;
    public string $selectedQueue;

    // Campos EXACTOS permitidos a nivel de usuario
    private array $exactUserFields = [
        'codSexo',
        'consecutivo',
        'incapacidad',
        'tipoUsuario',
        'codPaisOrigen',
        'fechaNacimiento',
        'codPaisResidencia',
        'codMunicipioResidencia',
        'numDocumentoIdentificacion',
        'tipoDocumentoIdentificacion',
        'codZonaTerritorialResidencia'
    ];

    // Campos EXACTOS permitidos a nivel de factura
    private array $exactInvoiceFields = [
        'numNota',
        'tipoNota',
        'numFactura',
        'numDocumentoIdObligado',
        'usuarios',
    ];

    public function __construct(string $batchId, string $chunkId, string $chunkPath, string $disk, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->chunkId = $chunkId;
        $this->chunkPath = $chunkPath;
        $this->disk = $disk; // Sin valor por defecto
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $metaKey = "import:batch:{$this->batchId}:meta";
        $chunkKey = "import:batch:{$this->batchId}:chunk:{$this->chunkId}";
        $partialsPrefix = "import:batch:{$this->batchId}:partials:";
        $invoicesSet = "import:batch:{$this->batchId}:invoices_set";

        // Log::info("ProcessChunkJob: starting chunk {$this->chunkId} for batch {$this->batchId} path={$this->chunkPath}");

        // Marcar como procesando
        $redis->hset($chunkKey, 'status', 'processing');
        $redis->hset($chunkKey, 'started_at', now()->toDateTimeString());
        $redis->hset($metaKey, 'status', 'processing_chunks');

        $disk = Storage::disk($this->disk);
        $stream = $disk->readStream($this->chunkPath);
        if ($stream === false) {
            $msg = "No se pudo abrir chunk file: {$this->chunkPath}";
            Log::error("ProcessChunkJob: {$msg}");
            $redis->hset($chunkKey, 'status', 'failed');
            ErrorCollector::addError($this->batchId, 0, null, $msg, 'chunk_read_error', $this->chunkPath, null);
            return;
        }

        // Leer header
        $headerLine = fgets($stream);
        if ($headerLine === false) {
            fclose($stream);
            $msg = "Chunk vacío o no tiene header: {$this->chunkPath}";
            Log::warning("ProcessChunkJob: {$msg}");
            $redis->hset($chunkKey, 'status', 'empty');
            $redis->hset($chunkKey, 'rows_count', 0);
            return;
        }

        // Detectar delimitador
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $headerLine = trim($headerLine);
        $possibleDelimiters = [',', ';', '|', "\t"];
        $chosenDelimiter = ';';
        $bestCount = 0;
        foreach ($possibleDelimiters as $d) {
            $parts = str_getcsv($headerLine, $d);
            if (count($parts) > $bestCount) {
                $bestCount = count($parts);
                $chosenDelimiter = $d;
            }
        }

        $headers = array_map('trim', str_getcsv($headerLine, $chosenDelimiter));

        // NO normalizar headers - mantener EXACTAMENTE como vienen
        $headers = array_map(function ($h) {
            return trim($h);
        }, $headers);

        $groups = [];
        $rowsInChunk = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($stream, 0, $chosenDelimiter)) !== false) {
            $rowNumber++;
            $rowsInChunk++;

            if (count($row) !== count($headers)) {
                $msg = "Fila con número de columnas distinto a headers en chunk {$this->chunkId} fila={$rowNumber}";
                Log::warning("ProcessChunkJob: {$msg}");
                ErrorCollector::addError(
                    $this->batchId,
                    $rowNumber,
                    null,
                    $msg,
                    'row_column_mismatch',
                    json_encode(['expected' => count($headers), 'found' => count($row)]),
                    json_encode($row)
                );
                continue;
            }

            $data = array_combine($headers, $row);
            $this->processRow($data, $groups, $rowNumber);
        }

        fclose($stream);

        // Escribir partials en Redis
        $partialsWritten = 0;
        foreach ($groups as $numFactura => $partial) {
            $partialJson = json_encode($partial, JSON_UNESCAPED_UNICODE);
            $partialKey = $partialsPrefix . $numFactura;
            $redis->hset($partialKey, $this->chunkId, $partialJson);
            $redis->sadd($invoicesSet, $numFactura);

            // LOG DE DIAGNÓSTICO - Factura agregada al set
            // Log::info("ProcessChunkJob: Factura {$numFactura} agregada al set. Total en set ahora: " . $redis->scard($invoicesSet));

            $partialsWritten++;
        }

        // Actualizar metadata
        $redis->hset($chunkKey, 'status', 'completed');
        $redis->hset($chunkKey, 'rows_count', $rowsInChunk);
        $redis->hset($chunkKey, 'completed_at', now()->toDateTimeString());
        $redis->hincrby($metaKey, 'chunks_completed', 1);
        $redis->hincrby($metaKey, 'rows_processed', $rowsInChunk);

        // Verificar si todos los chunks están completos para encolar MergeGroupsJob
        $this->checkAndDispatchMergeJob($redis, $metaKey);

        // Emitir evento de progreso - CORREGIDO
        $chunksCompleted = (int) $redis->hget($metaKey, 'chunks_completed');
        $totalChunks = (int) $redis->hget($metaKey, 'total_chunks');

        event(new ImportProgressEvent(
            $this->batchId,
            $chunksCompleted,  // ← Progreso basado en CHUNKS completados
            'PROCESSING_CHUNKS',
            ErrorCollector::countErrors($this->batchId),
            'active',
            "Chunk {$this->chunkId} procesado: {$chunksCompleted}/{$totalChunks} chunks, {$partialsWritten} partials",
        ));

        // Log::info("ProcessChunkJob: chunk {$this->chunkId} processed for batch {$this->batchId} partials={$partialsWritten} rows={$rowsInChunk}");
    }

    // Agrega esta propiedad a la clase
    private array $allowedServiceTypes = [
        'consultas',
        'procedimientos',
        'urgencias',
        'hospitalizacion',
        'recienNacidos',
        'medicamentos',
        'otrosServicios'
    ];

    /**
     * Valida si un campo pertenece a la sección correcta
     */
    protected function validateFieldSection(string $campo, string $section, array $data, int $rowNumber): bool
    {
        $valid = true;
        $errorMessage = null;
        $errorType = null;

        switch ($section) {
            case 'factura':
                if (!in_array($campo, $this->exactInvoiceFields, true)) {
                    $errorMessage = "Campo '{$campo}' no permitido a nivel de factura";
                    $errorType = 'FIELD_NOT_ALLOWED_IN_SECTION';
                    $valid = false;
                }
                break;

            case 'usuario':
                if (!in_array($campo, $this->exactUserFields, true)) {
                    $errorMessage = "Campo '{$campo}' no permitido a nivel de usuario";
                    $errorType = 'FIELD_NOT_ALLOWED_IN_SECTION';
                    $valid = false;
                }
                break;

            case 'servicio':
                // La validación de campos de servicio se hace en processServiceLevelField
                break;
        }

        if (!$valid) {
            ErrorCollector::addError(
                $this->batchId,
                $rowNumber,
                $campo,
                $errorMessage,
                $errorType,
                $data['valor'] ?? null,
                json_encode($data)
            );
        }

        return $valid;
    }

    /**
     * Valida si un tipo de servicio es permitido
     */
    protected function validateServiceType(string $servicio, array $data, int $rowNumber): bool
    {
        if (!in_array($servicio, $this->allowedServiceTypes, true)) {
            ErrorCollector::addError(
                $this->batchId,
                $rowNumber,
                'servicio',
                "Tipo de servicio '{$servicio}' no permitido. Servicios válidos: " . implode(', ', $this->allowedServiceTypes),
                'SERVICE_TYPE_NOT_ALLOWED',
                $servicio,
                json_encode($data)
            );
            return false;
        }
        return true;
    }

    /**
     * Valida si un campo es permitido para un tipo de servicio específico
     */
    protected function validateServiceField(string $campo, string $servicio, array $data, int $rowNumber): bool
    {
        $allowedFields = $this->getAllowedServiceFields($servicio);

        if (!in_array($campo, $allowedFields, true)) {
            ErrorCollector::addError(
                $this->batchId,
                $rowNumber,
                $campo,
                "Campo '{$campo}' no permitido para el servicio '{$servicio}'. Campos válidos: " . implode(', ', $allowedFields),
                'FIELD_NOT_ALLOWED_FOR_SERVICE',
                $data['valor'] ?? null,
                json_encode($data)
            );
            return false;
        }
        return true;
    }

    /**
     * Retorna los campos permitidos para cada tipo de servicio
     */
    protected function getAllowedServiceFields(string $serviceType): array
    {
        $fields = [
            'procedimientos' => [
                'idMIPRES',
                'vrServicio',
                'codServicio',
                'consecutivo',
                'codPrestador',
                'grupoServicios',
                'codComplicacion',
                'conceptoRecaudo',
                'numAutorizacion',
                'codProcedimiento',
                'valorPagoModerador',
                'fechaInicioAtencion',
                'numFEVPagoModerador',
                'codDiagnosticoPrincipal',
                'viaIngresoServicioSalud',
                'finalidadTecnologiaSalud',
                'codDiagnosticoRelacionado',
                'numDocumentoIdentificacion',
                'tipoDocumentoIdentificacion',
                'modalidadGrupoServicioTecSal'
            ],
            'consultas' => [
                'codPrestador',
                'fechaInicioAtencion',
                'numAutorizacion',
                'codConsulta',
                'modalidadGrupoServicioTecSal',
                'grupoServicios',
                'codServicio',
                'finalidadTecnologiaSalud',
                'causaMotivoAtencion',
                'codDiagnosticoPrincipal',
                'codDiagnosticoRelacionado1',
                'codDiagnosticoRelacionado2',
                'codDiagnosticoRelacionado3',
                'tipoDiagnosticoPrincipal',
                'tipoDocumentoIdentificacion',
                'numDocumentoIdentificacion',
                'vrServicio',
                'conceptoRecaudo',
                'valorPagoModerador',
                'numFEVPagoModerador',
                'consecutivo'
            ],
            'urgencias' => [
                'codPrestador',
                'fechaInicioAtencion',
                'causaMotivoAtencion',
                'codDiagnosticoPrincipal',
                'codDiagnosticoPrincipalE',
                'codDiagnosticoRelacionadoE1',
                'codDiagnosticoRelacionadoE2',
                'codDiagnosticoRelacionadoE3',
                'condicionDestinoUsuarioEgreso',
                'codDiagnosticoCausaMuerte',
                'fechaEgreso',
                'consecutivo'
            ],
            'hospitalizacion' => [
                'codPrestador',
                'viaIngresoServicioSalud',
                'fechaInicioAtencion',
                'numAutorizacion',
                'causaMotivoAtencion',
                'codDiagnosticoPrincipal',
                'codDiagnosticoPrincipalE',
                'codDiagnosticoRelacionadoE1',
                'codDiagnosticoRelacionadoE2',
                'codDiagnosticoRelacionadoE3',
                'codComplicacion',
                'condicionDestinoUsuarioEgreso',
                'codDiagnosticoCausaMuerte',
                'fechaEgreso',
                'consecutivo'
            ],
            'medicamentos' => [
                'codPrestador',
                'numAutorizacion',
                'idMIPRES',
                'fechaDispensAdmon',
                'codDiagnosticoPrincipal',
                'codDiagnosticoRelacionado',
                'tipoMedicamento',
                'codTecnologiaSalud',
                'nomTecnologiaSalud',
                'concentracionMedicamento',
                'unidadMedida',
                'formaFarmaceutica',
                'unidadMinDispensa',
                'cantidadMedicamento',
                'diasTratamiento',
                'tipoDocumentoIdentificacion',
                'numDocumentoIdentificacion',
                'vrUnitMedicamento',
                'vrServicio',
                'conceptoRecaudo',
                'valorPagoModerador',
                'numFEVPagoModerador',
                'consecutivo'
            ],
            'recienNacidos' => [
                'codPrestador',
                'tipoDocumentoIdentificacion',
                'numDocumentoIdentificacion',
                'fechaNacimiento',
                'edadGestacional',
                'numConsultasCPrenatal',
                'codSexoBiologico',
                'peso',
                'codDiagnosticoPrincipal',
                'condicionDestinoUsuarioEgreso',
                'codDiagnosticoCausaMuerte',
                'fechaEgreso',
                'consecutivo'
            ],
            'otrosServicios' => [
                'codPrestador',
                'numAutorizacion',
                'idMIPRES',
                'fechaSuministroTecnologia',
                'tipoOS',
                'codTecnologiaSalud',
                'nomTecnologiaSalud',
                'cantidadOS',
                'tipoDocumentoIdentificacion',
                'numDocumentoIdentificacion',
                'vrUnitOS',
                'vrServicio',
                'conceptoRecaudo',
                'valorPagoModerador',
                'numFEVPagoModerador',
                'consecutivo'
            ]
        ];

        return $fields[$serviceType] ?? [];
    }

    /**
     * Procesa una fila del CSV y la agrega al grupo correspondiente
     */
    protected function processRow(array $data, array &$groups, int $rowNumber): void
    {
        $numFactura = $data['num_factura'] ?? null;
        if (empty($numFactura)) {
            ErrorCollector::addError(
                $this->batchId,
                $rowNumber,
                'num_factura',
                'num_factura faltante',
                'missing_num_factura',
                null,
                json_encode($data)
            );
            return;
        }

        // Inicializar grupo si no existe
        if (!isset($groups[$numFactura])) {
            $groups[$numFactura] = [
                'numFactura' => $numFactura,
                'invoice_fields' => [],
                'usuarios' => [],
                'meta' => ['chunkId' => $this->chunkId, 'rows_count' => 0]
            ];
        }

        $groups[$numFactura]['meta']['rows_count']++;

        $idUsuario = $data['id_usuario'] ?? null;
        $numIdentificacion = $data['num_identificacion'] ?? null;
        $idServicio = $data['id_servicio'] ?? null;
        $servicio = $data['servicio'] ?? null;
        $campo = $data['campo'] ?? null;
        $valor = $this->castValue($data['valor'] ?? null);

        // Determinar el nivel del campo
        if (!empty($idUsuario) && !empty($numIdentificacion)) {
            // Campo a nivel de usuario o servicio
            $this->processUserLevelField($groups[$numFactura], $idUsuario, $numIdentificacion, $idServicio, $servicio, $campo, $valor, $rowNumber, $data);
        } else {
            // Campo a nivel de factura
            if (!empty($campo) && $this->validateFieldSection($campo, 'factura', $data, $rowNumber)) {
                $this->processInvoiceLevelField($groups[$numFactura], $campo, $valor);
            }
        }
    }

    /**
     * Procesa campos a nivel de usuario o servicio
     */
    protected function processUserLevelField(array &$invoiceGroup, $idUsuario, $numIdentificacion, $idServicio, $servicio, $campo, $valor, int $rowNumber, array $data): void
    {
        // Calcular posición del usuario (id_usuario - 1)
        $userPosition = max(0, ((int)$idUsuario) - 1);
        $userKey = 'user_' . $userPosition . '_' . $numIdentificacion;

        // Inicializar usuario si no existe
        if (!isset($invoiceGroup['usuarios'][$userKey])) {
            $invoiceGroup['usuarios'][$userKey] = [
                // Campos EXACTOS del usuario - inicializar todos como null
                'codSexo' => null,
                'consecutivo' => null,
                'incapacidad' => null,
                'tipoUsuario' => null,
                'codPaisOrigen' => null,
                'fechaNacimiento' => null,
                'codPaisResidencia' => null,
                'codMunicipioResidencia' => null,
                'numDocumentoIdentificacion' => $numIdentificacion,
                'tipoDocumentoIdentificacion' => null,
                'codZonaTerritorialResidencia' => null,
                'servicios' => [],
                'otros' => [],
                '_meta' => [
                    'id_usuario' => $idUsuario,
                    'num_identificacion' => $numIdentificacion,
                    'position' => $userPosition
                ]
            ];
        }

        $user = &$invoiceGroup['usuarios'][$userKey];

        if (!empty($idServicio) && !empty($servicio)) {
            // Campo a nivel de servicio
            $this->processServiceLevelField($user, $idServicio, $servicio, $campo, $valor, $rowNumber, $data);
        } else if (!empty($campo)) {
            // Campo a nivel de usuario
            if ($this->validateFieldSection($campo, 'usuario', $data, $rowNumber)) {
                $this->processUserField($user, $campo, $valor);
            }
        }
    }

    /**
     * Procesa campos a nivel de servicio
     */
    protected function processServiceLevelField(array &$user, $idServicio, $servicio, $campo, $valor, int $rowNumber, array $data): void
    {
        // Validar tipo de servicio
        if (!$this->validateServiceType($servicio, $data, $rowNumber)) {
            return;
        }

        // Validar campo del servicio
        if (!empty($campo) && !$this->validateServiceField($campo, $servicio, $data, $rowNumber)) {
            return;
        }

        $serviceKey = $idServicio . '_' . $servicio;

        if (!isset($user['servicios'][$serviceKey])) {
            $user['servicios'][$serviceKey] = [
                'id_servicio' => $idServicio,
                'servicio' => $servicio,
                'detalles' => [],
                'consecutivo' => $idServicio
            ];
        }

        // Agregar campo al servicio
        if (!empty($campo)) {
            $user['servicios'][$serviceKey]['detalles'][$campo] = $valor;
        }
    }

    /**
     * Procesa campos a nivel de usuario
     */
    protected function processUserField(array &$user, $campo, $valor): void
    {
        // Verificar si el campo es EXACTAMENTE uno de los campos permitidos
        if (in_array($campo, $this->exactUserFields, true)) {
            $user[$campo] = $valor;
            // Log::info("DEBUG - Campo de usuario asignado: {$campo} = {$valor}");
        } else {
            // Campo no permitido, ir a 'otros'
            $user['otros'][$campo] = $valor;
            // Log::info("DEBUG - Campo de usuario en 'otros': {$campo} = {$valor}");
        }
    }

    /**
     * Procesa campos a nivel de factura
     */
    protected function processInvoiceLevelField(array &$invoiceGroup, $campo, $valor): void
    {
        if (!empty($campo)) {
            // Verificar si el campo es EXACTAMENTE uno de los campos de factura permitidos
            if (in_array($campo, $this->exactInvoiceFields, true)) {
                $invoiceGroup['invoice_fields'][$campo] = $valor;
                // Log::info("DEBUG - Campo de factura asignado: {$campo} = {$valor}");
            } else {
                // Campo no permitido, ir a meta
                if (!isset($invoiceGroup['meta']['otros_campos'])) {
                    $invoiceGroup['meta']['otros_campos'] = [];
                }
                $invoiceGroup['meta']['otros_campos'][$campo] = $valor;
                // Log::info("DEBUG - Campo de factura en 'otros_campos': {$campo} = {$valor}");
            }
        }
    }

    /**
     * Convierte valores al tipo adecuado
     */
    protected function castValue($v)
    {
        if ($v === null || $v === '') return null;

        $v = trim($v);

        // Si es un número, convertirlo
        if (is_numeric($v)) {
            if (strpos($v, '.') !== false || strpos($v, ',') !== false) {
                return floatval(str_replace(',', '.', $v));
            }
            return intval($v);
        }

        // Valores booleanos
        if ($v === 'true' || $v === 'TRUE') return true;
        if ($v === 'false' || $v === 'FALSE') return false;

        // Mantener como string
        return $v;
    }

    /**
     * Verifica si todos los chunks están completos para encolar MergeGroupsJob
     */
    protected function checkAndDispatchMergeJob($redis, $metaKey): void
    {
        $chunksCompleted = (int) $redis->hget($metaKey, 'chunks_completed');
        $totalChunks = (int) $redis->hget($metaKey, 'total_chunks');
        $invoicesSet = "import:batch:{$this->batchId}:invoices_set";

        $totalErrors = ErrorCollector::countErrors($this->batchId);

        // LOG DE DIAGNÓSTICO - Verificación de merge
        // Log::info("ProcessChunkJob: Verificando merge - chunksCompleted={$chunksCompleted}, totalChunks={$totalChunks}, facturasEnSet=" . $redis->scard($invoicesSet) . ", errores={$totalErrors}");

        $mergeFlagKey = "import:batch:{$this->batchId}:merge_enqueued";

        if ($totalChunks > 0 && $chunksCompleted >= $totalChunks) {
            $set = $redis->setnx($mergeFlagKey, now()->toDateTimeString());
            if ($set) {
                $redis->expire($mergeFlagKey, 60 * 60 * 24);

                try {
                    // PRIMERO: INFORMAR QUE EL MERGE VA A COMENZAR
                    $totalInvoices = $redis->scard($invoicesSet);
                    event(new ImportProgressEvent(
                        $this->batchId,
                        0,  // ← Progreso en 0 para nueva fase
                        'MERGE_STARTING',
                        ErrorCollector::countErrors($this->batchId),
                        'active',
                        "Preparando merge de {$totalInvoices} facturas...",
                    ));

                    // sleep(5);

                    // SEGUNDO: REINICIAR CONTADORES PARA MERGE
                    $redis->hset($metaKey, 'merge_invoices_processed', 0);
                    $redis->hset($metaKey, 'merge_total_invoices', $totalInvoices);

                    // TERCERO: ACTUALIZAR TOTAL_ROWS EN METADATA PRINCIPAL PARA EL MERGE
                    $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
                    $metadata['total_rows'] = $totalInvoices;  // ← ¡CORRECCIÓN IMPORTANTE!
                    $redis->hmset("batch:{$this->batchId}:metadata", $metadata);

                    // CUARTO: DESPACHAR EL JOB
                    \App\Jobs\Rips\ImportCsv\MergeGroupsJob::dispatch($this->batchId, $this->disk, 500, $this->selectedQueue);
                    // Log::info("ProcessChunkJob: dispatched MergeGroupsJob for batch {$this->batchId}");
                    $redis->hset($metaKey, 'merge_enqueued_at', now()->toDateTimeString());
                } catch (\Throwable $e) {
                    Log::error("ProcessChunkJob: fallo al encolar MergeGroupsJob: {$e->getMessage()}");
                    $redis->del($mergeFlagKey);
                }
            }
        }
    }
}
