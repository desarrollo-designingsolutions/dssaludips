<?php

namespace App\Jobs\Rips;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\BuildAllDataToJson;
use App\Models\ProcessBatch;
use App\Models\Rip;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BuildJsonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;
    protected string $selectedQueue;

    public function __construct(string $batchId, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $totalErrors = ErrorCollector::countErrors($this->batchId);

        // Obtener metadatos del batch
        $metadata = $redis->hgetall("rip_batch:{$this->batchId}");
        Log::info("BuildJsonJob started for batch {$this->batchId} with metadata: ", [$metadata]);


        $userId = $metadata['user_id'] ?? null;
        $type = $metadata['type'] ?? 'RIP_TYPE_001'; // Tipo por defecto: zip
        $companyId = $metadata['company_id'] ?? null;
        $pathZip = $metadata['file_path'] ?? null;


        // Verificar si hay errores
        if ($totalErrors > 0) {
            Log::warning("BuildJsonJob falló: Se encontraron {$totalErrors} errores en el batch {$this->batchId}.");
            $this->updateBatchStatus('failed');
            $this->notifyUser($userId, 'Error en Construcción de JSON', "No se pudo construir el JSON debido a {$totalErrors} errores en la validación.", 'error');
            return;
        }

        try {
            // Construir el JSON
            $jsonContents = BuildAllDataToJson::execute($this->batchId);
            Log::info("BuildAllDataToJson started for batch {$this->batchId}", [$jsonContents]);

            if (empty($jsonContents)) {
                throw new \Exception("El JSON generado está vacío o no se pudo construir.");
            }

            // Calcular facturas
            $numInvoices = count($jsonContents); // Número total de facturas (AF)
            $successfulInvoices = $numInvoices; // Suponemos que todas son exitosas si no hay errores
            $failedInvoices = 0; // Sin errores, no hay facturas fallidas

            // Crear registro en la tabla rips
            $rip = Rip::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'user_id' => $userId,
                'process_batch_id' => $this->batchId,
                'path_zip' => $pathZip,
                'numInvoices' => $numInvoices,
                'successfulInvoices' => $successfulInvoices,
                'failedInvoices' => $failedInvoices,
                'type' => $type,
                'status' => 'completed',
            ]);

            // Guardar el JSON en el sistema de archivos
            $nameFile = 'rips_' . $rip->id . '.json';
            $path = "companies/company_{$companyId}/rips/{$type}/rips_{$rip->id}/{$nameFile}";
            Storage::disk(Constants::DISK_FILES)->put($path, json_encode($jsonContents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Actualizar el batch con estado completado
            $this->updateBatchStatus('completed');

            // Notificar al usuario
            $this->notifyUser($userId, 'JSON Construido Exitosamente', "Se generó el archivo JSON para el batch {$this->batchId} con {$numInvoices} facturas.", 'success');

            Log::info("BuildJsonJob completado: JSON guardado en {$path}, registro creado en rips con ID {$rip->id}.");
        } catch (\Exception $e) {
            Log::error("Error en BuildJsonJob: {$e->getMessage()}", [
                'batchId' => $this->batchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($userId, 'Error en Construcción de JSON', "Falló la construcción del JSON: {$e->getMessage()}", 'error');
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
        ProcessBatch::where('batch_id', $this->batchId)->update([
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
            Log::warning("No se proporcionó userId para notificación en batch {$this->batchId}");
        }
    }
}
