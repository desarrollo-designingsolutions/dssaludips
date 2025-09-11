<?php

namespace App\Jobs\ProcessBatch;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ProcessBatchesError;
use App\Models\User;
use App\Notifications\BellNotification;
use Carbon\Carbon;

class ExcelReportData implements ShouldQueue
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
        $processKey = null;

        try {
            Log::info("Iniciando generación XLSX para batch {$this->customBatchId}.");

            // Cuenta total de filas en la tabla de errores (por batch)
            $totalCount = ProcessBatchesError::where('batch_id', $this->customBatchId)->count();
            if ($totalCount === 0) {
                Log::info("No hay registros para el batch {$this->customBatchId}.");

                if ($user = User::find($this->userId)) {
                    $user->notify(new BellNotification([
                        'title'    => 'Reporte de Datos',
                        'subtitle' => 'No se encontraron registros para el batch especificado.',
                        'type'     => 'info',
                    ]));
                }
                return;
            }

            $chunkSize = 500;
            $numChunks = (int) ceil($totalCount / $chunkSize);

            // Clave única para el proceso en Redis
            $processKey = 'data_report:' . $this->customBatchId . ':' . uniqid();

            // Nombre del archivo final
            $fileName = 'report_data_' . Carbon::now()->format('Ymd_His') . '.xlsx';

            // Metadata del proceso
            Redis::hmset($processKey, [
                'user_id'        => $this->userId,
                'file_name'      => $fileName,
                'started_at'     => Carbon::now()->toIso8601String(),
                'total_records'  => $totalCount,
                'processed'      => 0,
                'batch_id'       => $this->customBatchId,
            ]);

            Log::info("Batch {$this->customBatchId}: {$totalCount} filas en {$numChunks} chunks.");

            // Preparar jobs por chunk
            $jobs = [];
            for ($i = 0; $i < $numChunks; $i++) {
                $offset = $i * $chunkSize;
                $jobs[] = new ProcessDataChunk($this->customBatchId, $processKey, $offset, $chunkSize, $this->userId);
            }

            // Job final
            $finalJob = new GenerateErrorExcel($processKey, $this->userId);

            Bus::batch($jobs)
                ->onQueue('download_files') // ¡Importante!
                ->then(function () use ($finalJob) {
                    dispatch($finalJob->onQueue('download_files'));
                })
                ->dispatch();

        } catch (\Throwable $e) {
            Log::error("Error en ExcelReportData (batch {$this->customBatchId}): {$e->getMessage()}");

            if ($user = User::find($this->userId)) {
                $user->notify(new BellNotification([
                    'title'    => 'Error al generar reporte',
                    'subtitle' => $e->getMessage(),
                    'type'     => 'error',
                ]));
            }

            if ($processKey) {
                $rowsKey = $processKey . ':rows';
                $seenKey = $processKey . ':seen_rows';
                Redis::del($processKey, $rowsKey, $seenKey);
            }

            throw $e;
        }
    }
}
