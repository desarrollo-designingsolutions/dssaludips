<?php

namespace App\Jobs\ProcessBatch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Bus;
use App\Models\ProcessBatchesError;
use App\Jobs\ProcessBatch\ProcessErrorChunk;
use App\Jobs\ProcessBatch\GenerateErrorCsv;
use App\Models\User;
use App\Notifications\BellNotification;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CsvReportErrors implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $customBatchId;
    protected string $userId;

    public function __construct(string $customBatchId, string $userId)
    {
        $this->customBatchId = $customBatchId;
        $this->userId = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        try {
            // Contar total de errores
            $totalCount = ProcessBatchesError::where('batch_id', $this->customBatchId)->count();

            if ($totalCount === 0) {
                Log::info("No se encontraron errores para el batch {$this->customBatchId}.");
                $user = User::find($this->userId);
                if ($user) {
                    $user->notify(new BellNotification([
                        'title' => 'Reporte de Errores',
                        'subtitle' => 'No se encontraron errores para el batch especificado.',
                        'type' => 'info'
                    ]));
                }
                return;
            }

            $chunkSize = 500;
            $numChunks = ceil($totalCount / $chunkSize);

            // Generar clave única para el proceso en Redis
            $processKey = 'error_report:' . $this->customBatchId . ':' . uniqid();

            // Generar nombre único para el archivo
            $fileName = 'errors_report_' . Carbon::now()->format('Ymd_His') . '.csv';

            // Guardar metadata en Redis
            Redis::hmset($processKey, [
                'user_id' => $this->userId,
                'file_name' => $fileName,
                'started_at' => Carbon::now()->toIso8601String(),
                'total_records' => $totalCount,
                'processed' => 0
            ]);

            Log::info("Iniciando generación de reporte CSV de errores para batch {$this->customBatchId} con {$totalCount} errores en {$numChunks} chunks.");

            // Preparar jobs para chunks
            $jobs = [];
            for ($i = 0; $i < $numChunks; $i++) {
                $offset = $i * $chunkSize;
                $jobs[] = new ProcessErrorChunk($this->customBatchId, $processKey, $offset, $chunkSize, $this->userId);
            }

            // Job final para generar CSV
            $finalJob = new GenerateErrorCsv($processKey, $this->userId);

            // Despachar batch de chunks, y al finalizar despachar el job final
            Bus::batch($jobs)
                ->then(function () use ($finalJob) {
                    dispatch($finalJob);
                })
                ->dispatch();
        } catch (\Exception $e) {
            Log::error("Error en CsvReportErrors para batch {$this->customBatchId}: {$e->getMessage()}");
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => 'Error al generar reporte de errores',
                    'subtitle' => $e->getMessage(),
                    'type' => 'error'
                ]));
            }
            // Limpiar Redis en caso de error
            $rowsKey = $processKey . ':rows';
            Redis::del($processKey, $rowsKey);
            throw $e; // Re-throw para marcar el job como fallido
        }
    }
}
