<?php

namespace App\Jobs\Rips\ImportCsv;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Enums\Rip\RipStatusEnum;
use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\GenerateRipInfo;
use App\Models\Rip;
use App\Models\RipInvoice;
use App\Models\RipInvoiceUser;
use App\Models\RipServiceQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveToDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public int $chunkSize;
    public string $disk;
    public int $insertChunkSize;

    public function __construct(string $batchId, int $chunkSize = 100, string $disk = 'public', int $insertChunkSize = 1000)
    {
        $this->batchId = $batchId;
        $this->chunkSize = $chunkSize;
        $this->disk = $disk;
        $this->insertChunkSize = $insertChunkSize;
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $metaKey = "import:batch:{$this->batchId}:meta";
        $redisKey = "batch:{$this->batchId}:metadata";
        $groupsFolder = "temp/rips/{$this->batchId}/groups";

        Log::info("SaveToDatabaseJob: iniciando guardado en BD batch={$this->batchId}");

        // Obtener metadata
        $metadata = $redis->hgetall($redisKey);
        $type = $metadata['type'] ?? 'ripsCsv';
        $company_id = $metadata['company_id'] ?? null;
        $user_id = $metadata['user_id'] ?? null;

        if (!$company_id || !$user_id) {
            Log::error("SaveToDatabaseJob: company_id o user_id no encontrados en metadata");
            return;
        }

        // Contar archivos JSON (facturas)
        $disk = Storage::disk($this->disk);
        $files = $disk->files($groupsFolder);
        $jsonFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'json';
        });

        $totalInvoices = count($jsonFiles);

        if ($totalInvoices === 0) {
            Log::warning("SaveToDatabaseJob: No se encontraron archivos JSON para procesar");
            return;
        }

        // FASE 1: INFORMAR INICIO
        event(new ImportProgressEvent(
            $this->batchId,
            0,
            "DB_SAVE_STARTING",
            ErrorCollector::countErrors($this->batchId),
            'processing',
            "Iniciando guardado en BD de {$totalInvoices} facturas"
        ));

        // FASE 2: CREAR REGISTRO RIP PRINCIPAL
        $status = RipStatusEnum::RIP_STATUS_002;
        $ripId = Str::uuid();
        $rip = Rip::create([
            'id' => $ripId,
            'company_id' => $company_id,
            'user_id' => $user_id,
            'process_batch_id' => $this->batchId,
            'path_zip' => null,
            'nit' => null,
            'numInvoices' => $totalInvoices,
            'successfulInvoices' => 0,
            'failedInvoices' => $totalInvoices,
            'type' => $type,
            'sumVr' => 0,
            'status' => $status,
        ]);

        event(new ImportProgressEvent(
            $this->batchId,
            0,
            "DB_RIP_CREATED",
            ErrorCollector::countErrors($this->batchId),
            'processing',
            "RIP general creado, procesando {$totalInvoices} facturas"
        ));

        // Variables para acumulación
        $allInvoicesData = [];
        $totalRipSumVr = 0;
        $invoiceSumVrMap = [];

        $processedInvoices = 0;
        $processedUsers = 0;
        $processedServices = 0;

        // Procesar archivos JSON por chunks
        foreach (array_chunk($jsonFiles, $this->chunkSize) as $fileChunkIndex => $fileChunk) {
            $invoiceChunk = [];
            $userChunk = [];
            $serviceChunk = [];

            foreach ($fileChunk as $file) {
                try {
                    $content = $disk->get($file);
                    $facturaData = json_decode($content, true);

                    if (!$facturaData) {
                        Log::warning("SaveToDatabaseJob: Error decodificando JSON: {$file}");
                        continue;
                    }

                    // 1. GUARDAR JSON INDIVIDUAL DE FACTURA
                    $invoiceFileName = $facturaData['numFactura'] . '.json';
                    $invoiceJsonPath = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $ripId . '/invoices/' . $invoiceFileName;

                    try {
                        $disk->put($invoiceJsonPath, json_encode($facturaData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    } catch (\Throwable $e) {
                        Log::error("SaveToDatabaseJob: Error guardando JSON individual para factura {$invoiceFileName}: {$e->getMessage()}");
                        continue;
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
                    $allInvoicesData[] = $facturaData;

                    $processedInvoices++;

                    // 3. CREAR INVOICE
                    $invoiceChunk[] = [
                        'id' => $invoiceId,
                        "company_id" => $company_id,
                        "rip_id" => $ripId,
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
                            $userChunk[] = [
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
                                            $serviceChunk[] = [
                                                'id' => $serviceId,
                                                'rip_invoice_user_id' => $userId,
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
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ];

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
                    Log::error("SaveToDatabaseJob: Error procesando archivo {$file}: {$e->getMessage()}");
                    continue;
                }
            }

            // INSERT MASIVO DEL CHUNK ACTUAL - CON SUB-CHUNKS
            try {
                $totalInserts = 0;

                // Chunkear invoices
                if (!empty($invoiceChunk)) {
                    foreach (array_chunk($invoiceChunk, $this->insertChunkSize) as $invoiceSubChunk) {
                        RipInvoice::insert($invoiceSubChunk);
                        $totalInserts += count($invoiceSubChunk);
                        Log::info("SaveToDatabaseJob: Insertados " . count($invoiceSubChunk) . " invoices");
                    }
                }

                // Chunkear usuarios
                if (!empty($userChunk)) {
                    foreach (array_chunk($userChunk, $this->insertChunkSize) as $userSubChunk) {
                        RipInvoiceUser::insert($userSubChunk);
                        $totalInserts += count($userSubChunk);
                        Log::info("SaveToDatabaseJob: Insertados " . count($userSubChunk) . " usuarios");
                    }
                }

                // Chunkear servicios
                if (!empty($serviceChunk)) {
                    foreach (array_chunk($serviceChunk, $this->insertChunkSize) as $serviceSubChunk) {
                        RipServiceQuery::insert($serviceSubChunk);
                        $totalInserts += count($serviceSubChunk);
                        Log::info("SaveToDatabaseJob: Insertados " . count($serviceSubChunk) . " servicios");
                    }
                }

                // INFORMAR CHUNK GUARDADO
                event(new ImportProgressEvent(
                    $this->batchId,
                    $processedInvoices,
                    "DB_CHUNK_SAVED",
                    ErrorCollector::countErrors($this->batchId),
                    'processing',
                    "Chunk #" . ($fileChunkIndex + 1) . " guardado: " .
                    count($invoiceChunk) . " facturas, " .
                    count($userChunk) . " usuarios, " .
                    count($serviceChunk) . " servicios"
                ));

                Log::info("SaveToDatabaseJob: Chunk #" . ($fileChunkIndex + 1) . " procesado - " .
                    "Facturas: " . count($invoiceChunk) .
                    ", Usuarios: " . count($userChunk) .
                    ", Servicios: " . count($serviceChunk) .
                    ", Total inserts: " . $totalInserts);

            } catch (\Throwable $e) {
                Log::error("SaveToDatabaseJob: Error en insert masivo: {$e->getMessage()}");

                event(new ImportProgressEvent(
                    $this->batchId,
                    $processedInvoices,
                    "DB_SAVE_ERROR",
                    ErrorCollector::countErrors($this->batchId),
                    'failed',
                    "Error guardando chunk #" . ($fileChunkIndex + 1) . ": " . $e->getMessage()
                ));
            }

            // Limpiar chunks temporales
            $invoiceChunk = [];
            $userChunk = [];
            $serviceChunk = [];
        }

        // 6. GUARDAR JSON UNIFICADO Y CALCULAR sumVr FINAL
        $finalRipSumVr = $totalRipSumVr;
        $unifiedJsonPath = null;

        try {
            $unifiedFileName = "rips_{$ripId}.json";
            $unifiedJsonPath = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $ripId . '/' . $unifiedFileName;

            $unifiedJsonContent = json_encode($allInvoicesData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $disk->put($unifiedJsonPath, $unifiedJsonContent);

            Log::info("SaveToDatabaseJob: JSON unificado guardado en: {$unifiedJsonPath}");

            // Calcular sumVr final usando el método para múltiples facturas
            $finalRipSumVr = GenerateRipInfo::sumVrServicioRips($allInvoicesData);

        } catch (\Throwable $e) {
            Log::error("SaveToDatabaseJob: Error guardando JSON unificado: {$e->getMessage()}");
            // Si falla, usamos la suma acumulada de las facturas individuales
        }

        // 7. ACTUALIZAR RIP CON sumVr FINAL
        try {
            $updateData = [
                'sumVr' => $finalRipSumVr,
            ];

            if ($unifiedJsonPath) {
                $updateData['path_json'] = $unifiedJsonPath;
            }

            $rip->update($updateData);

            Log::info("SaveToDatabaseJob: Actualizado sumVr del RIP: {$finalRipSumVr}");
        } catch (\Throwable $e) {
            Log::error("SaveToDatabaseJob: Error actualizando RIP: {$e->getMessage()}");
        }

        // FASE FINAL: INFORMAR COMPLETACIÓN
        event(new ImportProgressEvent(
            $this->batchId,
            $totalInvoices,
            "DB_SAVE_COMPLETED",
            ErrorCollector::countErrors($this->batchId),
            'completed',
            "Guardado en BD completado: {$processedInvoices} facturas, {$processedUsers} usuarios, {$processedServices} servicios, SumVr: {$finalRipSumVr}"
        ));

        Log::info("SaveToDatabaseJob: completado batch={$this->batchId} - " .
            "Facturas: {$processedInvoices}/{$totalInvoices}, " .
            "Usuarios: {$processedUsers}, " .
            "Servicios: {$processedServices}, " .
            "SumVr: {$finalRipSumVr}");

        // LIMPIAR ARCHIVOS TEMPORALES
        $this->cleanupTempFiles($disk, $groupsFolder);
    }

    /**
     * Limpia archivos temporales después del procesamiento
     */
    protected function cleanupTempFiles($disk, $groupsFolder): void
    {
        try {
            if ($disk->exists($groupsFolder)) {
                $files = $disk->files($groupsFolder);
                foreach ($files as $file) {
                    $disk->delete($file);
                }
                // También eliminar la carpeta vacía
                $disk->deleteDirectory($groupsFolder);
                Log::info("SaveToDatabaseJob: Archivos temporales limpiados: {$groupsFolder}");
            }
        } catch (\Throwable $e) {
            Log::warning("SaveToDatabaseJob: Error limpiando archivos temporales: {$e->getMessage()}");
        }
    }
}
