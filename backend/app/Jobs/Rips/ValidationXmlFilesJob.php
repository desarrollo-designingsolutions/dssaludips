<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Rips\XmlValidator;
use App\Models\RipInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ValidationXmlFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected string $batchId;

    /**
     * $filePath: ruta absoluta al XML (en storage o sistema de archivos)
     */
    public function __construct(string $filePath, string $batchId, string $selectedQueue)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->onQueue($selectedQueue);
    }

    public function handle(): void
    {
        $redis = Redis::connection('redis_6380');

        try {
            // Log::info("file path: {$this->filePath}, batchId: {$this->batchId}");

            // Evento: inicio lectura XML
            event(new ImportProgressEvent(
                $this->batchId,
                0,
                "Iniciando validación XML...",
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Leyendo archivo XML...",
            ));

            $validationResult = XmlValidator::validateAll($this->batchId, $this->filePath);

            // Obtener errores recolectados
            $errors = ErrorCollector::getErrors($this->batchId);
            $errorCount = count($errors);

            if ($errorCount > 0) {
                Log::error("Validación XML falló crítica para batch {$this->batchId}", ['errors' => $errors]);
                event(new ImportProgressEvent($this->batchId, 1, 'Validación XML fallida', $errorCount, 'failed', 'XML'));
                if (file_exists($this->filePath)) {
                    @unlink($this->filePath);
                }

                $rip_invoice_id = $redis->hget("batch:{$this->batchId}:metadata", 'invoice_id');

                RipInvoice::where('id', $rip_invoice_id)
                    ->update(['status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003->value, 'path_xml' => null]);

                RipInvoiceRowUpdatedNow::dispatch($rip_invoice_id);

                $this->fail(new \Exception('Validación XML crítica fallida'));
                return;
            }

            $status = $errorCount > 0 ? 'failed' : 'active';
            $message = $errorCount > 0 ? 'Validación XML completada con errores' : 'Validación XML completada sin errores';

            event(new ImportProgressEvent(
                $this->batchId,
                1,
                $message,
                ErrorCollector::countErrors($this->batchId),
                $status,
                basename($this->filePath)
            ));
            return;
        } catch (\Throwable $e) {
            Log::error("Error in ProcessXmlFilesJob for batch {$this->batchId}: {$e->getMessage()}");

            ErrorCollector::addError(
                $this->batchId,
                0,
                null,
                "Error procesando XML: {$e->getMessage()}",
                'XML_PROC_001',
                null,
                basename($this->filePath)
            );
            $this->fail($e);
        }
    }
}
