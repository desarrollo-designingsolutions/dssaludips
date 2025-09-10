<?php

namespace App\Jobs\ProcessBatch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Models\ProcessBatchesError;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ProcessErrorChunk implements ShouldQueue
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
        $this->processKey = $processKey;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->userId = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        try {
            // Consultar chunk de errores
            $errors = ProcessBatchesError::where('batch_id', $this->customBatchId)
                ->skip($this->offset)
                ->take($this->limit)
                ->get();

            $rowsKey = $this->processKey . ':rows';

            foreach ($errors as $error) {
                // Convertir a fila CSV con los campos solicitados
                $row = implode(';', [
                    $error->row_number ?? '',
                    $error->column_name ?? '',
                    str_replace(';', ',', $error->error_message ?? ''), // Escapar ';' para no romper CSV
                    $error->error_type ?? '',
                    str_replace(';', ',', $error->error_value ?? '') // Escapar ';' para error_value
                ]);

                // Guardar fila en lista de Redis
                Redis::rpush($rowsKey, $row);
            }

            // Actualizar contador de procesados
            Redis::hincrby($this->processKey, 'processed', $errors->count());
            Log::info("Procesado chunk {$this->offset} para batch {$this->customBatchId}, {$errors->count()} errores procesados.");
        } catch (\Exception $e) {
            Log::error("Error en ProcessErrorChunk para batch {$this->customBatchId}, offset {$this->offset}: {$e->getMessage()}");
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => 'Error al procesar chunk de errores',
                    'subtitle' => $e->getMessage(),
                    'type' => 'error'
                ]));
            }
            throw $e; // Re-throw para marcar el job como fallido
        }
    }
}
