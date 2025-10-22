<?php

namespace App\Jobs\Rips\ImportCsv;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Models\ProcessBatch;
use App\Helpers\Constants;
use App\Helpers\Rips\CsvValidator;
use App\Helpers\Rips\ErrorCodes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * ValidateStructureJob
 *
 * Job encargado de orquestar la validación de estructura del CSV.
 * - Adquiere lock
 * - Obtiene metadata (file_path)
 * - Llama a CsvValidator::validateAll(...) para obtener el resultado
 * - Persiste errores usando ErrorCollector y actualiza el ProcessBatch
 *
 * Nota: CsvValidator realiza solo validación pura (no persiste).
 */
class ValidateStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    protected int $lockTtl = 30;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle()
    {

            Log::info("bbbbb");

        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando validación CSV', 0, 'active', 'CSV'));


        $lockKey = "validate_structure_lock:{$this->batchId}";
        $lock = Cache::lock($lockKey, $this->lockTtl);

        if (! $lock->get()) {
            Log::info("ValidateStructureJob: lock activo, saliendo. batchId={$this->batchId}");
            return;
        }

        $batch = null;
        $redis = Redis::connection('redis_6380');
        $redisKey = "batch:{$this->batchId}:metadata";

        try {
            // 1) Obtener ProcessBatch
            $batch = ProcessBatch::find($this->batchId);
            if (! $batch) {
                $msg = ErrorCodes::getMessage('RIP_CSV_014') ?? 'No se encontró registro del batch en base de datos.';
                Log::warning("ValidateStructureJob: {$msg} batchId={$this->batchId}");

                // registrar en Redis (no podemos incrementar error_count en BD porque no existe)
                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    $msg,
                    ErrorCodes::RIP_CSV_014['code'] ?? 'RIP_CSV_014',
                    null,
                    null
                );

                return;
            }

            // 2) Obtener file_path (Redis preferido, fallback a metadata en BD)
            $filePath = $redis->hget($redisKey, 'file_path');
            if (empty($filePath)) {
                $batchMeta = $batch->metadata ? json_decode($batch->metadata, true) : [];
                $filePath = $batchMeta['file_path'] ?? null;
            }

            if (empty($filePath)) {
                $msg = ErrorCodes::getMessage('RIP_CSV_011') ?? 'file_path no encontrado en Redis ni en BD.';
                // Log::error("ValidateStructureJob: {$msg} batchId={$this->batchId}");

                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    $msg,
                    ErrorCodes::RIP_CSV_011['code'] ?? 'RIP_CSV_011',
                    null,
                    null
                );


                $batch->update([
                    'error_count' => DB::raw('error_count + 1'),
                    'status' => 'failed',
                ]);

                event(new ImportProgressEvent($this->batchId, 0, 'Validación CSV fallida', ErrorCollector::countErrors($this->batchId), 'failed', 'CSV'));
                return;
            }

            // 3) Verificar existencia del archivo en el disco correcto
            $diskName = Constants::DISK_FILES;
            Log::info("ValidateStructureJob: usando disk '{$diskName}' para batch {$this->batchId}");

            $disk = Storage::disk($diskName);
            if (! $disk->exists($filePath)) {
                $msg = ErrorCodes::getMessage('RIP_CSV_012', $filePath) ?? "Archivo no existe en storage: {$filePath}";
                Log::error("ValidateStructureJob: {$msg} batchId={$this->batchId}");

                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    $msg,
                    ErrorCodes::RIP_CSV_012['code'] ?? 'RIP_CSV_012',
                    $filePath,
                    null
                );

                $batch->update([
                    'error_count' => DB::raw('error_count + 1'),
                    'status' => 'failed',
                ]);

                event(new ImportProgressEvent($this->batchId, 0, 'Validación CSV fallida', ErrorCollector::countErrors($this->batchId), 'failed', 'CSV'));

                return;
            }

            // 4) Definir encabezados esperados (puedes mover esto a config o resolver por tipo)
            $expectedHeaders = [
                'num_factura',
                'id_usuario',
                'num_identificacion',
                'id_servicio',
                'servicio',
                'campo',
                'valor',
            ];

            // 5) Delegar validación pura a CsvValidator
            $validation = CsvValidator::validateAll($this->batchId, $expectedHeaders);

            // 6) Si CsvValidator reporta errores, persistir cada uno en ErrorCollector y actualizar batch
            if (! empty($validation['errors'])) {
                $errorsCount = 0;

                foreach ($validation['errors'] as $err) {
                    $code = $err['code'] ?? (ErrorCodes::RIP_CSV_010['code'] ?? 'RIP_CSV_010');
                    $message = $err['message'] ?? ($err['details'] ?? json_encode($err));
                    ErrorCollector::addError(
                        $this->batchId,
                        0,
                        null,
                        $message,
                        $code,
                        isset($err['details']) ? json_encode($err['details'], JSON_UNESCAPED_UNICODE) : null,
                        json_encode($validation['detected_headers'] ?? [], JSON_UNESCAPED_UNICODE)
                    );

                    $errorsCount++;
                }

                // Actualizar error_count y status en UNA sola consulta atómica
                if ($errorsCount > 0) {
                    $batch->update([
                        'error_count' => DB::raw("error_count + {$errorsCount}"),
                        'status' => 'failed_structure', // o 'failed' según tu flujo
                    ]);

                    event(new ImportProgressEvent($this->batchId, 0, 'Validación CSV fallida', ErrorCollector::countErrors($this->batchId), 'failed', 'CSV'));

                    Log::warning("ValidateStructureJob: validación estructura fallida. batchId={$this->batchId} (errors={$errorsCount})");
                    return;
                } else {
                    // No hubo errores — simplemente continúa el flujo normal
                    Log::info("ValidateStructureJob: sin errores estructurales. batchId={$this->batchId}");
                }
            }

            // 7) Si ok -> persistir detected_headers y marcar validated
            if (! empty($validation['detected_headers'])) {
                // Guardar solo una clave JSON simple en Redis (evitamos hmset con arrays)
                $redis->hset($redisKey, 'detected_headers', json_encode($validation['detected_headers'], JSON_UNESCAPED_UNICODE));
                $batch->update([
                    'status' => 'active',
                    'metadata' => json_encode(array_merge(json_decode($batch->metadata ?? '{}', true) ?: [], ['detected_headers' => $validation['detected_headers']])),
                ]);
            } else {
                // No headers detectados (raro), marcar como failed por seguridad
                $msg = ErrorCodes::getMessage('RIP_CSV_005') ?? 'Delimitador del CSV no reconocido o inconsistente.';
                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    $msg,
                    ErrorCodes::RIP_CSV_005['code'] ?? 'RIP_CSV_005',
                    null,
                    null
                );
                $batch->update([
                    'error_count' => DB::raw('error_count + 1'),
                    'status' => 'failed',
                ]);

                event(new ImportProgressEvent($this->batchId, 0, 'Validación CSV fallida', ErrorCollector::countErrors($this->batchId), 'failed', 'CSV'));

                return;
            }

            event(new ImportProgressEvent($this->batchId, 0, 'Validación CSV OK', ErrorCollector::countErrors($this->batchId), 'completed', 'CSV'));



            Log::info("cccccccccc");



            $cantChunks = 5;
            Bus::dispatch(new CreateChunksJob($this->batchId, $filePath, $diskName, $cantChunks));








            Log::info("ValidateStructureJob: estructura validada OK. batchId={$this->batchId}");
        } catch (\Throwable $e) {
            $msg = sprintf("ValidateStructureJob excepción: %s", $e->getMessage());
            Log::error($msg, ['exception' => $e]);

            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                $e->getMessage(),
                ErrorCodes::RIP_CSV_010['code'] ?? 'RIP_CSV_010',
                null,
                null
            );

            if ($batch instanceof ProcessBatch) {
                $batch->increment('error_count');
                $batch->update(['status' => 'failed']);
            }
        } finally {
            try {
                // Consolidar y persistir errores en BD (si tu ErrorCollector implementa esto)
                $countErrors = ErrorCollector::countErrors($this->batchId);
                $status = $countErrors > 0 ? 'failed' : 'completed';
                ErrorCollector::saveErrorsToDatabase($this->batchId, $status);
            } catch (\Throwable $e) {
                Log::debug("ValidateStructureJob: error guardando errores a BD: {$e->getMessage()}");
            }

            // Liberar lock
            if (isset($lock) && method_exists($lock, 'release')) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    Log::debug("ValidateStructureJob: error liberando lock: {$e->getMessage()}");
                }
            }
        }
    }
}
