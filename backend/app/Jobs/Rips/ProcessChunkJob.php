<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\ACFileValidator;
use App\Helpers\Rips\AFFileValidator;
use App\Helpers\Rips\AHFileValidator;
use App\Helpers\Rips\AMFileValidator;
use App\Helpers\Rips\ANFileValidator;
use App\Helpers\Rips\APFileValidator;
use App\Helpers\Rips\ATFileValidator;
use App\Helpers\Rips\AUFileValidator;
use App\Helpers\Rips\CTFileValidator;
use App\Helpers\Rips\USFileValidator;
use App\Services\ProcessBatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $file_name;
    protected $chunk;
    protected $start_row;

    public function __construct(string $batchId, string $file_name, array $chunk, int $start_row)
    {
        $this->batchId = $batchId;
        $this->file_name = $file_name;
        $this->chunk = $chunk;
        $this->start_row = $start_row;
        // $this->onQueue('import_rips');
    }

    public function handle(): void
    {

        $processed = count($this->chunk);
        $end_row = $this->start_row + $processed - 1;

        try {
            $validators = [
                'CT' => CTFileValidator::class,
                'US' => USFileValidator::class,
                'AF' => AFFileValidator::class,
                'AC' => ACFileValidator::class,
                'AP' => APFileValidator::class,
                'AU' => AUFileValidator::class,
                'AH' => AHFileValidator::class,
                'AN' => ANFileValidator::class,
                'AM' => AMFileValidator::class,
                'AT' => ATFileValidator::class,
            ];

            $prefix = strtoupper(substr($this->file_name, 0, 2));
            if (!array_key_exists($prefix, $validators)) {
                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    null,
                    "Prefijo de archivo no válido: {$prefix}",
                    'FILE_PREFIX_INVALID',
                    $prefix,
                    $this->file_name
                );
                Log::error("Invalid file prefix {$prefix} for file {$this->file_name} in batch {$this->batchId}");
                $this->fail(new \Exception("Invalid file prefix: {$prefix}"));
                return;
            }


            // --- Validar filas del chunk ---
            // foreach ($this->chunk as $offset => $row) {
            //     $rowNumber = $this->start_row + $offset;
            //      $validators[$prefix]::validate($this->file_name, $row, $rowNumber, $this->batchId);
            // }

            // --- Actualizar processed_rows de manera atómica (sin transacción) ---
            $redis = Redis::connection('redis_6380');
            $redis->incrby("rip_batch:{$this->batchId}:processed_rows", $processed);
            $redis->expire("rip_batch:{$this->batchId}:processed_rows", 86400);

            // --- Obtener total_rows y processed_rows ---
            $processedRows = (int) $redis->get("rip_batch:{$this->batchId}:processed_rows");

            // --- Evento: Chunk procesado ---
            event(new ImportProgressEvent(
                $this->batchId,
                (string) $processedRows,
                $this->file_name,
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Filas {$this->start_row}-{$end_row} de {$this->file_name} procesadas.",
            ));


            if ($processedRows == (int)$redis->get("rip_batch:{$this->batchId}:total_rows")) {
                event(new ImportProgressEvent(
                    $this->batchId,
                    (string) $processedRows,
                    $this->file_name,
                    ErrorCollector::countErrors($this->batchId),
                    'active',
                    "Todos los archivos procesados.",
                ));
            }
        } catch (\Throwable $e) {
            Log::error("Error in ProcessChunkJob for batch {$this->batchId}, file {$this->file_name}: {$e->getMessage()}");
            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                "Error procesando chunk de {$this->file_name}: {$e->getMessage()}",
                'CHUNK_PROC_001',
                null,
                $this->file_name
            );
            $this->fail($e);
        }
    }
}
