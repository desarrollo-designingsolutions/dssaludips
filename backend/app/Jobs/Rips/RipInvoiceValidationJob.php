<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\ExcelRequired;
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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        string $userId,
        array $metadata,
        string $selectedQueue = 'default'
    ) {
        $this->customBatchId = $customBatchId;
        $this->userId        = $userId;
        $this->metadata      = $metadata;
        $this->onQueue($selectedQueue);
    }

    public function handle(): void
    {
        // Inicio: limpiar errores previos y notificar progreso
        ErrorCollector::clear($this->customBatchId);

        event(new ImportProgressEvent(
            $this->customBatchId,
            0,
            'Iniciando validación de Excel...',
            0,
            'active',
            'Leyendo archivo Excel...'
        ));

        try {
            // Agrupar por número de factura
            $groupedByFactura = ExcelRequired::groupByNumFactura($this->metadata['xlsCollection']); // Collection keyed by num_facti
            $total = $groupedByFactura->count();
            $processed = 0;

            Log::Info($this->metadata['ripInvoice']);

            // Lógica de alcance
            if ($this->metadata['ripInvoice']) { // INDEPENDIENTE: solo la factura seleccionada
                $ripInvoice = RipInvoice::find($this->metadata['ripInvoice']['id'], "rip");
                $rip = $ripInvoice->rip;
                if (!$ripInvoice) {
                    $this->pushError($processed, 'numFactura', 0, [], 'Factura seleccionada no existe.', 'RIP_EXCEL_001');
                }
                $numFacturaSel = (string) $ripInvoice->invoice_number;

                if (!$groupedByFactura->has($numFacturaSel)) {
                    $this->pushError($processed, 'numFactura', 0, [], 'La factura seleccionada no aparece en el Excel.', 'RIP_EXCEL_002');
                }

                // Deja en $xlsCollection SOLO ese grupo (aunque el archivo sea masivo)
                $xlsCollection = collect([$numFacturaSel => $groupedByFactura->get($numFacturaSel)]);
            } else {
                // GLOBAL: valida que TODAS las facturas del Excel existan y pertenezcan al RIP
                $rip = Rip::find($this->metadata['rip']);
                if (!$rip) {
                    // return ['code' => 404, 'status' => 'error', 'message' => 'RIP no encontrado.'];
                    $this->pushError($processed, 'numFactura', 0, [], 'RIP no encontrado.', 'RIP_EXCEL_003');
                }

                // Facturas detectadas en el Excel (claves del groupBy)
                $excelInvoices = $groupedByFactura->keys()->map(fn($n) => (string) $n)->values();

                if ($excelInvoices->isEmpty()) {
                    $this->pushError($processed, 'numFactura', 0, [], 'No se encontraron números de factura en el Excel.', 'RIP_EXCEL_004');
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

                    $this->pushError($processed, 'numFactura', 0, [], "Las siguientes facturas del Excel no existen en el sistema: {$list}.", 'RIP_EXCEL_005');
                }

                // --- 2) Verificar que PERTENEZCAN al RIP seleccionado ---
                $validForRip = RipInvoice::query()
                    ->where('rip_id', $this->metadata['rip'])
                    ->pluck('invoice_number')
                    ->map(fn($n) => (string) $n);

                $foreign = $excelInvoices->diff($validForRip);
                if ($foreign->isNotEmpty()) {
                    $cols = $foreign->values()->all();
                    $last = array_pop($cols);
                    $list = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                    $this->pushError($processed, 'numFactura', 0, [], "El Excel contiene facturas que no pertenecen al RIP seleccionado: {$list}.", 'RIP_EXCEL_006');
                }

                $xlsCollection = $groupedByFactura;
            }

            // if ($invoice_id) {
            //     $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
            //     $ripInvoice->save();
            //     RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
            //     $jsonData = openFileJson($ripInvoice->path_json);
            //     $jsonData = [$jsonData]; // aqui lo hacemos asi para que me siga funcionando la funcion proccessData cunado es independiente


            // } else {
            //     $rip->status = RipStatusEnum::RIP_STATUS_004->value;
            //     $rip->save();
            //     RipRowUpdatedNow::dispatch($rip->id);
            //     $jsonData = openFileJson($rip->path_json);



            //     $firstKey = $xlsCollection->keys()->first();
            //     if (is_int($firstKey)) {
            //         $xlsCollection = $xlsCollection->groupBy(fn($r) => (string)($r['num_factura'] ?? $r['num_facti'] ?? ''));
            //     }

            //     $invoiceNumbers = $xlsCollection->keys()->map(fn($n) => (string) $n)->values();

            //     $idsByNumber = RipInvoice::query()
            //         ->where('rip_id', $rip_id)
            //         ->whereIn('invoice_number', $invoiceNumbers)
            //         ->pluck('id', 'invoice_number');

            //     foreach ($xlsCollection as $numFactura => $rows) {
            //         $ripInvoiceId = $idsByNumber->get((string) $numFactura);
            //         $ripInvoice = $this->ripInvoiceRepository->find($ripInvoiceId);

            //         $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
            //         $ripInvoice->save();
            //         RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
            //     }
            // }

            // //aqui pasamos toda la data encontrada en el archivo xls al array general de las facturas
            // $jsonInfo = ExcelRequired::processData($jsonData, $xlsCollection);

            // $validationExcel = ExcelRequired::validateDataFilesExcel($jsonInfo, $jsonData);

            // if ($validationExcel['totalErrorMessages'] > 0) {
            //     return [
            //         'code'    => 422,
            //         'status'  => 'error',
            //         'message' => "Se encontraron {$validationExcel['totalErrorMessages']} errores en la validacion del excel.",
            //     ];
            // }

            // //Aqui se traspasa la informacion que esta bien segun las validaciones de excel
            // return $jsonInvoices = $jsonData;
            // foreach ($jsonInvoices as $key => $value) {
            //     DB::beginTransaction();
            //     //se guarda el xls nuevo y json independientes en la bd
            //     GenerateRipInfo::saveReloadDataInvoice($rip->id, $value, $validationExcel['totalErrorMessages']);

            //     DB::commit();
            // }

            // //informacion del resultado de las validaciones Excel

            // GenerateRipInfo::generateDataJsonAndExcel($rip->id);
            // ExcelRequired::validateRipsStatus($rip->id);
            // RipRowUpdatedNow::dispatch($rip->id);
        } catch (\Throwable $e) {
            // Error inesperado
            Log::error("Error en RipInvoiceValidationJob: {$e->getMessage()}", [
                'customBatchId' => $this->customBatchId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->updateBatchStatus('failed');
            $this->notifyUser($this->userId, 'Error en Validacion de datos del Excel', "Error en Validacion de datos del Excel: {$e->getMessage()}", 'error');
            $this->fail($e);
        } finally {
            // Persistir errores a BD y actualizar process_batches/rip_batch
            ErrorCollector::saveErrorsToDatabase(
                $this->customBatchId,
                $this->hasErrors ? 'failed' : 'completed'
            );
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
            batchId: $this->batchId,
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
