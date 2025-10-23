<?php

namespace App\Jobs\Rips\ImportCsv;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
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
        $chunksPrefix = "import:batch:{$this->batchId}:chunk:";
        $chunksFolder = "temp/rips/{$this->batchId}/chunks";

        Log::info("CreateChunksJob: iniciando chunking batch={$this->batchId} file={$this->filePath} disk={$this->disk} chunkSize={$this->chunkSize}");

        // Emitir evento INICIAL
        event(new ImportProgressEvent(
            $this->batchId,
            0,
            'CHUNKING',
            ErrorCollector::countErrors($this->batchId),
            'processing',
            'Iniciando división del archivo en chunks',
        ));
        sleep(5);

        // Borrar meta previa si existe
        try {
            $redis->del($metaKey);
        } catch (\Throwable $e) {
            Log::warning("CreateChunksJob: no se pudo limpiar meta redis previo: {$e->getMessage()}");
        }

        // Asegurar carpeta existe
        $disk = Storage::disk($this->disk);

        // PRIMERO: Contar total de filas del archivo para el progreso
        $streamForCounting = $disk->readStream($this->filePath);
        if ($streamForCounting === false) {
            Log::error("CreateChunksJob: no se pudo abrir archivo para contar: {$this->filePath}");
            $redis->hset($metaKey, 'status', 'chunking_failed');

            event(new ImportProgressEvent(
                $this->batchId,
                0,
                'Error: No se pudo abrir el archivo',
                ErrorCollector::countErrors($this->batchId),
                'failed',
                'CHUNKING'
            ));
            return;
        }

        // Contar total de filas (incluyendo header)
        $totalRowsWithHeader = 0;
        while (fgets($streamForCounting) !== false) {
            $totalRowsWithHeader++;
        }
        fclose($streamForCounting);

        // Calcular filas de datos (excluyendo header)
        $totalDataRows = max(0, $totalRowsWithHeader - 1);

        // Guardar total_rows en Redis para el progreso
        $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
        $metadata['total_rows'] = $totalDataRows;
        $redis->hmset("batch:{$this->batchId}:metadata", $metadata);

        // CONTADORES SEPARADOS
        $redis->hset($metaKey, 'total_rows', $totalDataRows);
        $redis->hset($metaKey, 'chunking_rows_processed', 0);
        $redis->hset($metaKey, 'file_path', $this->filePath);
        $redis->hset($metaKey, 'status', 'chunking');
        $redis->hset($metaKey, 'chunks_created', 0);
        $redis->hset($metaKey, 'chunks_completed', 0);
        $redis->hset($metaKey, 'started_at', now()->toDateTimeString());

        Log::info("CreateChunksJob: Total de filas de datos a procesar: {$totalDataRows}");

        // Emitir evento con el total correcto
        event(new ImportProgressEvent(
            $this->batchId,
            0,
            'CHUNKING',
            ErrorCollector::countErrors($this->batchId),
            'processing',
            "Preparando archivo ({$totalDataRows} filas)",
        ));

        // Ahora abrir stream para procesar
        $stream = $disk->readStream($this->filePath);
        if ($stream === false) {
            Log::error("CreateChunksJob: no se pudo abrir archivo para procesar: {$this->filePath} en disk {$this->disk}");
            $redis->hset($metaKey, 'status', 'chunking_failed');

            event(new ImportProgressEvent(
                $this->batchId,
                0,
                'Error: No se pudo procesar el archivo',
                ErrorCollector::countErrors($this->batchId),
                'failed',
                'CHUNKING'
            ));
            return;
        }

        // Leer header (primera línea)
        $headerLine = fgets($stream);
        if ($headerLine === false) {
            fclose($stream);
            Log::error("CreateChunksJob: archivo vacío o sin header: {$this->filePath}");
            $redis->hset($metaKey, 'status', 'chunking_failed');

            event(new ImportProgressEvent(
                $this->batchId,
                0,
                'CHUNKING',
                ErrorCollector::countErrors($this->batchId),
                'failed',
                'Error: Archivo vacío o sin encabezados',
            ));
            return;
        }
        $headerLine = preg_replace('/\r\n|\r|\n$/', '', $headerLine);

        $chunkIndex = 0;
        $currentRowCount = 0;
        $currentTemp = null;
        $rowsProcessedTotal = 0;

        // Array para almacenar todos los chunks creados
        $allChunks = [];

        // función local para flush del temp stream - SOLO CREAR CHUNK, NO DESPACHAR
        $flushChunk = function () use (
            &$chunkIndex,
            &$currentTemp,
            &$currentRowCount,
            &$rowsProcessedTotal,
            &$allChunks,
            $disk,
            $chunksFolder,
            $redis,
            $metaKey,
            $chunksPrefix,
            $totalDataRows,
            $headerLine
        ) {
            if (! $currentTemp) return null;

            rewind($currentTemp);

            $chunkIndex++;
            $chunkId = "c-{$chunkIndex}";
            $chunkPath = "{$chunksFolder}/chunk_{$chunkId}.csv";

            // 1️⃣ PRIMERO: Actualizar contadores en Redis
            $redis->hincrby($metaKey, 'chunks_created', 1);
            $redis->hincrby($metaKey, 'chunking_rows_processed', $currentRowCount);

            // 2️⃣ SEGUNDO: Emitir evento de progreso AL FRONTEND (solo progreso)
            $currentProcessed = (int) $redis->hget($metaKey, 'chunking_rows_processed');
            $totalChunksCreated = (int) $redis->hget($metaKey, 'chunks_created');
            $progressPercentage = $totalDataRows > 0 ? round(($currentProcessed / $totalDataRows) * 100, 2) : 0;

            event(new ImportProgressEvent(
                $this->batchId,
                $currentProcessed,
                'CHUNKING',
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Creando chunks: {$totalChunksCreated} creados, {$currentProcessed}/{$totalDataRows} filas procesadas",
            ));

            Log::info("CreateChunksJob: Creando chunk {$chunkId} - {$currentRowCount} filas");

            // 3️⃣ TERCERO: Guardar el chunk físicamente
            try {
                if (method_exists($disk, 'writeStream') && is_callable([$disk, 'writeStream'])) {
                    rewind($currentTemp);
                    $success = $disk->writeStream($chunkPath, $currentTemp);
                    if ($success === false) {
                        throw new \RuntimeException("writeStream devolvió false para {$chunkPath}");
                    }
                } else {
                    // Fallback para guardar el chunk
                    $tmpFolder = storage_path('app/tmp/rips/' . $this->batchId);
                    if (! is_dir($tmpFolder)) {
                        @mkdir($tmpFolder, 0755, true);
                    }

                    $tmpFile = $tmpFolder . '/chunk_' . $chunkId . '_' . uniqid() . '.tmp';
                    $tmpFp = fopen($tmpFile, 'w+');
                    if ($tmpFp === false) {
                        throw new \RuntimeException("No se pudo crear temporal {$tmpFile}");
                    }

                    rewind($currentTemp);
                    stream_copy_to_stream($currentTemp, $tmpFp);
                    fflush($tmpFp);
                    fclose($tmpFp);

                    $readFp = fopen($tmpFile, 'r');
                    if ($readFp === false) {
                        @unlink($tmpFile);
                        throw new \RuntimeException("No se pudo abrir temporal para lectura {$tmpFile}");
                    }

                    $putResult = $disk->put($chunkPath, $readFp);
                    fclose($readFp);
                    @unlink($tmpFile);

                    if ($putResult === false) {
                        throw new \RuntimeException("put devolvió false para {$chunkPath}");
                    }
                }
            } catch (\Throwable $e) {
                Log::error("CreateChunksJob: error guardando chunk {$chunkId}: {$e->getMessage()}");
                if (is_resource($currentTemp)) {
                    @fclose($currentTemp);
                }
                $currentTemp = null;
                return null;
            }

            // 4️⃣ CUARTO: Registrar metadata del chunk en Redis
            $chunkKey = $chunksPrefix . $chunkId;
            $redis->hset($chunkKey, 'chunk_id', $chunkId);
            $redis->hset($chunkKey, 'chunk_path', $chunkPath);
            $redis->hset($chunkKey, 'rows_count', $currentRowCount);
            $redis->hset($chunkKey, 'status', 'pending');
            $redis->hset($chunkKey, 'created_at', now()->toDateTimeString());

            // 5️⃣ QUINTO: Guardar información del chunk para despachar después
            $allChunks[] = [
                'id' => $chunkId,
                'path' => $chunkPath,
                'rows' => $currentRowCount,
                'key' => $chunkKey
            ];

            // 6️⃣ Limpiar recursos
            fclose($currentTemp);
            $currentTemp = null;
            $currentRowCount = 0;

            return $chunkId;
        };

        // Inicializar metadata en Redis
        $redis->hset($metaKey, 'file_path', $this->filePath);
        $redis->hset($metaKey, 'status', 'chunking');
        $redis->hset($metaKey, 'chunks_created', 0);
        $redis->hset($metaKey, 'chunking_rows_processed', 0);
        $redis->hset($metaKey, 'started_at', now()->toDateTimeString());

        // Iterar cada línea y escribir en un temp stream por chunk
        $isFirstLine = true;
        while (($line = fgets($stream)) !== false) {
            // Saltar la primera línea (header)
            if ($isFirstLine) {
                $isFirstLine = false;
                continue;
            }

            // Si no hay temp stream actual, crear uno y escribir header
            if ($currentTemp === null) {
                $currentTemp = fopen('php://temp', 'w+');
                fwrite($currentTemp, $headerLine . PHP_EOL);
            }

            // Escribir la línea en temp
            fwrite($currentTemp, $line);
            $currentRowCount++;
            $rowsProcessedTotal++;

            // Si alcanzamos chunkSize, flush
            if ($currentRowCount >= $this->chunkSize) {
                $flushChunk();
            }
        }

        // flush any remaining rows
        if ($currentTemp !== null && $currentRowCount > 0) {
            $flushChunk();
        }

        // close input stream
        fclose($stream);

        // ========== FASE 2: TODOS LOS CHUNKS CREADOS - INFORMAR ==========

        $totalChunks = count($allChunks);
        $totalRowsProcessed = (int) $redis->hget($metaKey, 'chunking_rows_processed');

        // Emitir evento de chunking completado
        event(new ImportProgressEvent(
            $this->batchId,
            $totalRowsProcessed,
            'CHUNKING_COMPLETED',
            ErrorCollector::countErrors($this->batchId),
            'active',
            "División completada: {$totalChunks} chunks creados",
        ));

        // Guardar cantidad de chunks en metadata
        $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
        $metadata['total_chunks'] = $totalChunks;
        $redis->hmset("batch:{$this->batchId}:metadata", $metadata);

        Log::info("CreateChunksJob: Chunking completado - {$totalChunks} chunks creados, {$totalRowsProcessed} filas procesadas");

        // ========== FASE 3: DESPACHAR JOBS PARA PROCESAR CHUNKS ==========

        // REINICIAR CONTADORES PARA PROCESAMIENTO
        $redis->hset($metaKey, 'chunks_completed', 0);

        event(new ImportProgressEvent(
            $this->batchId,
            0,
            'PROCESSING_STARTING',
            ErrorCollector::countErrors($this->batchId),
            'active',
            "Iniciando procesamiento de {$totalChunks} chunks...",
        ));

         sleep(5);
        Log::info("CreateChunksJob: Despachando {$totalChunks} jobs ProcessChunkJob");

        // Despachar todos los jobs de procesamiento
        $jobsDespachados = 0;
        foreach ($allChunks as $chunk) {
            try {
                Bus::dispatch(new ProcessChunkJob(
                    $this->batchId,
                    $chunk['id'],
                    $chunk['path'],
                    $this->disk
                ));
                $jobsDespachados++;

                // Actualizar estado del chunk en Redis
                $redis->hset($chunk['key'], 'job_dispatched', true);
                $redis->hset($chunk['key'], 'dispatched_at', now()->toDateTimeString());
            } catch (\Throwable $e) {
                Log::error("CreateChunksJob: Error despachando job para chunk {$chunk['id']}: {$e->getMessage()}");
                $redis->hset($chunk['key'], 'status', 'dispatch_failed');
                $redis->hset($chunk['key'], 'error', $e->getMessage());
            }
        }

        // ========== FASE 4: INFORMAR PROCESAMIENTO INICIADO ==========

        $redis->hset($metaKey, 'total_chunks', $totalChunks);
        $redis->hset($metaKey, 'status', 'chunks_created');
        $redis->hset($metaKey, 'chunks_dispatched', $jobsDespachados);
        $redis->hset($metaKey, 'completed_at', now()->toDateTimeString());
        $redis->hset($metaKey, 'rows_total_estimate', $totalRowsProcessed);

        event(new ImportProgressEvent(
            $this->batchId,
            0,
            'PROCESSING_STARTED',
            ErrorCollector::countErrors($this->batchId),
            'active',
            "Procesamiento iniciado: {$jobsDespachados}/{$totalChunks} chunks en cola",
        ));

        Log::info("CreateChunksJob: terminado batch={$this->batchId} totalChunks={$totalChunks} jobsDespachados={$jobsDespachados} totalRowsProcessed={$totalRowsProcessed}");
    }
}
