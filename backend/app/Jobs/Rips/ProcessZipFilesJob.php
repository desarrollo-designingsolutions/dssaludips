<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\ErrorCodes;
use App\Helpers\Rips\FormatDataTxt;
use App\Models\ProcessBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessZipFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $batchId;

    public function __construct(string $filePath, string $batchId, string $selectedQueue)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->onQueue($selectedQueue);
    }

    public function handle(): void
    {
        $redis = Redis::connection('redis_6380');
        try {
            $zip = new ZipArchive;
            if ($zip->open($this->filePath) !== true) {
                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    ErrorCodes::getMessage('ZIP_STR_001'),
                    ErrorCodes::ZIP_STR_001['code'],
                    null,
                    basename($this->filePath)
                );
                Log::error("Failed to open ZIP file for batch {$this->batchId}");
                $this->fail(new \Exception("Cannot open ZIP file"));
                return;
            }

            $tempDir = 'temp/rips/' . $this->batchId;
            Storage::disk('public')->makeDirectory($tempDir);
            $basePath = storage_path('app/public/' . $tempDir);
            $redis->set("rip_batch:{$this->batchId}:tempZip", $basePath);
            $redis->expire("rip_batch:{$this->batchId}:tempZip", 86400);
            // Log::info("Created temporary directory {$basePath} for batch {$this->batchId}");

            // Evento: Inicio de extracción del ZIP
            event(new ImportProgressEvent(
                $this->batchId,
                '0',
                "Extrayendo archivos del ZIP...",
                ErrorCollector::countErrors($this->batchId),
                'active',
                basename($this->filePath)
            ));

            $archivos = [];
            $totalRows = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (substr($name, -1) === '/') {
                    continue; // Saltar carpetas
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'txt') {
                    continue; // Saltar no-TXT
                }

                $filename = basename($name);
                $rutaTemporal = $tempDir . '/' . $filename;
                $fullTempPath = storage_path('app/public/' . $rutaTemporal);

                if (!$zip->extractTo($basePath, $name)) {
                    ErrorCollector::addError(
                        $this->batchId,
                        0,
                        null,
                        "No se pudo extraer el archivo {$filename}",
                        'ZIP_EXT_001',
                        null,
                        $filename
                    );
                    Log::error("Failed to extract {$filename} for batch {$this->batchId}");
                    $zip->close();
                    $this->fail(new \Exception("Failed to extract file {$filename}"));
                    return;
                }

                // Contar filas del archivo extraído
                $contenido = file_get_contents($fullTempPath);
                if (empty(trim($contenido))) {
                    ErrorCollector::addError(
                        $this->batchId,
                        0,
                        null,
                        ErrorCodes::getMessage('TXT_STR_001'),
                        ErrorCodes::TXT_STR_001['code'],
                        null,
                        $filename
                    );
                    Log::warning("Empty file {$filename} for batch {$this->batchId}");
                    continue;
                }

                $encoding = mb_detect_encoding($contenido, 'UTF-8, ISO-8859-1', true);
                if ($encoding !== 'UTF-8') {
                    $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
                }

                $contentDataArray = FormatDataTxt::execute($contenido);
                $countRows = count($contentDataArray);
                $totalRows += $countRows;

                // Log::info("Extracted {$filename} for batch {$this->batchId}: {$countRows} rows");

                $archivos[] = [
                    'name' => $filename,
                    'extension' => $ext,
                    'rutaTemporal' => $rutaTemporal,
                    'contentData' => $contenido,
                    'contentDataArray' => $contentDataArray,
                    'count_rows' => $countRows,
                    'type' => substr($filename, 0, 2),
                ];


                // Evento: Archivo extraído
                event(new ImportProgressEvent(
                    $this->batchId,
                    (string) $totalRows,
                    "Archivo {$filename} extraído ({$countRows} filas).",
                    ErrorCollector::countErrors($this->batchId),
                    'active',
                    $filename
                ));
            }

            $zip->close();

            // Log::info("archivos {$this->batchId}:", [$archivos]);
            // Log::info("Total rows for batch {$this->batchId}: {$totalRows}");

            // Guardar total_rows en los metadatos del batch
            $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
            $metadata['total_rows'] = $totalRows;
            $redis->hmset("batch:{$this->batchId}:metadata", $metadata);
            ProcessBatch::where('batch_id', $this->batchId)->update([
                'total_records' => $totalRows,
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);

            // Evento: Todos los archivos extraídos
            event(new ImportProgressEvent(
                $this->batchId,
                (string) $totalRows,
                basename($this->filePath),
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Todos los archivos extraídos. Total de filas a procesar: {$totalRows}",
            ));

            // Guardar metadatos en Redis
            $redis->set("rip_batch:{$this->batchId}:total_rows", $totalRows);
            $redis->set("rip_batch:{$this->batchId}:processed_rows", 0);
            $redis->set("rip_batch:{$this->batchId}:validationCt_codigoArchivos", json_encode([]));
            $redis->set("rip_batch:{$this->batchId}:files_txts", json_encode($archivos)); // Guardar todos los archivos en la clave :files_txts
            $redis->expire("rip_batch:{$this->batchId}:total_rows", 86400);
            $redis->expire("rip_batch:{$this->batchId}:processed_rows", 86400);
            $redis->expire("rip_batch:{$this->batchId}:validationCt_codigoArchivos", 86400);
            $redis->expire("rip_batch:{$this->batchId}:files_txts", 86400);



            // Procesar cada archivo en chunks
            foreach ($archivos as $file) {
                $prefix = strtoupper(substr($file['name'], 0, 2));
                $redis->set("rip_batch:{$this->batchId}:{$prefix}", json_encode($file));
                $redis->expire("rip_batch:{$this->batchId}:{$prefix}", 86400);

                $chunks = array_chunk($file['contentDataArray'], Constants::CHUNKSIZE);

                foreach ($chunks as $index => $chunk) {
                    $startRow = ($index * Constants::CHUNKSIZE) + 1;
                    $endRow = $startRow + count($chunk) - 1;
                    // Log::info("Dispatching chunk for {$file['name']} (rows {$startRow}-{$endRow})");
                    ProcessChunkJob::dispatch($this->batchId, $file['name'], $chunk, $startRow)->onQueue($this->queue);
                }
            }

            // Log::info("Temporary directory {$tempDir} will be cleaned up later for batch {$this->batchId}");
        } catch (\Throwable $e) {
            Log::error("Error in ProcessZipFilesJob for batch {$this->batchId}: {$e->getMessage()}");
            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                "Error procesando ZIP: {$e->getMessage()}",
                'ZIP_PROC_001',
                null,
                basename($this->filePath)
            );
            $this->fail($e);
        }
    }
}
