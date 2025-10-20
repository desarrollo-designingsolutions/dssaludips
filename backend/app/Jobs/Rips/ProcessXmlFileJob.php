<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Models\RipInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ProcessXmlFileJob implements ShouldQueue
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
                "Iniciando cargue de XML...",
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Leyendo archivo XML...",
            ));

            $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");

            $rip_invoice = RipInvoice::select(['id', 'invoice_number', 'rip_id'])->with('rip:id,nit,type')->find($metadata['invoice_id']);

            $type = $rip_invoice?->rip?->type->value;
            $rip_id = $rip_invoice?->rip?->id;
            $invoice_number = $rip_invoice?->invoice_number;
            $nameFile = $invoice_number . '.xml';

            $path_xml = 'companies/company_' . $metadata['company_id'] . '/rips/' . $type . '/rip_' . $rip_id . '/invoices/' . $invoice_number . '/' . $nameFile;

            Storage::disk(Constants::DISK_FILES)->move($this->filePath, $path_xml);

            $rip_invoice_id = $redis->hget("batch:{$this->batchId}:metadata", 'invoice_id');
            RipInvoice::where('id', $rip_invoice_id)
                ->update(['status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->value, 'path_xml' => $path_xml]);

            RipInvoiceRowUpdatedNow::dispatch($rip_invoice_id);

            event(new ImportProgressEvent(
                $this->batchId,
                1,
                'Cargue de XML completado',
                ErrorCollector::countErrors($this->batchId),
                'completed',
                'XML cargado exitosamente'
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
