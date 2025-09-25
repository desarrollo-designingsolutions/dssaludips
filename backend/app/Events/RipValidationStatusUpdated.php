<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RipValidationStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoiceId;
    public $status;
    public $result;
    public $error;
    public $batchId;

    public function __construct($invoiceId, $status, $result = null, $error = null, $batchId = null)
    {
        $this->invoiceId = $invoiceId;
        $this->status = $status;
        $this->result = $result;
        $this->error = $error;
        $this->batchId = $batchId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("rip_invoice_modal.{$this->invoiceId}"),
            new Channel("rip_invoice.{$this->invoiceId}")
        ];

    }

    public function broadcastWith()
    {
        return [
            'invoice_id' => $this->invoiceId,
            'status' => $this->status?->value,
            'status_backgroundColor' => $this->status?->backgroundColor(),
            'status_description' => $this->status?->description(),
            'result' => $this->result,
            'error' => $this->error,
            'batch_id' => $this->batchId,
            'timestamp' => now()->toISOString(),
        ];
    }
}
