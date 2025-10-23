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

class MergeGroupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public string $disk;
    public int $batchSize;

    // Campos EXACTOS permitidos a nivel de factura
    private array $exactInvoiceFields = [
        'numNota',
        'TipoNota',
        'numFactura',
        'numDocumentoIdObligado'
    ];

    public function __construct(string $batchId, ?string $disk = 'public', int $batchSize = 500)
    {
        $this->batchId = $batchId;
        $this->disk = $disk ?? config('filesystems.default');
        $this->batchSize = $batchSize;
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $metaKey = "import:batch:{$this->batchId}:meta";
        $invoicesSet = "import:batch:{$this->batchId}:invoices_set";
        $partialsPrefix = "import:batch:{$this->batchId}:partials:";
        $groupsFolder = "temp/rips/{$this->batchId}/groups";
        $disk = Storage::disk($this->disk);

        // ========== LOGS DE DIAGNÓSTICO CRÍTICOS ==========
        $totalInvoices = $redis->scard($invoicesSet);
        Log::info("MergeGroupsJob: DIAGNOSTICO - Total facturas en set: {$totalInvoices}");

        if ($totalInvoices === 0) {
            Log::warning("MergeGroupsJob: DIAGNOSTICO - No hay facturas en el set. Verificando partials...");

            // Verificar si hay partials keys
            $partialsPattern = $partialsPrefix . "*";
            $partialsKeys = $redis->keys($partialsPattern);
            Log::info("MergeGroupsJob: DIAGNOSTICO - Total partials keys encontrados: " . count($partialsKeys));

            foreach ($partialsKeys as $key) {
                $numFactura = str_replace($partialsPrefix, '', $key);
                $partialData = $redis->hgetall($key);
                Log::info("MergeGroupsJob: DIAGNOSTICO - Partial {$numFactura}: " . json_encode($partialData));
            }

            return; // Salir si no hay facturas
        }

        Log::info("MergeGroupsJob: iniciando merge batch={$this->batchId} batchSize={$this->batchSize}");

        // Crear carpeta de grupos si no existe
        if (!$disk->exists($groupsFolder)) {
            $disk->makeDirectory($groupsFolder, 0755, true);
            Log::info("MergeGroupsJob: Carpeta creada: {$groupsFolder}");
        }

        $totalInvoicesEstimated = (int) $redis->scard($invoicesSet);
        $processedInvoices = 0;

        // Iterador SCAN sobre el set de facturas
        $cursor = '0';
        $processedThisRun = 0;

        do {
            $reply = $redis->sscan($invoicesSet, $cursor, ['COUNT' => $this->batchSize]);
            if (!is_array($reply) || count($reply) < 2) {
                break;
            }
            [$cursor, $items] = $reply;

            foreach ($items as $numFactura) {
                Log::info("MergeGroupsJob: Procesando factura {$numFactura} - PASO 1");

                // 1) HGETALL partials for this factura
                $partialKey = $partialsPrefix . $numFactura;
                $partials = $redis->hgetall($partialKey);

                if (empty($partials)) {
                    Log::info("MergeGroupsJob: Factura {$numFactura} - PASO 2 (sin partials)");
                    $redis->srem($invoicesSet, $numFactura);
                    continue;
                }

                Log::info("MergeGroupsJob: Factura {$numFactura} - PASO 3 (con partials)");

                // 2) Decodificar y mergear partials
                $merged = $this->mergePartials(array_values($partials));
                $mergedNormalized = $this->normalizeMerged($merged);

                // 3) Persistir JSON final
                $groupPath = "{$groupsFolder}/{$this->sanitizeFilename($numFactura)}.json";
                Log::info("MergeGroupsJob: groupPath = {$groupPath}");
                $encoded = json_encode($mergedNormalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                try {
                    $disk->put($groupPath, $encoded);
                    Log::info("MergeGroupsJob: JSON guardado exitosamente para factura {$numFactura} en {$groupPath}");
                } catch (\Throwable $e) {
                    Log::error("MergeGroupsJob: error guardando group {$numFactura}: {$e->getMessage()}");
                    continue;
                }

                // 4) Limpiar partials y remover invoice del set
                try {
                    $redis->del($partialKey);
                    $redis->srem($invoicesSet, $numFactura);
                    Log::info("MergeGroupsJob: Partial limpiado para factura {$numFactura}");
                } catch (\Throwable $e) {
                    Log::warning("MergeGroupsJob: no se pudo limpiar partials para {$numFactura}: {$e->getMessage()}");
                }

                $processedInvoices++;
                $processedThisRun++;

                // ACTUALIZAR CONTADOR DE MERGE
                $redis->hincrby($metaKey, 'merge_invoices_processed', 1);

                // Emitir progreso cada N facturas
                if ($processedThisRun % 10 === 0) {
                    $invoicesProcessed = (int) $redis->hget($metaKey, 'merge_invoices_processed');
                    $totalInvoices = (int) $redis->hget($metaKey, 'merge_total_invoices');

                    event(new ImportProgressEvent(
                        $this->batchId,
                        $invoicesProcessed,  // ← Progreso basado en facturas procesadas
                        'MERGING',
                        ErrorCollector::countErrors($this->batchId),
                        'active',
                        "Merge: {$invoicesProcessed}/{$totalInvoices} facturas procesadas",
                    ));
                }

                if ($processedThisRun >= $this->batchSize) {
                    break 2;
                }
            }
        } while ($cursor != '0');

        // Actualizar meta y emitir evento final
        $invoicesProcessed = (int) $redis->hget($metaKey, 'merge_invoices_processed');
        $totalInvoices = (int) $redis->hget($metaKey, 'merge_total_invoices');
        $remaining = (int) $redis->scard($invoicesSet);

        $redis->hset($metaKey, 'merge_completed_at', now()->toDateTimeString());
        $redis->hset($metaKey, 'invoices_remaining', $remaining);

        event(new ImportProgressEvent(
            $this->batchId,
            $invoicesProcessed,
            "MERGING",
            ErrorCollector::countErrors($this->batchId),
            'active',
            "Merge: procesadas={$invoicesProcessed} restantes={$remaining}"
        ));

        Log::info("MergeGroupsJob: final run batch={$this->batchId} processed={$invoicesProcessed} remaining={$remaining}");

        if ($remaining > 0) {
            self::dispatch($this->batchId, $this->disk, $this->batchSize);
            Log::info("MergeGroupsJob: Re-encolando job para procesar facturas restantes");
        } else {
            event(new ImportProgressEvent(
                $this->batchId,
                $totalInvoices,
                "MERGE_COMPLETED",
                ErrorCollector::countErrors($this->batchId),
                'completed',
                "Merge completo: {$invoicesProcessed} facturas procesadas"
            ));
            Log::info("MergeGroupsJob: merge completado para batch={$this->batchId}");

            $countErrors = ErrorCollector::countErrors($this->batchId);
            $status = $countErrors > 0 ? 'failed' : "completed";
            ErrorCollector::saveErrorsToDatabase($this->batchId, $status);
        }
    }

    /**
     * Merge simplificado de partials
     */
    protected function mergePartials(array $partialsJsonValues): array
    {
        $result = [
            'numFactura' => null,
            'invoice_fields' => [],
            'usuarios' => [],
            'meta' => [
                'chunks' => [],
            ],
        ];

        foreach ($partialsJsonValues as $partialItem) {
            $partial = is_string($partialItem) ? json_decode($partialItem, true) : $partialItem;
            if (!is_array($partial)) {
                continue;
            }

            // numFactura
            if (empty($result['numFactura']) && !empty($partial['numFactura'])) {
                $result['numFactura'] = $partial['numFactura'];
            }

            // meta chunks
            if (!empty($partial['meta']) && isset($partial['meta']['chunkId'])) {
                $result['meta']['chunks'][] = [
                    'chunkId' => $partial['meta']['chunkId'] ?? null,
                    'rows_count' => $partial['meta']['rows_count'] ?? null,
                ];
            }

            // invoice_fields - last wins
            if (!empty($partial['invoice_fields']) && is_array($partial['invoice_fields'])) {
                $result['invoice_fields'] = array_merge($result['invoice_fields'], $partial['invoice_fields']);
            }

            // usuarios - merge por userKey
            if (!empty($partial['usuarios']) && is_array($partial['usuarios'])) {
                foreach ($partial['usuarios'] as $userKey => $userData) {
                    if (!isset($result['usuarios'][$userKey])) {
                        // Nuevo usuario
                        $result['usuarios'][$userKey] = $userData;
                    } else {
                        // Merge de usuario existente
                        $existingUser = $result['usuarios'][$userKey];

                        // Merge campos básicos del usuario (last wins)
                        foreach ($this->getUserFields() as $field) {
                            if (isset($userData[$field]) && $userData[$field] !== null) {
                                $existingUser[$field] = $userData[$field];
                            }
                        }

                        // Merge servicios
                        if (!empty($userData['servicios']) && is_array($userData['servicios'])) {
                            foreach ($userData['servicios'] as $serviceKey => $serviceData) {
                                if (!isset($existingUser['servicios'][$serviceKey])) {
                                    $existingUser['servicios'][$serviceKey] = $serviceData;
                                } else {
                                    // Merge detalles del servicio (last wins)
                                    $existingService = $existingUser['servicios'][$serviceKey];
                                    if (!empty($serviceData['detalles']) && is_array($serviceData['detalles'])) {
                                        $existingService['detalles'] = array_merge(
                                            $existingService['detalles'] ?? [],
                                            $serviceData['detalles']
                                        );
                                    }
                                    $existingUser['servicios'][$serviceKey] = $existingService;
                                }
                            }
                        }

                        // Merge otros campos
                        if (!empty($userData['otros']) && is_array($userData['otros'])) {
                            $existingUser['otros'] = array_merge(
                                $existingUser['otros'] ?? [],
                                $userData['otros']
                            );
                        }

                        $result['usuarios'][$userKey] = $existingUser;
                    }
                }
            }
        }

        return $result;
    }

    protected function normalizeMerged(array $merged): array
    {
        $out = [
            'numNota' => null,
            'TipoNota' => null,
            'usuarios' => [],
            'numFactura' => $merged['numFactura'] ?? null,
            'numDocumentoIdObligado' => null,
            'meta' => $merged['meta'] ?? [],
        ];

        // Procesar campos de factura
        $invoiceFields = $merged['invoice_fields'] ?? [];
        foreach ($this->exactInvoiceFields as $field) {
            if (isset($invoiceFields[$field])) {
                $out[$field] = $invoiceFields[$field];
            }
        }

        // Procesar usuarios
        if (!empty($merged['usuarios']) && is_array($merged['usuarios'])) {
            $users = [];
            foreach ($merged['usuarios'] as $userKey => $userData) {
                $normalizedUser = $this->normalizeUser($userData);
                if ($normalizedUser) {
                    $users[] = $normalizedUser;
                }
            }
            $out['usuarios'] = $users;
        }

        return $out;
    }

    protected function normalizeUser(array $userData): array
    {
        // Estructura EXACTA del usuario - consecutivo como null
        $user = [
            'codSexo' => $userData['codSexo'] ?? null,
            'consecutivo' => null, // Siempre null para usuarios
            'incapacidad' => $userData['incapacidad'] ?? null,
            'tipoUsuario' => $userData['tipoUsuario'] ?? null,
            'codPaisOrigen' => $userData['codPaisOrigen'] ?? null,
            'fechaNacimiento' => $userData['fechaNacimiento'] ?? null,
            'codPaisResidencia' => $userData['codPaisResidencia'] ?? null,
            'codMunicipioResidencia' => $userData['codMunicipioResidencia'] ?? null,
            'numDocumentoIdentificacion' => $userData['numDocumentoIdentificacion'] ?? null,
            'tipoDocumentoIdentificacion' => $userData['tipoDocumentoIdentificacion'] ?? null,
            'codZonaTerritorialResidencia' => $userData['codZonaTerritorialResidencia'] ?? null,
            'servicios' => new \stdClass(),
        ];

        // Procesar servicios
        if (!empty($userData['servicios']) && is_array($userData['servicios'])) {
            $servicesByType = [];
            foreach ($userData['servicios'] as $serviceKey => $serviceData) {
                $serviceType = $serviceData['servicio'] ?? 'unknown';

                // Solo procesar servicios permitidos
                $allowedServiceTypes = [
                    'consultas',
                    'procedimientos',
                    'urgencias',
                    'hospitalizacion',
                    'recienNacidos',
                    'medicamentos',
                    'otrosServicios'
                ];

                if (!in_array($serviceType, $allowedServiceTypes)) {
                    Log::warning("MergeGroupsJob: Saltando servicio no permitido: {$serviceType}");
                    continue;
                }

                if (!isset($servicesByType[$serviceType])) {
                    $servicesByType[$serviceType] = [];
                }

                $normalizedService = $this->normalizeService($serviceData);
                if ($normalizedService) {
                    $servicesByType[$serviceType][] = $normalizedService;
                }
            }
            $user['servicios'] = (object) $servicesByType;
        }

        return $user;
    }

    protected function normalizeService(array $serviceData): array
    {
        $serviceType = $serviceData['servicio'] ?? 'unknown';

        // Obtener la estructura base según el tipo de servicio
        $service = $this->getServiceStructure($serviceType);

        // // Preservar id_servicio como consecutivo si no hay otro valor
        // if (!empty($serviceData['id_servicio']) && empty($service['consecutivo'])) {
        //     $service['consecutivo'] = $serviceData['id_servicio'];
        // }

        // Mapear campos desde los detalles del servicio
        if (!empty($serviceData['detalles']) && is_array($serviceData['detalles'])) {
            foreach ($serviceData['detalles'] as $key => $value) {
                $mappedKey = $this->mapServiceField($key, $serviceType);
                if ($mappedKey && array_key_exists($mappedKey, $service)) {
                    $service[$mappedKey] = $value;
                }
            }
        }

        return $service;
    }

    /**
     * Retorna la estructura de campos para cada tipo de servicio
     */
    protected function getServiceStructure(string $serviceType): array
    {
        $structures = [
            'procedimientos' => [
                'idMIPRES' => null,
                'vrServicio' => null,
                'codServicio' => null,
                'consecutivo' => null,
                'codPrestador' => null,
                'grupoServicios' => null,
                'codComplicacion' => null,
                'conceptoRecaudo' => null,
                'numAutorizacion' => null,
                'codProcedimiento' => null,
                'valorPagoModerador' => null,
                'fechaInicioAtencion' => null,
                'numFEVPagoModerador' => null,
                'codDiagnosticoPrincipal' => null,
                'viaIngresoServicioSalud' => null,
                'finalidadTecnologiaSalud' => null,
                'codDiagnosticoRelacionado' => null,
                'numDocumentoIdentificacion' => null,
                'tipoDocumentoIdentificacion' => null,
                'modalidadGrupoServicioTecSal' => null,
            ],

            'consultas' => [
                'codPrestador' => null,
                'fechaInicioAtencion' => null,
                'numAutorizacion' => null,
                'codConsulta' => null,
                'modalidadGrupoServicioTecSal' => null,
                'grupoServicios' => null,
                'codServicio' => null,
                'finalidadTecnologiaSalud' => null,
                'causaMotivoAtencion' => null,
                'codDiagnosticoPrincipal' => null,
                'codDiagnosticoRelacionado1' => null,
                'codDiagnosticoRelacionado2' => null,
                'codDiagnosticoRelacionado3' => null,
                'tipoDiagnosticoPrincipal' => null,
                'tipoDocumentoIdentificacion' => null,
                'numDocumentoIdentificacion' => null,
                'vrServicio' => null,
                'conceptoRecaudo' => null,
                'valorPagoModerador' => null,
                'numFEVPagoModerador' => null,
                'consecutivo' => null,
            ],

            'urgencias' => [
                'codPrestador' => null,
                'fechaInicioAtencion' => null,
                'causaMotivoAtencion' => null,
                'codDiagnosticoPrincipal' => null,
                'codDiagnosticoPrincipalE' => null,
                'codDiagnosticoRelacionadoE1' => null,
                'codDiagnosticoRelacionadoE2' => null,
                'codDiagnosticoRelacionadoE3' => null,
                'condicionDestinoUsuarioEgreso' => null,
                'codDiagnosticoCausaMuerte' => null,
                'fechaEgreso' => null,
                'consecutivo' => null,
            ],

            'hospitalizacion' => [
                'codPrestador' => null,
                'viaIngresoServicioSalud' => null,
                'fechaInicioAtencion' => null,
                'numAutorizacion' => null,
                'causaMotivoAtencion' => null,
                'codDiagnosticoPrincipal' => null,
                'codDiagnosticoPrincipalE' => null,
                'codDiagnosticoRelacionadoE1' => null,
                'codDiagnosticoRelacionadoE2' => null,
                'codDiagnosticoRelacionadoE3' => null,
                'codComplicacion' => null,
                'condicionDestinoUsuarioEgreso' => null,
                'codDiagnosticoCausaMuerte' => null,
                'fechaEgreso' => null,
                'consecutivo' => null,
            ],

            'medicamentos' => [
                'codPrestador' => null,
                'numAutorizacion' => null,
                'idMIPRES' => null,
                'fechaDispensAdmon' => null,
                'codDiagnosticoPrincipal' => null,
                'codDiagnosticoRelacionado' => null,
                'tipoMedicamento' => null,
                'codTecnologiaSalud' => null,
                'nomTecnologiaSalud' => null,
                'concentracionMedicamento' => null,
                'unidadMedida' => null,
                'formaFarmaceutica' => null,
                'unidadMinDispensa' => null,
                'cantidadMedicamento' => null,
                'diasTratamiento' => null,
                'tipoDocumentoIdentificacion' => null,
                'numDocumentoIdentificacion' => null,
                'vrUnitMedicamento' => null,
                'vrServicio' => null,
                'conceptoRecaudo' => null,
                'valorPagoModerador' => null,
                'numFEVPagoModerador' => null,
                'consecutivo' => null,
            ],

            'recienNacidos' => [
                'codPrestador' => null,
                'tipoDocumentoIdentificacion' => null,
                'numDocumentoIdentificacion' => null,
                'fechaNacimiento' => null,
                'edadGestacional' => null,
                'numConsultasCPrenatal' => null,
                'codSexoBiologico' => null,
                'peso' => null,
                'codDiagnosticoPrincipal' => null,
                'condicionDestinoUsuarioEgreso' => null,
                'codDiagnosticoCausaMuerte' => null,
                'fechaEgreso' => null,
                'consecutivo' => null,
            ],

            'otrosServicios' => [
                'codPrestador' => null,
                'numAutorizacion' => null,
                'idMIPRES' => null,
                'fechaSuministroTecnologia' => null,
                'tipoOS' => null,
                'codTecnologiaSalud' => null,
                'nomTecnologiaSalud' => null,
                'cantidadOS' => null,
                'tipoDocumentoIdentificacion' => null,
                'numDocumentoIdentificacion' => null,
                'vrUnitOS' => null,
                'vrServicio' => null,
                'conceptoRecaudo' => null,
                'valorPagoModerador' => null,
                'numFEVPagoModerador' => null,
                'consecutivo' => null,
            ]
        ];

        return $structures[$serviceType] ?? [
            'consecutivo' => null,
            'servicio' => $serviceType
        ];
    }

    /**
     * Mapea campos del CSV a campos de la estructura JSON de servicios
     * Considerando el tipo de servicio
     */
    protected function mapServiceField(string $csvField, string $serviceType): ?string
    {
        // Mapeo general que aplica a todos los servicios
        $generalMapping = [
            // Campos comunes
            'consecutivo' => 'consecutivo',
            'codprestador' => 'codPrestador',
            'numautorizacion' => 'numAutorizacion',
            'idmipres' => 'idMIPRES',
            'vrservicio' => 'vrServicio',
            'conceptorecaudo' => 'conceptoRecaudo',
            'valorpagomoderador' => 'valorPagoModerador',
            'numfevpagomoderador' => 'numFEVPagoModerador',
            'fechainicioatencion' => 'fechaInicioAtencion',
            'coddiagnosticoprincipal' => 'codDiagnosticoPrincipal',
            'viaingresoserviciosalud' => 'viaIngresoServicioSalud',
            'finalidadtecnologiasalud' => 'finalidadTecnologiaSalud',
            'coddiagnosticorelacionado' => 'codDiagnosticoRelacionado',
            'numdocumentoidentificacion' => 'numDocumentoIdentificacion',
            'tipodocumentoidentificacion' => 'tipoDocumentoIdentificacion',
            'modalidadgruposerviciotecsal' => 'modalidadGrupoServicioTecSal',
            'codcomplicacion' => 'codComplicacion',
            'fechaegreso' => 'fechaEgreso',
            'coddiagnosticocausamuerte' => 'codDiagnosticoCausaMuerte',

            // Campos específicos por servicio
            'codservicio' => 'codServicio',
            'codprocedimiento' => 'codProcedimiento',
            'gruposervicios' => 'grupoServicios',
            'codconsulta' => 'codConsulta',
            'causamotivoatencion' => 'causaMotivoAtencion',
            'coddiagnosticoprincipale' => 'codDiagnosticoPrincipalE',
            'coddiagnosticorelacionadoe1' => 'codDiagnosticoRelacionadoE1',
            'coddiagnosticorelacionadoe2' => 'codDiagnosticoRelacionadoE2',
            'coddiagnosticorelacionadoe3' => 'codDiagnosticoRelacionadoE3',
            'condiciondestinousuarioegreso' => 'condicionDestinoUsuarioEgreso',
            'fechasuministrotecnologia' => 'fechaSuministroTecnologia',
            'tipoos' => 'tipoOS',
            'codtecnologiasalud' => 'codTecnologiaSalud',
            'nomtecnologiasalud' => 'nomTecnologiaSalud',
            'cantidados' => 'cantidadOS',
            'vrunitos' => 'vrUnitOS',
            'fechadispensadmon' => 'fechaDispensAdmon',
            'tipomedicamento' => 'tipoMedicamento',
            'concentracionmedicamento' => 'concentracionMedicamento',
            'unidadmedida' => 'unidadMedida',
            'formafarmaceutica' => 'formaFarmaceutica',
            'unidadmindispensa' => 'unidadMinDispensa',
            'cantidadmedicamento' => 'cantidadMedicamento',
            'diastratamiento' => 'diasTratamiento',
            'vrunitmedicamento' => 'vrUnitMedicamento',
            'fechanacimiento' => 'fechaNacimiento',
            'edadgestacional' => 'edadGestacional',
            'numconsultascprenatal' => 'numConsultasCPrenatal',
            'codsexobiologico' => 'codSexoBiologico',
            'peso' => 'peso',
            'tipodiagnosticoprincipal' => 'tipoDiagnosticoPrincipal',

            // Campos comunes que pueden venir con diferentes nombres
            'fecha' => 'fechaInicioAtencion',
            'valor' => 'vrServicio',
            'codigo' => 'codServicio',
            'diagnostico' => 'codDiagnosticoPrincipal',
            'autorizacion' => 'numAutorizacion',
        ];

        $normalizedField = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $csvField));

        return $generalMapping[$normalizedField] ?? null;
    }

    protected function getUserFields(): array
    {
        return [
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
    }

    protected function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string) $name);
    }
}
