<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Rips\RipsMinistryApiClient;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Models\RipInvoice;
use App\Repositories\RipInvoiceRepository;

class ValidateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoiceId;

    /**
     * Create a new job instance.
     */
    public function __construct($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * Execute the job.
     */
    public function handle(RipsMinistryApiClient $client, RipInvoiceRepository $ripInvoiceRepository)
    {
        // Marca como processing y notifica
        $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006, "status");

        event(new RipInvoiceRowUpdatedNow($this->invoiceId));

        // Valida la factura
        $result = $client->validateInvoice($this->invoiceId);
        if($result["status_code"] == 400){
            $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_007, "status");
            event(new RipInvoiceRowUpdatedNow($this->invoiceId));
        }else{
            // Actualiza estado final y notifica
            $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001, "status");
            event(new RipInvoiceRowUpdatedNow($this->invoiceId));

        }

    }
}
