<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Exports\Rips\RipXlsExport;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Models\ProcessBatch;
use App\Models\RipInvoice;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateExcelGlobalRipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;
    protected $rip;
    protected string $company_id;
    protected string $user_id;
    protected string $selectedQueue;

    public function __construct(string $batchId, $rip, string $company_id, string $user_id, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->rip = $rip;
        $this->company_id = $company_id;
        $this->user_id = $user_id;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        self::generateGlobalExcelForRip($this->rip, $this->batchId);
    }

    public function generateGlobalExcelForRip($rip, $batchId): void
    {
        try {
            $invoices = RipInvoice::where('rip_id', $rip->id)
                ->whereNotNull('validation_metadata')
                ->where('validation_metadata', '<>', '')
                ->get();

            $total = $invoices->count();
            $filename = "rip_{$rip->id}";
            $metadata = [
                'file_name' => $filename,
                'file_size' => '',
                'started_at' => now()->toDateTimeString(),
                'total_rows' => $total,
                'user_id' => $this->user_id,
                'company_id' => $this->company_id,
            ];

            $redis = Redis::connection('redis_6380');
            $redis->hmset("batch:{$batchId}:metadata", $metadata);

            ProcessBatch::create([
                'id' => $batchId,
                'batch_id' => $batchId,
                'company_id' => $this->company_id,
                'user_id' => $this->user_id,
                'total_records' => $total,
                'error_count' => 0,
                'status' => 'active',
                'metadata' => json_encode($metadata),
            ]);

            event(new ImportProgressEvent($batchId, 0, 'Iniciando Creacion de archivo Excel', count(ErrorCollector::getErrors($batchId)), 'active', 'Iniciando...'));

            $rows = [];
            $attempted = 0;

            foreach ($invoices as $inv) {
                $attempted++;
                // 1) Validación útil
                $vm = json_decode($inv->validation_metadata, true);
                $rv = $vm['ResultadosValidacion'] ?? null;
                if (empty($rv) || (is_array($rv) && count($rv) === 0)) {
                    continue; // saltar facturas sin validación útil
                }

                // 2) Cargar JSON fuente
                if (!Storage::disk(Constants::DISK_FILES)->exists($inv->path_json)) {
                    Log::warning("No se encontró path_json para RipInvoice ID: {$inv->id}");
                    continue;
                }
                $json = json_decode(Storage::disk(Constants::DISK_FILES)->get($inv->path_json), true);
                if (!$json || !is_array($json)) {
                    Log::warning("Error al decodificar path_json para RipInvoice ID: {$inv->id}");
                    continue;
                }

                $obsByField = [];
                foreach ((array) $rv as $item) {
                    $path = $item['PathFuente'] ?? null;
                    if (!$path) continue;

                    // Mensaje del error (ajusta las llaves según tu estructura real)
                    $msg = $item['Observaciones'];

                    // Tomar el último segmento del path como nombre del campo
                    // Ej: usuarios[0].servicios.consultas[2].valorCampo -> valorCampo
                    if (preg_match('/([A-Za-z0-9_]+)(?:\]?)$/', $path, $m)) {
                        $field = $m[1];
                        $obsByField[$field] = isset($obsByField[$field]) && $obsByField[$field]
                            ? ($obsByField[$field] . ' | ' . $msg)
                            : $msg;
                    }
                }

                // Aplicar PathFuente únicos para "marcar" los campos
                $pathFuentes = array_unique(array_filter(array_map(function ($item) {
                    return $item['PathFuente'] ?? null;
                }, is_array($rv) ? $rv : [])));

                foreach ($pathFuentes as $path) {
                    self::setNullByPath($json, $path);
                }

                // En lugar de empujar solo el JSON, empuja { data, obsByField }
                $rows[] = [
                    'data'       => $json,
                    'obsByField' => $obsByField,
                ];

                event(new ImportProgressEvent($batchId, $attempted, 'Procesando elementos en el excel', count(ErrorCollector::getErrors($batchId)), 'active', "Procesando: {$attempted}/{$total}"));

            }

            if (empty($rows)) {
                event(new ImportProgressEvent($batchId, 0, "Generacion de excel fallida", count(ErrorCollector::getErrors($batchId)), 'failed', "No hay filas válidas para Excel global del RIP ID: {$rip->id}"));
                Log::info("No hay filas válidas para Excel global del RIP ID: {$rip->id}");
                return;
            }

            $type        = $rip->type->value ?? 'unknown';
            $globalName  = "rips_{$rip->id}.xlsx";
            $globalRoute = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/{$globalName}";

            // Escribir nuevo
            Excel::store(new RipXlsExport($rows), $globalRoute, Constants::DISK_FILES, \Maatwebsite\Excel\Excel::XLSX);

            // Verificar
            $exists = Storage::disk(Constants::DISK_FILES)->exists($globalRoute);
            $size   = $exists ? Storage::disk(Constants::DISK_FILES)->size($globalRoute) : 0;
            Log::info("Excel global regenerado: {$globalRoute} (exists={$exists}, size={$size})");

            // Guardar ruta en BD (ajusta el campo si se llama distinto)
            if (property_exists($rip, 'path_excel')) {
                $rip->path_excel = $globalRoute;
                $rip->save();
            }

            $metadata['processed_records'] = $attempted;

            $redis->hmset("batch:{$batchId}:metadata", $metadata);

            ErrorCollector::saveErrorsToDatabase($batchId, 'completed');

            event(new ImportProgressEvent($batchId, $attempted, 'Excel generado', count(ErrorCollector::getErrors($batchId)), 'completed', 'Excel generado exitosamente'));
        } catch (\Throwable $e) {
            Log::error("Error generando Excel global para RIP ID: {$rip->id}. {$e->getMessage()}");
        }
    }

    private static function setNullByPath(&$array, $path)
    {
        if (stripos($path, 'rips.') === 0) {
            $path = substr($path, 5);
        }

        $parts = [];
        preg_match_all('/([^\.\[\]]+|\[\d+\])/', $path, $matches);
        $parts = $matches[0];

        $current = &$array;

        foreach ($parts as $i => $part) {

            if (preg_match('/^\[(\d+)\]$/', $part, $indexMatch)) {
                $index = (int)$indexMatch[1];

                if (!array_key_exists($index, $current)) {  // CAMBIO AQUÍ
                    return;
                }
                $current = &$current[$index];
            } else {

                if (!array_key_exists($part, $current)) {  // CAMBIO AQUÍ
                    return;
                }
                $current = &$current[$part];
            }
        }

        $current = Constants::EXCEL_GENERATION_KEY; // Valor especial para solicitar el campo en el excel a descargar
    }
}
