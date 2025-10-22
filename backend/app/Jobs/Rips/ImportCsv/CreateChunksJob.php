<?php

namespace App\Jobs\Rips\ImportCsv;

use App\Events\ImportProgressEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CreateChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public string $filePath;
    public string $disk;
    public int $chunkSize;

    /**
     * @param string $batchId  Id del proceso (ProcessBatch.id)
     * @param string $filePath Ruta relativa en el disk (storage) donde está el CSV
     * @param string|null $disk  Disk name (ej. 'public'). Si null, usa Constants::DISK_FILES o default.
     * @param int|null $chunkSize filas por chunk (override ENV)
     */
    public function __construct(string $batchId, string $filePath, ?string $disk = null, ?int $chunkSize = null)
    {
        $this->batchId = $batchId;
        $this->filePath = $filePath;
        $this->disk = $disk ?: config('filesystems.default');
        $this->chunkSize = $chunkSize ?: (int) env('IMPORT_CHUNK_SIZE', 100000);
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $metaKey = "import:batch:{$this->batchId}:meta";
        $chunksPrefix = "import:batch:{$this->batchId}:chunk:"; // we'll HSET per chunk
        $chunksFolder = "temp/rips/{$this->batchId}/chunks";

        Log::info("CreateChunksJob: iniciando chunking batch={$this->batchId} file={$this->filePath} disk={$this->disk} chunkSize={$this->chunkSize}");

        // emitimos evento inicial
        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando chunking', 0, 'processing', 'CSV'));

        // Borrar meta previa si existe (idempotencia)
        try {
            $redis->del($metaKey);
        } catch (\Throwable $e) {
            Log::warning("CreateChunksJob: no se pudo limpiar meta redis previo: {$e->getMessage()}");
        }

        // Asegurar carpeta existe (Storage lo hace automáticamente al escribir)
        $disk = Storage::disk($this->disk);

        // Abrir stream de lectura
        $stream = $disk->readStream($this->filePath);
        if ($stream === false) {
            Log::error("CreateChunksJob: no se pudo abrir archivo: {$this->filePath} en disk {$this->disk}");
            $redis->hset($metaKey, 'status', 'chunking_failed');
            return;
        }

        // Leer header (primera línea) y normalizar (mantener tal cual para cada chunk)
        $headerLine = fgets($stream);
        if ($headerLine === false) {
            fclose($stream);
            Log::error("CreateChunksJob: archivo vacío o sin header: {$this->filePath}");
            $redis->hset($metaKey, 'status', 'chunking_failed');
            return;
        }
        $headerLine = preg_replace('/\r\n|\r|\n$/', '', $headerLine); // quitar newline al final

        $chunkIndex = 0;
        $currentRowCount = 0;
        $currentTemp = null; // stream resource for the chunk buffer
        $rowsProcessedTotal = 0;

        // función local para flush del temp stream a storage y registrar metadata
        $flushChunk = function () use (&$chunkIndex, &$currentTemp, &$currentRowCount, &$rowsProcessedTotal, $disk, $chunksFolder, $redis, $metaKey, $chunksPrefix) {
            if (! $currentTemp) return null;
            rewind($currentTemp);

            $chunkIndex++;
            $chunkId = "c-{$chunkIndex}";
            $chunkPath = "{$chunksFolder}/chunk_{$chunkId}.csv";

            // Put stream to storage (overwrites if exists)
            try {
                // Ensure parent directory exists in storage: putStream will create path
                // $disk->putStream($chunkPath, $currentTemp);



                // --- Bloque robusto para escribir chunk al storage (reemplaza el anterior) ---

                try {
                    // debug: una sola vez por flush — opcional
                    $diskClass = is_object($disk) ? get_class($disk) : 'unknown';
                    Log::info("CreateChunksJob: disk adapter class={$diskClass}");

                    // Preferimos usar writeStream si está disponible (tu adapter local lo expone)
                    if (method_exists($disk, 'writeStream') && is_callable([$disk, 'writeStream'])) {
                        // Asegurarnos de posicionar el stream al inicio
                        rewind($currentTemp);
                        $success = $disk->writeStream($chunkPath, $currentTemp);
                        if ($success === false) {
                            throw new \RuntimeException("writeStream devolvió false para {$chunkPath}");
                        }
                    } else {
                        // Fallback robusto: escribir a archivo temporal local y luego subir con put + fopen
                        $tmpFolder = storage_path('app/tmp/rips/' . $this->batchId);
                        if (! is_dir($tmpFolder)) {
                            @mkdir($tmpFolder, 0755, true);
                        }

                        $tmpFile = $tmpFolder . '/chunk_' . $chunkId . '_' . uniqid() . '.tmp';
                        $tmpFp = fopen($tmpFile, 'w+');
                        if ($tmpFp === false) {
                            throw new \RuntimeException("No se pudo crear temporal {$tmpFile}");
                        }

                        // copiar stream $currentTemp -> $tmpFp sin cargar todo en memoria
                        rewind($currentTemp);
                        stream_copy_to_stream($currentTemp, $tmpFp);
                        fflush($tmpFp);
                        fclose($tmpFp);

                        // abrir para lectura y subir con put (aceptará resource o contenido)
                        $readFp = fopen($tmpFile, 'r');
                        if ($readFp === false) {
                            @unlink($tmpFile);
                            throw new \RuntimeException("No se pudo abrir temporal para lectura {$tmpFile}");
                        }

                        $putResult = $disk->put($chunkPath, $readFp);

                        // cerrar y eliminar temporal
                        fclose($readFp);
                        @unlink($tmpFile);

                        if ($putResult === false) {
                            throw new \RuntimeException("put devolvió false para {$chunkPath}");
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("CreateChunksJob: error escribiendo chunk {$chunkId} a disk: {$e->getMessage()}");
                    if (is_resource($currentTemp)) {
                        @fclose($currentTemp);
                    }
                    $currentTemp = null;
                    return null;
                }
            } catch (\Throwable $e) {
                Log::error("CreateChunksJob: error escribiendo chunk {$chunkId} a disk: {$e->getMessage()}");
                fclose($currentTemp);
                $currentTemp = null;
                return null;
            }

            // Register chunk metadata in Redis
            $chunkKey = $chunksPrefix . $chunkId;
            $redis->hset($chunkKey, 'chunk_id', $chunkId);
            $redis->hset($chunkKey, 'chunk_path', $chunkPath);
            $redis->hset($chunkKey, 'rows_count', $currentRowCount);
            $redis->hset($chunkKey, 'status', 'pending');
            $redis->hset($chunkKey, 'created_at', now()->toDateTimeString());

            // increment total processed counter
            $redis->hincrby($metaKey, 'chunks_created', 1);
            $redis->hincrby($metaKey, 'rows_processed', $currentRowCount);

            // liberar temp stream
            fclose($currentTemp);
            $currentTemp = null;

            // reset counter
            $rowsProcessedTotal += $currentRowCount;
            $currentRowCount = 0;

            // Dispatch ProcessChunkJob (en cola por defecto)
            try {
                Bus::dispatch(new ProcessChunkJob($this->batchId, $chunkId, $chunkPath, $this->disk));
            } catch (\Throwable $e) {
                Log::error("CreateChunksJob: error encolando ProcessChunkJob chunkId={$chunkId}: {$e->getMessage()}");
            }

            Log::info("CreateChunksJob: chunk creado chunkId={$chunkId} rows={$currentRowCount} path={$chunkPath}");

            return $chunkId;
        };

        // inicializar metadata en Redis
        $redis->hset($metaKey, 'file_path', $this->filePath);
        $redis->hset($metaKey, 'status', 'chunking');
        $redis->hset($metaKey, 'chunks_created', 0);
        $redis->hset($metaKey, 'rows_processed', 0);
        $redis->hset($metaKey, 'started_at', now()->toDateTimeString());

        // Iterar cada línea y escribir en un temp stream por chunk
        while (($line = fgets($stream)) !== false) {
            // Si no hay temp stream actual, crear uno y escribir header
            if ($currentTemp === null) {
                $currentTemp = fopen('php://temp', 'w+');
                // write header + newline
                fwrite($currentTemp, $headerLine . PHP_EOL);
            }

            // Escribir la línea en temp
            fwrite($currentTemp, $line);
            $currentRowCount++;
            $rowsProcessedTotal++;

            // Si alcanzamos chunkSize, flush
            if ($currentRowCount >= $this->chunkSize) {
                $flushChunk();
                // Emitir progreso parcial
                $chunksCreatedSoFar = (int) $redis->hget($metaKey, 'chunks_created');
                event(new ImportProgressEvent($this->batchId, $rowsProcessedTotal, "Chunks creados: {$chunksCreatedSoFar}", $rowsProcessedTotal, 'processing', 'CSV'));
            }
        }

        // flush any remaining rows
        if ($currentTemp !== null && $currentRowCount > 0) {
            $flushChunk();
        }

        // close input stream
        fclose($stream);

        // finalize meta
        $totalChunks = (int) $redis->hget($metaKey, 'chunks_created');
        $redis->hset($metaKey, 'total_chunks', $totalChunks);
        $redis->hset($metaKey, 'status', 'chunks_created');
        $redis->hset($metaKey, 'completed_at', now()->toDateTimeString());
        $redis->hset($metaKey, 'rows_total_estimate', $rowsProcessedTotal);

        // Emitir evento final de chunking
        event(new ImportProgressEvent($this->batchId, $rowsProcessedTotal, "Chunking completo: {$totalChunks} chunks", $rowsProcessedTotal, 'chunks_created', 'CSV'));

        Log::info("CreateChunksJob: terminado batch={$this->batchId} totalChunks={$totalChunks} totalRows={$rowsProcessedTotal}");
    }
}
