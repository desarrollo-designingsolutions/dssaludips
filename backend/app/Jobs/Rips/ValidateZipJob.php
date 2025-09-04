<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\ZipValidator;
use App\Models\ProcessBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ZipArchive;

class ValidateZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $batchId;
    public $userId;
    public $companyId;

    public function __construct(string $filePath, string $batchId, string $userId, string $companyId)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->userId = $userId;
        $this->companyId = $companyId;
        $this->onQueue('import_rips');
    }

    public function handle(): void
    {
        $redis = Redis::connection('redis_6380');

        ErrorCollector::clear($this->batchId);
        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando validación ZIP', 0, 'active', 'ZIP'));

        try {
            $validationResult = ZipValidator::validateAll($this->filePath, $this->batchId);

            $zip = new ZipArchive();
            $numFiles = 0;
            if ($zip->open($this->filePath) === true) {
                $numFiles = $zip->numFiles;
                $zip->close();
            }

            $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
            $metadata['total_rows'] = $numFiles;
            $redis->hmset("batch:{$this->batchId}:metadata", $metadata);
            ProcessBatch::where('batch_id', $this->batchId)->update([
                'total_records' => $numFiles,
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);

            $errors = ErrorCollector::getErrors($this->batchId);
            $errorCount = count($errors);

            if ($validationResult['isCritical']) {
                Log::error("Validación ZIP falló crítica para batch {$this->batchId}", ['errors' => $errors]);
                event(new ImportProgressEvent($this->batchId, $numFiles, 'Validación ZIP fallida', $errorCount, 'failed', 'ZIP'));
                if (file_exists($this->filePath)) {
                    @unlink($this->filePath);
                }
                $this->fail(new \Exception('Validación ZIP crítica fallida'));
                return;
            }

            Log::info("Validación ZIP completada para batch {$this->batchId}", ['error_count' => $errorCount]);
            $status = $errorCount > 0 ? 'failed' : 'completed';
            event(new ImportProgressEvent($this->batchId, $numFiles, 'Validación ZIP completada', $errorCount, $status, 'ZIP'));

            $redis->hmset("rip_batch:{$this->batchId}", [
                'status' => 'zip_validated',
            ]);
            $redis->expire("rip_batch:{$this->batchId}", 86400);
        } catch (\Exception $e) {

            Log::error("Excepción en validación ZIP para batch {$this->batchId}: {$e->getMessage()}");
            $errors = ErrorCollector::getErrors($this->batchId);
            $errorCount = count($errors);
            event(new ImportProgressEvent($this->batchId, 0, 'Error inesperado en ZIP', $errorCount, 'failed', 'ZIP'));
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
            $this->fail($e);
        }
    }
}
