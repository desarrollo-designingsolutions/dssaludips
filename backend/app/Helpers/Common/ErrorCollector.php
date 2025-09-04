<?php

namespace App\Helpers\Common;

use App\Models\ProcessBatch;
use App\Models\ProcessBatchesError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ErrorCollector
{
    /**
     * Agrega un error a la lista en Redis.
     */
    public static function addError(
        string $batchId,
        int $rowNumber,
        ?string $columnName,
        string $errorMessage,
        string $errorType,
        $errorValue,
        ?string $originalData
    ): void {
        $error = [
            'id' => Str::uuid(),
            'batch_id' => $batchId,
            'row_number' => $rowNumber,
            'column_name' => $columnName,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'error_value' => is_null($errorValue) ? null : strval($errorValue),
            'original_data' => $originalData ?: null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        Redis::connection('redis_6380')->rpush("import_errors:{$batchId}", json_encode($error));
        Redis::connection('redis_6380')->expire("import_errors:{$batchId}", 3600);
    }

    /**
     * Devuelve todos los errores recolectados.
     */
    public static function getErrors(string $batchId): array
    {
        $rawErrors = Redis::connection('redis_6380')->lrange("import_errors:{$batchId}", 0, -1);
        $errors = [];
        foreach ($rawErrors as $errorJson) {
            $errors[] = json_decode($errorJson, true);
        }

        return $errors;
    }

    /**
     * Limpia la lista de errores en Redis.
     */
    public static function clear(string $batchId): void
    {
        Redis::connection('redis_6380')->del("import_errors:{$batchId}");
    }

    /**
     * Guarda los errores en la base de datos y actualiza ProcessBatch.
     */
    public static function saveErrorsToDatabase(string $batchId, string $status = 'failed'): void
    {
        $redis = Redis::connection('redis_6380');
        $errors = self::getErrors($batchId);
        $metadata = $redis->hgetall("batch:{$batchId}:metadata");
        $metadata['completed_at'] = now()->toDateTimeString();

        if (empty($errors)) {
            Log::info("No errors to save for batch {$batchId}");
            ProcessBatch::where('batch_id', $batchId)->update([
                'error_count' => 0,
                'status' => 'completed',
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);
            $redis->hmset("batch:{$batchId}:metadata", $metadata);
            $redis->hmset("rip_batch:{$batchId}", ['status' => 'completed']);
            self::clear($batchId);
            return;
        }

        $errorRecords = array_map(function ($error) use ($batchId) {
            return [
                'id' => Str::uuid(),
                'batch_id' => $batchId,
                'row_number' => $error['row_number'] ?? null,
                'column_name' => $error['column_name'] ?? null,
                'error_message' => $error['error_message'],
                'error_type' => $error['error_type'] ?? 'R',
                'error_value' => $error['error_value'] ?? null,
                'original_data' => isset($error['original_data']) ? json_encode(['data' => $error['original_data']]) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $errors);

        ProcessBatchesError::insert($errorRecords);

        ProcessBatch::where('batch_id', $batchId)->update([
            'error_count' => count($errors),
            'status' => $status,
            'metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]);

        $redis->hmset("batch:{$batchId}:metadata", $metadata);
        $redis->hmset("rip_batch:{$batchId}", ['status' => $status]);

        Log::info("Saved " . count($errors) . " errors to process_batches_errors for batch {$batchId}");

        self::clear($batchId);
    }
}
