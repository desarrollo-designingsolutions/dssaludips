<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\BuildAllDataToJson;
use App\Helpers\Rips\GenerateRipInfo;
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
        if (empty($metadata)) {
            Log::error("No se encontraron metadatos para el batchId: {$this->batchId}");
            return;
        }

        $userId = $metadata['user_id'];
        $type = $metadata['type'];
        $companyId = $metadata['company_id'];
        $pathZip = $metadata['file_path'];
        $process_batch_id = $metadata['process_batch_id'];


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

            //genero los consecutivos para usuarios y servicios tomando encuenta que deben ser consecutivos e iniciar en uno en los servicios y en usuarios
            BuildAllDataToJson::generateConsecutive($jsonContents);

            //revisamos cuantas fcturas estan completas y cuantas estan incompletas de datos
            // $counts = BuildAllDataToJson::checkInvoiceCompleteness($jsonContents);

            if (empty($jsonContents)) {
                throw new \Exception("El JSON generado está vacío o no se pudo construir.");
            }

            // Calcular facturas
            $status = RipStatusEnum::RIP_STATUS_002;
            $numInvoices = count($jsonContents); // Número total de facturas (AF)
            // if ($counts["successfulInvoices"] == $numInvoices) {
            //     $status = RipStatusEnum::RIP_STATUS_002;
            // }

            $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");

            // Evento: Inicio de construcción del JSON
            event(new ImportProgressEvent(
                $this->batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                "Iniciando la contrucción y guardado de Jsons y Exceles independientes", // Todos los registros procesados
                $totalErrors, // Total de errores
                'active',
                "Iniciando {$numInvoices} facturas" // Progreso
            ));

            // Crear registro en la tabla rips
            $rip = Rip::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'user_id' => $userId,
                'process_batch_id' => $process_batch_id,
                'path_zip' => $pathZip,
                'nit' => $jsonContents[0]['numDocumentoIdObligado'] ?? null,
                'numInvoices' => $numInvoices,
                'successfulInvoices' => 0,
                'failedInvoices' => $numInvoices,
                'type' => $type,
                'sumVr' => 0,
                'status' => $status,
            ]);

            GenerateRipInfo::saveReloadDataRips($rip->id, $jsonContents, $this->batchId);


            event(new ImportProgressEvent(
                $this->batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                'Contruyendo JSON y Excel global',
                $totalErrors, // Total de errores
                'active',
                "Generando JSON y Excel global" // Progreso
            ));

            GenerateRipInfo::generateDataJsonAndExcel($rip->id);

            // Actualizar el batch con estado completado
            $this->updateBatchStatus('completed');

            event(new ImportProgressEvent(
                $this->batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                'Validación completada',
                $totalErrors, // Total de errores
                'completed',
                "proceso finalizado" // Progreso
            ));

            // Notificar al usuario
            $this->notifyUser($userId, 'JSON Construido Exitosamente', "Se generó el archivo JSON para el batch {$this->batchId} con {$numInvoices} facturas.", 'success');

            // Log::info("BuildJsonJob completado: JSON guardado en {$path}, registro creado en rips con ID {$rip->id}.");
        } catch (\Exception $e) {
            Log::error("Error en BuildJsonJob: {$e->getMessage()}", [
                'batchId' => $this->batchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($userId, 'Error en Construcción de JSON', "Falló la construcción del JSON: {$e->getMessage()}", 'error');
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
