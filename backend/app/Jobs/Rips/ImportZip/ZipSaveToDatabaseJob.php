<?php

namespace App\Jobs\Rips\ImportZip;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Enums\Rip\RipStatusEnum;
use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\BuildAllDataToJson;
use App\Helpers\Rips\GenerateRipInfo;
use App\Models\Rip;
use App\Models\RipInvoice;
use App\Models\RipInvoiceUser;
use App\Models\RipServiceHospitalization;
use App\Models\RipServiceMedicine;
use App\Models\RipServiceNewlyBorn;
use App\Models\RipServiceOtherService;
use App\Models\RipServiceProcedure;
use App\Models\RipServiceQuery;
use App\Models\RipServiceUrgency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZipSaveToDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public int $chunkSize;
    public string $disk;
    public int $insertChunkSize;


    public function __construct(string $batchId, int $chunkSize = 100, string $disk = 'public', int $insertChunkSize = 1000, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->chunkSize = $chunkSize;
        $this->disk = $disk;
        $this->insertChunkSize = $insertChunkSize;
        $this->onQueue($selectedQueue); // ✅ Asignar la cola

    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $redisKey = "batch:{$this->batchId}:metadata";

        // Obtener metadata
        $metadata = $redis->hgetall($redisKey);
        $path_json = $metadata['path_json'];
        $rip_id = $metadata['rip_id'];
        $company_id = $metadata['company_id'] ?? null;
        $user_id = $metadata['user_id'] ?? null;
        $service_vendor_nit = $metadata['service_vendor_nit'] ?? null;
        $type = $metadata['type'] ?? null;
        $path_zip = $metadata['path_zip'] ?? null;


        // Cargar el JSON existente
        $disk = Storage::disk($this->disk);
        if (!$disk->exists($path_json)) {
            Log::error("SaveToDatabaseJob: Archivo JSON no encontrado en: {$path_json}");
            return;
        }


        try {
            $content = $disk->get($path_json);
            $allInvoicesData = json_decode($content, true);

            if (!$allInvoicesData || !is_array($allInvoicesData)) {
                Log::error("SaveToDatabaseJob: JSON inválido o vacío");
                return;
            }
        } catch (\Throwable $e) {
            Log::error("SaveToDatabaseJob: Error leyendo JSON: {$e->getMessage()}");
            return;
        }

        $totalInvoices = count($allInvoicesData);

        if ($totalInvoices === 0) {
            Log::warning("SaveToDatabaseJob: No se encontraron facturas en el JSON");
            return;
        }


        // FASE 1: INFORMAR INICIO
        event(new ImportProgressEvent(
            $this->batchId,
            0,
            "DB_SAVE_STARTING",
            ErrorCollector::countErrors($this->batchId),
            'processing',
            "Iniciando guardado en BD de {$totalInvoices} facturas desde JSON existente"
        ));

        // FASE 2: CREAR O REUTILIZAR REGISTRO RIP
        if ($rip_id) {
            // Reutilizar RIP existente
            $rip = Rip::find($rip_id);
            if (!$rip) {
                Log::error("SaveToDatabaseJob: RIP con ID {$rip_id} no encontrado");
                return;
            }
            $status = RipStatusEnum::RIP_STATUS_002;
        } else {
            // Crear nuevo RIP
            $status = RipStatusEnum::RIP_STATUS_002;
            $rip_id = Str::uuid();
            $rip = Rip::create([
                'id' => $rip_id,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'process_batch_id' => $this->batchId,
                'path_zip' => $path_zip ?? null,
                'nit' => $service_vendor_nit,
                'numInvoices' => $totalInvoices,
                'successfulInvoices' => 0,
                'failedInvoices' => $totalInvoices,
                'type' => $type,
                'sumVr' => 0,
                'status' => $status,
                'path_json' => $path_json, // Guardar la ruta del JSON existente
            ]);
        }




        event(new ImportProgressEvent(
            $this->batchId,
            0,
            "DB_RIP_CREATED",
            ErrorCollector::countErrors($this->batchId),
            'processing',
            "RIP " . ($rip_id ? "reutilizado" : "creado") . ", procesando {$totalInvoices} facturas desde JSON"
        ));



        // Variables para acumulación
        $totalRipSumVr = 0;
        $invoiceSumVrMap = [];

        $processedInvoices = 0;
        $processedUsers = 0;
        $processedServices = 0;

        // Procesar facturas por chunks
        foreach (array_chunk($allInvoicesData, $this->chunkSize) as $invoiceChunkIndex => $invoiceChunk) {
            $invoiceBatch = [];
            $userBatch = [];
            $serviceBatches = [
                'consultas' => [],
                'procedimientos' => [],
                'hospitalizacion' => [],
                'medicamentos' => [],
                'recienNacidos' => [],
                'urgencias' => [],
                'otrosServicios' => [],
            ];

            foreach ($invoiceChunk as $facturaData) {
                try {
                    // 0. Recalcular consecutivos y preparar datos
                    $facturaData = self::convertArraysToCollections($facturaData);
                    $facturaArray = [$facturaData];
                    BuildAllDataToJson::generateConsecutive($facturaArray);
                    $facturaData = $facturaArray[0];
                    $facturaData["numDocumentoIdObligado"] = $service_vendor_nit;
                    unset($facturaData['meta']);
                    $facturaData = $facturaData->toArray();

                    // 1. GUARDAR JSON INDIVIDUAL DE FACTURA (opcional - según necesites)
                    $invoiceFileName = $facturaData['numFactura'] . '.json';
                    $invoiceJsonPath = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip_id . '/invoices/' . $invoiceFileName;

                    try {
                        $disk->put($invoiceJsonPath, json_encode($facturaData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    } catch (\Throwable $e) {
                        Log::error("SaveToDatabaseJob: Error guardando JSON individual para factura {$invoiceFileName}: {$e->getMessage()}");
                        // Continuar aunque falle el guardado individual
                    }

                    // 2. CALCULAR sumVr PARA ESTA FACTURA
                    $invoiceSumVr = 0;
                    try {
                        $invoiceSumVr = GenerateRipInfo::sumVrServicio($facturaData);
                    } catch (\Throwable $e) {
                        Log::error("SaveToDatabaseJob: Error calculando sumVr para factura: {$e->getMessage()}");
                    }

                    $invoiceId = Str::uuid();
                    $invoiceSumVrMap[$invoiceId->toString()] = $invoiceSumVr;
                    $totalRipSumVr += $invoiceSumVr;

                    $processedInvoices++;

                    // 3. CREAR INVOICE
                    $invoiceBatch[] = [
                        'id' => $invoiceId,
                        "company_id" => $company_id,
                        "rip_id" => $rip_id,
                        "invoice_number" => $facturaData['numFactura'] ?? null,
                        "status" => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->value,
                        "status_xml" => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002->value,
                        "path_json" => $invoiceJsonPath,
                        "path_xml" => null,
                        "path_excel" => null,
                        "sumVr" => $invoiceSumVr,
                        "count_users" => count($facturaData['usuarios'] ?? []),
                        "tipoNota" => $facturaData['tipoNota'] ?? null,
                        "numNota" => $facturaData['numNota'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // 4. CREAR USUARIOS
                    if (!empty($facturaData['usuarios']) && is_array($facturaData['usuarios'])) {
                        foreach ($facturaData['usuarios'] as $usuarioData) {
                            $userId = Str::uuid();
                            $userBatch[] = [
                                'id' => $userId,
                                "rip_invoice_id" => $invoiceId,
                                "tipoDocumentoIdentificacion" => $usuarioData["tipoDocumentoIdentificacion"] ?? null,
                                "numDocumentoIdentificacion" => $usuarioData["numDocumentoIdentificacion"] ?? null,
                                "tipoUsuario" => $usuarioData["tipoUsuario"] ?? null,
                                "fechaNacimiento" => $usuarioData["fechaNacimiento"] ?? null,
                                "codSexo" => $usuarioData["codSexo"] ?? null,
                                "codPaisResidencia" => $usuarioData["codPaisResidencia"] ?? null,
                                "codMunicipioResidencia" => $usuarioData["codMunicipioResidencia"] ?? null,
                                "codZonaTerritorialResidencia" => $usuarioData["codZonaTerritorialResidencia"] ?? null,
                                "incapacidad" => $usuarioData["incapacidad"] ?? null,
                                "codPaisOrigen" => $usuarioData["codPaisOrigen"] ?? null,
                                "consecutivo" => $usuarioData["consecutivo"] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            $processedUsers++;

                            // 5. CREAR SERVICIOS
                            if (!empty($usuarioData['servicios']) && is_array($usuarioData['servicios'])) {
                                $serviciosData = $usuarioData['servicios'];

                                foreach ($serviciosData as $tipoServicio => $serviciosArray) {
                                    if (is_array($serviciosArray)) {
                                        foreach ($serviciosArray as $servicioData) {
                                            $serviceId = Str::uuid();

                                            $camposEspecificos = $this->getFieldsByType($tipoServicio, $servicioData);

                                            $serviceData = array_merge([
                                                'id' => $serviceId,
                                                'rip_invoice_user_id' => $userId,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ], $camposEspecificos);

                                            // Agrupar por tipo de servicio
                                            if (isset($serviceBatches[$tipoServicio])) {
                                                $serviceBatches[$tipoServicio][] = $serviceData;
                                            }

                                            $processedServices++;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // PROGRESO CADA 5 FACTURAS
                    if ($processedInvoices % 5 === 0) {
                        event(new ImportProgressEvent(
                            $this->batchId,
                            $processedInvoices,
                            "DB_SAVE_PROGRESS",
                            ErrorCollector::countErrors($this->batchId),
                            'processing',
                            "Procesadas {$processedInvoices}/{$totalInvoices} facturas - Usuarios: {$processedUsers}, Servicios: {$processedServices}"
                        ));

                    }
                } catch (\Throwable $e) {
                    Log::error("SaveToDatabaseJob: Error procesando factura: {$e->getMessage()}");
                    continue;
                }
            }

            // INSERT MASIVO DEL CHUNK ACTUAL - CON SUB-CHUNKS
            try {
                $totalInserts = 0;

                // Chunkear invoices
                if (!empty($invoiceBatch)) {
                    foreach (array_chunk($invoiceBatch, $this->insertChunkSize) as $invoiceSubChunk) {
                        RipInvoice::insert($invoiceSubChunk);
                        $totalInserts += count($invoiceSubChunk);
                    }
                }

                // Chunkear usuarios
                if (!empty($userBatch)) {
                    foreach (array_chunk($userBatch, $this->insertChunkSize) as $userSubChunk) {
                        RipInvoiceUser::insert($userSubChunk);
                        $totalInserts += count($userSubChunk);
                    }
                }

                // Chunkear servicios POR TIPO
                foreach ($serviceBatches as $tipoServicio => $chunk) {
                    if (!empty($chunk)) {
                        $model = $this->getServiceModelByType($tipoServicio);

                        foreach (array_chunk($chunk, $this->insertChunkSize) as $serviceSubChunk) {
                            $model::insert($serviceSubChunk);
                            $totalInserts += count($serviceSubChunk);
                        }
                    }
                }

                // INFORMAR CHUNK GUARDADO
                $totalServicesByType = [];
                foreach ($serviceBatches as $tipoServicio => $chunk) {
                    if (!empty($chunk)) {
                        $totalServicesByType[$tipoServicio] = count($chunk);
                    }
                }

                $servicesMessage = "";
                if (!empty($totalServicesByType)) {
                    $servicesParts = [];
                    foreach ($totalServicesByType as $tipo => $cantidad) {
                        $servicesParts[] = "{$cantidad} {$tipo}";
                    }
                    $servicesMessage = implode(", ", $servicesParts);
                } else {
                    $servicesMessage = "0 servicios";
                }

                event(new ImportProgressEvent(
                    $this->batchId,
                    $processedInvoices,
                    "DB_CHUNK_SAVED",
                    ErrorCollector::countErrors($this->batchId),
                    'processing',
                    "Chunk #" . ($invoiceChunkIndex + 1) . " guardado: " .
                        count($invoiceBatch) . " facturas, " .
                        count($userBatch) . " usuarios, " .
                        $servicesMessage
                ));

            } catch (\Throwable $e) {
                Log::error("SaveToDatabaseJob: Error en insert masivo: {$e->getMessage()}");

                event(new ImportProgressEvent(
                    $this->batchId,
                    $processedInvoices,
                    "DB_SAVE_ERROR",
                    ErrorCollector::countErrors($this->batchId),
                    'failed',
                    "Error guardando chunk #" . ($invoiceChunkIndex + 1) . ": " . $e->getMessage()
                ));
            }

            // Limpiar batches temporales
            $invoiceBatch = [];
            $userBatch = [];
            $serviceBatches = [
                'consultas' => [],
                'procedimientos' => [],
                'hospitalizacion' => [],
                'medicamentos' => [],
                'recienNacidos' => [],
                'urgencias' => [],
                'otrosServicios' => [],
            ];
        }


        // 6. ACTUALIZAR RIP CON DATOS FINALES (solo si es nuevo RIP)
        if (!$metadata['rip_id']) {
            try {
                $finalRipSumVr = GenerateRipInfo::sumVrServicioRips($allInvoicesData);

                $rip->update([
                    'sumVr' => $finalRipSumVr,
                    'successfulInvoices' => $processedInvoices,
                    'failedInvoices' => $totalInvoices - $processedInvoices,
                ]);

                Log::info("SaveToDatabaseJob: Actualizado sumVr del RIP: {$finalRipSumVr}");
            } catch (\Throwable $e) {
                Log::error("SaveToDatabaseJob: Error actualizando RIP: {$e->getMessage()}");
            }
        }

        // FASE FINAL: INFORMAR COMPLETACIÓN
        event(new ImportProgressEvent(
            $this->batchId,
            $totalInvoices,
            "DB_SAVE_COMPLETED",
            ErrorCollector::countErrors($this->batchId),
            'completed',
            "Guardado en BD completado: {$processedInvoices} facturas, {$processedUsers} usuarios, {$processedServices} servicios, SumVr: {$totalRipSumVr}"
        ));


        Log::info("SaveToDatabaseJob: completado batch={$this->batchId} - " .
            "Facturas: {$processedInvoices}/{$totalInvoices}, " .
            "Usuarios: {$processedUsers}, " .
            "Servicios: {$processedServices}, " .
            "SumVr: {$totalRipSumVr}");


        // LIMPIAR METADATA DE REDIS (opcional)
        // $redis->del($redisKey);

    }

    /**
     * Obtiene el modelo correspondiente según el tipo de servicio
     */
    private function getServiceModelByType($tipoServicio)
    {
        switch ($tipoServicio) {
            case 'consultas':
                return RipServiceQuery::class;
            case 'procedimientos':
                return RipServiceProcedure::class;
            case 'hospitalizacion':
                return RipServiceHospitalization::class;
            case 'medicamentos':
                return RipServiceMedicine::class;
            case 'recienNacidos':
                return RipServiceNewlyBorn::class;
            case 'urgencias':
                return RipServiceUrgency::class;
            case 'otrosServicios':
                return RipServiceOtherService::class;
            default:
                throw new \Exception("Tipo de servicio no válido: {$tipoServicio}");
        }
    }

    // Función que retorna campos según el tipo
    private function getFieldsByType($tipoServicio, $servicioData)
    {
        switch ($tipoServicio) {
            case 'consultas':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'fechaInicioAtencion' => $servicioData["fechaInicioAtencion"] ?? null,
                    'numAutorizacion' => $servicioData["numAutorizacion"] ?? null,
                    'codConsulta' => $servicioData["codConsulta"] ?? null,
                    'modalidadGrupoServicioTecSal' => $servicioData["modalidadGrupoServicioTecSal"] ?? null,
                    'grupoServicios' => $servicioData["grupoServicios"] ?? null,
                    'codServicio' => $servicioData["codServicio"] ?? null,
                    'finalidadTecnologiaSalud' => $servicioData["finalidadTecnologiaSalud"] ?? null,
                    'causaMotivoAtencion' => $servicioData["causaMotivoAtencion"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'codDiagnosticoRelacionado1' => $servicioData["codDiagnosticoRelacionado1"] ?? null,
                    'codDiagnosticoRelacionado2' => $servicioData["codDiagnosticoRelacionado2"] ?? null,
                    'codDiagnosticoRelacionado3' => $servicioData["codDiagnosticoRelacionado3"] ?? null,
                    'tipoDiagnosticoPrincipal' => $servicioData["tipoDiagnosticoPrincipal"] ?? null,
                    'tipoDocumentoIdentificacion' => $servicioData["tipoDocumentoIdentificacion"] ?? null,
                    'numDocumentoIdentificacion' => $servicioData["numDocumentoIdentificacion"] ?? null,
                    'vrServicio' => $servicioData["vrServicio"] ?? null,
                    'conceptoRecaudo' => $servicioData["conceptoRecaudo"] ?? null,
                    'valorPagoModerador' => $servicioData["valorPagoModerador"] ?? null,
                    'numFEVPagoModerador' => $servicioData["numFEVPagoModerador"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];

            case 'procedimientos':
                return [
                    'codPrestador' => $servicioData["codProcedimiento"] ?? null,
                    'fechaInicioAtencion' => $servicioData["fechaInicioAtencion"] ?? null,
                    'idMIPRES' => $servicioData["idMIPRES"] ?? null,
                    'numAutorizacion' => $servicioData["numAutorizacion"] ?? null,
                    'codProcedimiento' => $servicioData["codProcedimiento"] ?? null,
                    'viaIngresoServicioSalud' => $servicioData["viaIngresoServicioSalud"] ?? null,
                    'modalidadGrupoServicioTecSal' => $servicioData["modalidadGrupoServicioTecSal"] ?? null,
                    'grupoServicios' => $servicioData["grupoServicios"] ?? null,
                    'codServicio' => $servicioData["codServicio"] ?? null,
                    'finalidadTecnologiaSalud' => $servicioData["finalidadTecnologiaSalud"] ?? null,
                    'tipoDocumentoIdentificacion' => $servicioData["tipoDocumentoIdentificacion"] ?? null,
                    'numDocumentoIdentificacion' => $servicioData["numDocumentoIdentificacion"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'codDiagnosticoRelacionado' => $servicioData["codDiagnosticoRelacionado"] ?? null,
                    'codComplicacion' => $servicioData["codComplicacion"] ?? null,
                    'vrServicio' => $servicioData["vrServicio"] ?? null,
                    'conceptoRecaudo' => $servicioData["conceptoRecaudo"] ?? null,
                    'valorPagoModerador' => $servicioData["valorPagoModerador"] ?? null,
                    'numFEVPagoModerador' => $servicioData["numFEVPagoModerador"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];

            case 'hospitalizacion':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'viaIngresoServicioSalud' => $servicioData["viaIngresoServicioSalud"] ?? null,
                    'fechaInicioAtencion' => $servicioData["fechaInicioAtencion"] ?? null,
                    'numAutorizacion' => $servicioData["numAutorizacion"] ?? null,
                    'causaMotivoAtencion' => $servicioData["causaMotivoAtencion"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'codDiagnosticoPrincipalE' => $servicioData["codDiagnosticoPrincipalE"] ?? null,
                    'codDiagnosticoRelacionadoE1' => $servicioData["codDiagnosticoRelacionadoE1"] ?? null,
                    'codDiagnosticoRelacionadoE2' => $servicioData["codDiagnosticoRelacionadoE2"] ?? null,
                    'codDiagnosticoRelacionadoE3' => $servicioData["codDiagnosticoRelacionadoE3"] ?? null,
                    'codComplicacion' => $servicioData["codComplicacion"] ?? null,
                    'condicionDestinoUsuarioEgreso' => $servicioData["condicionDestinoUsuarioEgreso"] ?? null,
                    'codDiagnosticoCausaMuerte' => $servicioData["codDiagnosticoCausaMuerte"] ?? null,
                    'fechaEgreso' => $servicioData["fechaEgreso"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];
            case 'medicamentos':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'numAutorizacion' => $servicioData["numAutorizacion"] ?? null,
                    'idMIPRES' => $servicioData["idMIPRES"] ?? null,
                    'fechaDispensAdmon' => $servicioData["fechaDispensAdmon"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'codDiagnosticoRelacionado' => $servicioData["codDiagnosticoRelacionado"] ?? null,
                    'tipoMedicamento' => $servicioData["tipoMedicamento"] ?? null,
                    'codTecnologiaSalud' => $servicioData["codTecnologiaSalud"] ?? null,
                    'nomTecnologiaSalud' => $servicioData["nomTecnologiaSalud"] ?? null,
                    'concentracionMedicamento' => $servicioData["concentracionMedicamento"] ?? null,
                    'unidadMedida' => $servicioData["unidadMedida"] ?? null,
                    'formaFarmaceutica' => $servicioData["formaFarmaceutica"] ?? null,
                    'unidadMinDispensa' => $servicioData["unidadMinDispensa"] ?? null,
                    'cantidadMedicamento' => $servicioData["cantidadMedicamento"] ?? null,
                    'diasTratamiento' => $servicioData["diasTratamiento"] ?? null,
                    'tipoDocumentoIdentificacion' => $servicioData["tipoDocumentoIdentificacion"] ?? null,
                    'numDocumentoIdentificacion' => $servicioData["numDocumentoIdentificacion"] ?? null,
                    'vrUnitMedicamento' => $servicioData["vrUnitMedicamento"] ?? null,
                    'vrServicio' => $servicioData["vrServicio"] ?? null,
                    'conceptoRecaudo' => $servicioData["conceptoRecaudo"] ?? null,
                    'valorPagoModerador' => $servicioData["valorPagoModerador"] ?? null,
                    'numFEVPagoModerador' => $servicioData["numFEVPagoModerador"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];
            case 'recienNacidos':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'tipoDocumentoIdentificacion' => $servicioData["tipoDocumentoIdentificacion"] ?? null,
                    'numDocumentoIdentificacion' => $servicioData["numDocumentoIdentificacion"] ?? null,
                    'fechaNacimiento' => $servicioData["fechaNacimiento"] ?? null,
                    'edadGestacional' => $servicioData["edadGestacional"] ?? null,
                    'numConsultasCPrenatal' => $servicioData["numConsultasCPrenatal"] ?? null,
                    'codSexoBiologico' => $servicioData["codSexoBiologico"] ?? null,
                    'peso' => $servicioData["peso"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'condicionDestinoUsuarioEgreso' => $servicioData["condicionDestinoUsuarioEgreso"] ?? null,
                    'codDiagnosticoCausaMuerte' => $servicioData["codDiagnosticoCausaMuerte"] ?? null,
                    'fechaEgreso' => $servicioData["fechaEgreso"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];
            case 'urgencias':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'fechaInicioAtencion' => $servicioData["fechaInicioAtencion"] ?? null,
                    'causaMotivoAtencion' => $servicioData["causaMotivoAtencion"] ?? null,
                    'codDiagnosticoPrincipal' => $servicioData["codDiagnosticoPrincipal"] ?? null,
                    'codDiagnosticoPrincipalE' => $servicioData["codDiagnosticoPrincipalE"] ?? null,
                    'codDiagnosticoRelacionadoE1' => $servicioData["codDiagnosticoRelacionadoE1"] ?? null,
                    'codDiagnosticoRelacionadoE2' => $servicioData["codDiagnosticoRelacionadoE2"] ?? null,
                    'codDiagnosticoRelacionadoE3' => $servicioData["codDiagnosticoRelacionadoE3"] ?? null,
                    'condicionDestinoUsuarioEgreso' => $servicioData["condicionDestinoUsuarioEgreso"] ?? null,
                    'codDiagnosticoCausaMuerte' => $servicioData["codDiagnosticoCausaMuerte"] ?? null,
                    'fechaEgreso' => $servicioData["fechaEgreso"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,
                ];
            case 'otrosServicios':
                return [
                    'codPrestador' => $servicioData["codPrestador"] ?? null,
                    'numAutorizacion' => $servicioData["numAutorizacion"] ?? null,
                    'idMIPRES' => $servicioData["idMIPRES"] ?? null,
                    'fechaSuministroTecnologia' => $servicioData["fechaSuministroTecnologia"] ?? null,
                    'tipoOS' => $servicioData["tipoOS"] ?? null,
                    'codTecnologiaSalud' => $servicioData["codTecnologiaSalud"] ?? null,
                    'nomTecnologiaSalud' => $servicioData["nomTecnologiaSalud"] ?? null,
                    'cantidadOS' => $servicioData["cantidadOS"] ?? null,
                    'tipoDocumentoIdentificacion' => $servicioData["tipoDocumentoIdentificacion"] ?? null,
                    'numDocumentoIdentificacion' => $servicioData["numDocumentoIdentificacion"] ?? null,
                    'vrUnitOS' => $servicioData["vrUnitOS"] ?? null,
                    'vrServicio' => $servicioData["vrServicio"] ?? null,
                    'conceptoRecaudo' => $servicioData["conceptoRecaudo"] ?? null,
                    'valorPagoModerador' => $servicioData["valorPagoModerador"] ?? null,
                    'numFEVPagoModerador' => $servicioData["numFEVPagoModerador"] ?? null,
                    'consecutivo' => $servicioData["consecutivo"] ?? null,

                ];

            default:
                return []; // Campos por defecto o vacío
        }
    }

    /**
     * Convierte arrays anidados a colecciones de Laravel
     */
    private static function convertArraysToCollections($data)
    {
        if (is_array($data)) {
            // Convertir array a colección
            $collection = collect($data);

            // Recursivamente convertir elementos anidados
            return $collection->map(function ($item) {
                if (is_array($item)) {
                    return self::convertArraysToCollections($item);
                }
                return $item;
            });
        }

        return $data;
    }
}
