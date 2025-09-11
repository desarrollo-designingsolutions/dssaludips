<?php

namespace App\Jobs\ProcessBatch;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ProcessBatchesError;

class ProcessDataChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $customBatchId;
    protected string $processKey;
    protected int $offset;
    protected int $limit;
    protected string $userId;

    public function __construct(string $customBatchId, string $processKey, int $offset, int $limit, string $userId)
    {
        $this->customBatchId = $customBatchId;
        $this->processKey    = $processKey;
        $this->offset        = $offset;
        $this->limit         = $limit;
        $this->userId        = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        $rowsKey = $this->processKey . ':rows';
        $seenKey = $this->processKey . ':seen_rows';

        $rows = ProcessBatchesError::where('batch_id', $this->customBatchId)
            ->orderBy('id')
            ->skip($this->offset)
            ->take($this->limit)
            ->get(['id', 'row_number', 'original_data']); // usamos row_number + original_data

        foreach ($rows as $row) {
            try {
                // 1) Decodificar original_data (JSON)
                $payload = json_decode($row->original_data, true);
                if (!is_array($payload)) {
                    Log::warning("Row {$row->id}: original_data no es JSON.");
                    continue;
                }

                // 2) 'data' puede venir string JSON o array
                $inner = $payload['data'] ?? null;
                if (is_string($inner)) {
                    $decoded = json_decode($inner, true);
                } elseif (is_array($inner)) {
                    $decoded = $inner;
                } else {
                    $decoded = null;
                }

                if (!is_array($decoded)) {
                    Log::warning("Row {$row->id}: payload['data'] vacío/no JSON.");
                    continue;
                }

                // 3) Normalizar valores a string (genérico)
                foreach ($decoded as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $decoded[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    } elseif (is_bool($v)) {
                        $decoded[$k] = $v ? 'true' : 'false';
                    } elseif ($v === null) {
                        $decoded[$k] = '';
                    } else {
                        $decoded[$k] = (string) $v;
                    }
                }

                // 4) Excluir claves por ENV (por defecto company_id)
                foreach ($this->getExcludedKeys() as $badKey) {
                    unset($decoded[$badKey]);
                }

                // 5) Deduplicar por row_number (1 fila por row_number)
                $rowNumber = (string) ($row->row_number ?? '');
                if ($rowNumber !== '') {
                    $added = Redis::sadd($seenKey, $rowNumber); // atomic
                    if ($added !== 1) {
                        // ya procesado ese row_number ⇒ continuar con el siguiente registro
                        continue;
                    }
                }

                // (Opcional) incluirlo como columna:
                // $decoded['_row_number'] = $rowNumber;

                // 6) Empujar el objeto completo como JSON al list
                Redis::rpush($rowsKey, json_encode($decoded, JSON_UNESCAPED_UNICODE));

                // 7) Progreso
                Redis::hincrby($this->processKey, 'processed', 1);

            } catch (\Throwable $e) {
                Log::warning("Row {$row->id}: error parseando original_data: {$e->getMessage()}");
            }
        }
    }

    /**
     * Lee claves a excluir desde ENV: EXPORT_EXCLUDE_KEYS="company_id,foo,bar"
     */
    private function getExcludedKeys(): array
    {
        $raw = env('EXPORT_EXCLUDE_KEYS', 'company_id');
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
