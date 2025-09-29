<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Models\ProcessBatch;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Str;

class ValidateExcelJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $customBatchId;
    protected string $userId;
    protected string $selectedQueue;
    protected array $metadata;

    public function __construct(
        string $customBatchId,
        string $userId,
        array $metadata,
        string $selectedQueue,
    ) {
        $this->customBatchId   = $customBatchId;
        $this->userId    = $userId;
        $this->metadata   = $metadata;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $errors = false;
        event(new ImportProgressEvent(
            $this->customBatchId,
            0,
            "Iniciando validación Excel...",
            0,
            'active',
            "Leyendo archivo Excel...",
        ));

        try {
            if ($this->metadata['xlsCollection']->isEmpty()) {
                Log::Info("entra if", [$this->metadata['xlsCollection']]);

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    0,
                    'El archivo no cuenta con datos registrados',
                    1,
                    'failed',
                    'El archivo no contiene filas.'
                ));
                $errors = true;
            }

            $normalize = fn($s) => Str::of($s)->lower()->replace(' ', '')->toString();
            $headers = collect(array_keys($this->metadata['xlsCollection']->first()))->map($normalize);
            $missing = collect($this->metadata['required'])->diff($headers);
            if ($missing->isNotEmpty() && !$errors) {
                // Convierte claves faltantes a nombres legibles
                $cols = $missing->map(fn($k) => $k)->values()->all();

                // Formatea: "a, b y c"
                $last = array_pop($cols);
                $colsStr = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    0,
                    'Estructura invalida en el excel',
                    1,
                    'failed',
                    "Estructura inválida en el Excel. Faltan columnas requeridas: {$colsStr}."
                ));

                $errors = true;
            }

            if ($errors) {
                ProcessBatch::where('batch_id', $this->customBatchId)->update([
                    'error_count' => 1,
                    'status' => 'failed',
                    'metadata' => json_encode($this->metadata),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Error en ValidateExcelJob: {$e->getMessage()}", [
                'customBatchId' => $this->customBatchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($this->userId, 'Error en Validacion de estructura del Excel', "Error en Validacion de estructura del Excel: {$e->getMessage()}", 'error');
            $this->fail($e);
        }
    }

    /**
     * Actualiza el estado del batch en la tabla process_batches.
     *
     * @param string $status
     * @return void
     */
    protected function updateBatchStatus(string $status): void
    {
        ProcessBatch::where('batch_id', $this->customBatchId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    /**
     * Envía una notificación al usuario.
     *
     * @param string|null $userId
     * @param string $title
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function notifyUser(?string $userId, string $title, string $message, string $type): void
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => $title,
                    'subtitle' => $message,
                    'type' => $type,
                ]));
            } else {
                Log::warning("Usuario no encontrado para notificación: {$userId}");
            }
        } else {
            Log::warning("No se proporcionó userId para notificación en batch {$this->customBatchId}");
        }
    }
}
