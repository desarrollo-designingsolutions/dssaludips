<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Events\RipValidationStatusUpdated;
use App\Repositories\RipInvoiceRepository;
use App\Services\Rips\RipsMinistryApiClient;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidateRipInvoiceJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $invoiceId;
    public $batchId;


    public function __construct($invoiceId, $batchId = null)
    {
        $this->invoiceId = $invoiceId;
        $this->batchId = $batchId;
    }

    public function handle(RipsMinistryApiClient $ripsClient, RipInvoiceRepository $ripInvoiceRepository)
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Notificar inicio - se envía al canal específico de esta factura
        event(new RipValidationStatusUpdated(
            $this->invoiceId,
            RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006,
            null,
            $this->batchId,
        ));
        $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006, "status");


        try {
            $result = $ripsClient->validateInvoice($this->invoiceId);

            if ($result["status_code"] != 200) {
                $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_007, "status");
                event(new RipValidationStatusUpdated(
                    $this->invoiceId,
                    RipInvoiceStatusEnum::RIP_INVOICE_STATUS_007,
                    null,
                    $this->batchId,
                ));
            } else {
                // Actualiza estado final y notifica
                $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001, "status");
                event(new RipValidationStatusUpdated(
                    $this->invoiceId,
                    RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001,
                    null,
                    $this->batchId,
                ));
            }
        } catch (\Exception $e) {
            Log::error("Error en job ValidateRipInvoiceJob para factura {$this->invoiceId}: " . $e->getMessage());


            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception->getMessage());
        // event(new RipValidationStatusUpdated(
        //     $this->invoiceId,
        //     'failed',
        //     null,
        //     $exception->getMessage(),
        //     $this->batchId
        // ));
    }
}
