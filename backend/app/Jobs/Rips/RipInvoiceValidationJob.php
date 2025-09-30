<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipStatusEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Events\RipRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\ErrorCodes;
use App\Helpers\Rips\ExcelRequired;
use App\Helpers\Rips\GenerateRipInfo;
use App\Models\ProcessBatch;
use App\Models\Rip;
use App\Models\RipInvoice;
use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Sleep;

class RipInvoiceValidationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Identificador de tu batch de dominio (NO el de Laravel Batchable) */
    protected string $customBatchId;

    /** Usuario a notificar en error inesperado */
    protected string $userId;

    /** Parámetros (colección y headers requeridos) */
    protected array $metadata;

    /** Para marcar si hubo errores en la corrida */
    protected bool $hasErrors = false;

    public function __construct(
        string $customBatchId,
        string $selectedQueue = 'default'
    ) {
        $this->customBatchId = $customBatchId;
        $this->onQueue($selectedQueue);
    }

    public function handle(): void
    {
        $redis = Redis::connection('redis_6380');
        $this->metadata = $redis->hgetall("batch:{$this->customBatchId}:metadata");
        $this->userId = $this->metadata['user_id'] ?? null;

        $xlsCollection = ExcelRequired::openXls($this->metadata['filePath']);

        event(new ImportProgressEvent(
            $this->customBatchId,
            0,
            'Iniciando validación de Excel...',
            ErrorCollector::countErrors($this->customBatchId),
            'active',
            'Leyendo archivo Excel...'
        ));

        try {
            // Agrupar por número de factura
            $groupedByFactura = ExcelRequired::groupByNumFactura($xlsCollection); // Collection keyed by num_facti

            $metadata = $this->metadata;
            $metadata['total_rows'] = $groupedByFactura->count();
            $redis->hmset("batch:{$this->customBatchId}:metadata", $metadata);
            $processed = 0;
            $xlsCollection = [];

            // Lógica de alcance
            if ($this->metadata['ripInvoice_id']) { // INDEPENDIENTE: solo la factura seleccionada
                $ripInvoice = RipInvoice::with('rip')->find($this->metadata['ripInvoice_id']);
                $rip = $ripInvoice->rip;
                if (!$ripInvoice) {
                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_001'), ErrorCodes::RIP_EXCEL_001['code']);
                }
                $numFacturaSel = (string) $ripInvoice->invoice_number;

                if (!$groupedByFactura->has($numFacturaSel)) {
                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_002'), ErrorCodes::RIP_EXCEL_002['code']);
                } else {
                    // Deja en $xlsCollection SOLO ese grupo (aunque el archivo sea masivo)
                    $xlsCollection = collect([$numFacturaSel => $groupedByFactura->get($numFacturaSel)]);
                }
            } else {
                // GLOBAL: valida que TODAS las facturas del Excel existan y pertenezcan al RIP
                $rip = Rip::find($this->metadata['rip_id']);
                if (!$rip) {
                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_003'), ErrorCodes::RIP_EXCEL_003['code']);
                }

                // Facturas detectadas en el Excel (claves del groupBy)
                $excelInvoices = $groupedByFactura->keys()->map(fn($n) => (string) $n)->values();

                if ($excelInvoices->isEmpty()) {
                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_004'), ErrorCodes::RIP_EXCEL_004['code']);
                }

                // --- 1) Verificar que EXISTAN en BD (en cualquier RIP) ---
                $existingAny = RipInvoice::query()
                    ->pluck('invoice_number')
                    ->map(fn($n) => (string) $n);

                $notFound = $excelInvoices->diff($existingAny);

                if ($notFound->isNotEmpty()) {
                    // formatea "a, b y c"
                    $cols = $notFound->values()->all();
                    $last = array_pop($cols);
                    $list = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_005', $list), ErrorCodes::RIP_EXCEL_005['code']);
                }

                // --- 2) Verificar que PERTENEZCAN al RIP seleccionado ---
                $validForRip = RipInvoice::query()
                    ->where('rip_id', $this->metadata['rip_id'])
                    ->pluck('invoice_number')
                    ->map(fn($n) => (string) $n);

                $foreign = $excelInvoices->diff($validForRip);
                if ($foreign->isNotEmpty()) {
                    $cols = $foreign->values()->all();
                    $last = array_pop($cols);
                    $list = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                    $this->hasErrors = true;
                    $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_006', $list), ErrorCodes::RIP_EXCEL_006['code']);
                }

                $xlsCollection = $groupedByFactura;
            }

            if ($this->metadata['ripInvoice_id']) {
                $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
                $ripInvoice->save();
                RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
                $jsonData = openFileJson($ripInvoice->path_json);
                $jsonData = [$jsonData]; // aqui lo hacemos asi para que me siga funcionando la funcion proccessData cunado es independiente
            } else {
                $rip->status = RipStatusEnum::RIP_STATUS_004->value;
                $rip->save();
                RipRowUpdatedNow::dispatch($rip->id);
                $jsonData = openFileJson($rip->path_json);



                $firstKey = $xlsCollection->keys()->first();
                if (is_int($firstKey)) {
                    $xlsCollection = $xlsCollection->groupBy(fn($r) => (string)($r['num_factura'] ?? $r['num_facti'] ?? ''));
                }

                $invoiceNumbers = $xlsCollection->keys()->map(fn($n) => (string) $n)->values();

                $idsByNumber = RipInvoice::query()
                    ->where('rip_id', $rip->id)
                    ->whereIn('invoice_number', $invoiceNumbers)
                    ->pluck('id', 'invoice_number');

                foreach ($xlsCollection as $numFactura => $rows) {
                    $ripInvoiceId = $idsByNumber->get((string) $numFactura);
                    $ripInvoice = RipInvoice::find($ripInvoiceId);

                    if ($ripInvoice) {
                        $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
                        $ripInvoice->save();
                        RipInvoiceRowUpdatedNow::dispatch($ripInvoiceId);
                    }
                }
            }

            if (!$this->hasErrors) {

                $countErrors = ErrorCollector::countErrors($this->customBatchId);

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    0,
                    'Pasando información del excel a un clon del json.',
                    $countErrors,
                    'active',
                    'Iniciando proceso de transferencia de datos.'
                ));

                //aqui pasamos toda la data encontrada en el archivo xls al array general de las facturas
                $jsonInfo = ExcelRequired::processData($jsonData, $xlsCollection);

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    0,
                    'Validando información del clon del json transferido.',
                    $countErrors,
                    'active',
                    'Iniciando proceso validación.'
                ));

                $validationExcel = ExcelRequired::validateDataFilesExcel($jsonInfo, $jsonData);

                if ($validationExcel['totalErrorMessages'] > 0) {

                    foreach ($validationExcel['errorMessages'] as $key => $value) {
                        // $this->pushError($processed, 'numFactura', 0, [], ErrorCodes::getMessage('RIP_EXCEL_006', $list), ErrorCodes::RIP_EXCEL_006['code']);
                    }

                    $this->fail(new \Exception('Validación Excel crítica fallida'));
                }

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    0,
                    'Transfiriendo información al json original.',
                    $countErrors,
                    'active',
                    'Iniciando proceso tranferencia.'
                ));

                //Aqui se traspasa la informacion que esta bien segun las validaciones de excel
                $jsonInvoices = $jsonData;
                foreach ($jsonInvoices as $key => $value) {
                    DB::beginTransaction();

                    $ripInvoice = RipInvoice::query()
                        ->where('rip_id', $rip->id)
                        ->where('invoice_number', $value['numFactura'])
                        ->first();

                    //se guarda el xls nuevo y json independientes en la bd
                    GenerateRipInfo::saveReloadDataInvoice($rip, $value, $ripInvoice);

                    DB::commit();

                    event(new ImportProgressEvent(
                        $this->customBatchId,
                        $key + 1,
                        'Transfiriendo información al json original.',
                        $countErrors,
                        'active',
                        "Facturas procesadas: " . ($key + 1) . " de " . count($jsonInvoices),
                    ));
                    Sleep(5);
                }

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    $key + 1,
                    'Generando el excel y json global.',
                    $countErrors,
                    'active',
                    "Generando archivos finales del RIPS.",
                ));

                GenerateRipInfo::generateDataJsonAndExcel($rip->id);

                event(new ImportProgressEvent(
                    $this->customBatchId,
                    $key + 1,
                    'Verificando estados de facturas y el rips en general.',
                    $countErrors,
                    'active',
                    "Verificando estados de facturas y el rips en general.",
                ));

                // ExcelRequired::validateRipsStatus($rip->id);
                RipRowUpdatedNow::dispatch($rip->id);
            }
        } catch (\Throwable $e) {
            // Error inesperado
            Log::error("Error en RipInvoiceValidationJob: {$e->getMessage()}", [
                'customBatchId' => $this->customBatchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->pushError(0, 'trycatch', null, [], $e->getMessage(), $e->getCode());
            $this->updateBatchStatus('failed');
            $this->notifyUser($this->userId, 'Error en Validacion de datos del Excel', "Error en Validacion de datos del Excel: {$e->getMessage()}", 'error');
        } finally {
            // Persistir errores a BD y actualizar process_batches/rip_batch
            $countErrors = ErrorCollector::countErrors($this->customBatchId);

            Log::info('dentro del finaly', ['countErrors' => $countErrors, 'hasErrors' => $this->hasErrors]);

            $status = $countErrors > 0 ? 'completed_with_errors' : 'completed';

            event(new ImportProgressEvent(
                $this->customBatchId,
                0,
                'Finalizando proceso.',
                $countErrors,
                $status,
                'Finalizando proceso de validación.'
            ));

            ErrorCollector::saveErrorsToDatabase($this->customBatchId, $status);

            foreach ($xlsCollection as $numFactura => $rows) {
                $ripInvoiceId = $idsByNumber->get((string) $numFactura);
                $ripInvoice = RipInvoice::find($ripInvoiceId);

                if ($ripInvoice) {
                    $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->value;
                    $ripInvoice->save();
                    RipInvoiceRowUpdatedNow::dispatch($ripInvoiceId);
                }
            }

            $rip->status = RipStatusEnum::RIP_STATUS_002->value;
            $rip->save();
            RipRowUpdatedNow::dispatch($rip->id);

            event(new ImportProgressEvent(
                $this->customBatchId,
                0,
                'Proceso de validación finalizado.',
                $countErrors,
                $status,
                'Validación de Excel finalizada.'
            ));
        }
    }

    /* ===========================
     * Helpers privados
     * ===========================
     */

    /** Reporta un error a Redis, emite progreso y marca bandera local */
    private function pushError(
        int $processedRowIndex,
        string $columnKey,
        $value,
        array $originalRow,
        string $message,
        string $code
    ): void {
        // Con WithHeadingRow, la primera fila de datos en Excel es la 2.
        $excelRowNumber = $processedRowIndex + 1; // processed=1 -> Excel row 2

        ErrorCollector::addError(
            batchId: $this->customBatchId,
            rowNumber: $excelRowNumber,
            columnName: $columnKey, // p.ej. 'tipo_de_documento', 'fecha_de_nacimiento'
            errorMessage: $message,
            errorType: $code, // p.ej. 'PATIENT_ROW_002'
            errorValue: is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            originalData: json_encode($originalRow, JSON_UNESCAPED_UNICODE) // <-- ROW ORIGINAL DEL EXCEL
        );
    }

    protected function updateBatchStatus(string $status): void
    {
        ProcessBatch::where('batch_id', $this->customBatchId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    /** Notificación al usuario (opcional) */
    protected function notifyUser(?string $userId, string $title, string $message, string $type): void
    {
        if (!$userId) return;
        $user = User::find($userId);
        if ($user) {
            $user->notify(new BellNotification([
                'title'    => $title,
                'subtitle' => $message,
                'type'     => $type,
            ]));
        } else {
            Log::warning("Usuario no encontrado para notificación: {$userId}");
        }
    }
}
