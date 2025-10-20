<?php

namespace App\Jobs\ProcessBatch;

use App\Helpers\Constants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class GenerateErrorCsv implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $processKey;
    protected string $userId;

    public function __construct(string $processKey, string $userId)
    {
        $this->processKey = $processKey;
        $this->userId = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        try {
            // Obtener metadata
            $metadata = Redis::hgetall($this->processKey);
            $fileName = $metadata['file_name'];
            $userId = $metadata['user_id'];

            // Obtener todas las filas de Redis
            $rowsKey = $this->processKey . ':rows';
            $rows = Redis::lrange($rowsKey, 0, -1);

            // Agregar header en español
            $header = 'Número de Fila;Nombre de Columna;Mensaje de Error;Tipo de Error;Valor con Error';
            array_unshift($rows, $header);

            // Generar contenido CSV
            $content = implode("\n", $rows);

            // Guardar archivo en storage
            $finalPath = 'processBatch_reportsErrors/' . $fileName;
            Storage::disk(Constants::DISK_FILES)->put($finalPath, $content);

             // Obtener URL para descarga
            $absolutePath = env('SYSTEM_URL_BACK') . 'storage/' . $finalPath;

            // Notificar al usuario
            $user = User::find($userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => 'Reporte de Errores Generado',
                    'subtitle' => "El archivo {$fileName} está listo para descargar.",
                    'type' => 'success',
                    'action_url' => $absolutePath,
                    'openInNewTab' => true,
                ]));
                Log::info("Reporte CSV {$fileName} generado exitosamente para usuario {$userId}.",[[
                    'title' => 'Reporte de Errores Generado',
                    'subtitle' => "El archivo {$fileName} está listo para descargar.",
                    'type' => 'success',
                    'action_url' => $absolutePath,
                    'openInNewTab' => true,
                ]]);
            }

            // Limpiar Redis
            Redis::del($this->processKey, $rowsKey);
            Log::info("Reporte CSV {$fileName} generado exitosamente para usuario {$userId}.");
        } catch (\Exception $e) {
            Log::error("Error en GenerateErrorCsv para proceso {$this->processKey}: {$e->getMessage()}");
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new BellNotification([
                    'title' => 'Error al generar reporte CSV',
                    'subtitle' => $e->getMessage(),
                    'type' => 'error'
                ]));
            }
            // Limpiar Redis en caso de error
            Redis::del($this->processKey, $rowsKey);
            throw $e; // Re-throw para marcar el job como fallido
        }
    }
}
