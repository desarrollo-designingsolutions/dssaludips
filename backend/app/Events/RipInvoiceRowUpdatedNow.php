<?php

namespace App\Events;

use App\Models\RipInvoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RipInvoiceRowUpdatedNow implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ripInvoice;

    /**
     * Create a new event instance.
     */
    public function __construct($rip_invoice_id)
    {
        $this->ripInvoice = RipInvoice::find($rip_invoice_id);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        // Define el canal que usará el evento para emitir
        return new Channel("rip_invoice.{$this->ripInvoice->id}");
    }

    public function broadcastAs()
    {
        return 'RipInvoiceRowUpdatedNow'; // Nombre del evento que escuchará el frontend
    }

    public function broadcastWith()
    {
        // Aquí puedes incluir los datos que deseas enviar al frontend

        return [
            'id' => $this->ripInvoice->id,

            'status' => $this->ripInvoice->status,
            'status_backgroundColor' => $this->ripInvoice->status->backgroundColor(),
            'status_description' => $this->ripInvoice->status->description(),

            'status_xml' => $this->ripInvoice->status_xml,
            'status_xml_backgroundColor' => $this->ripInvoice->status_xml->backgroundColor(),
            'status_xml_description' => $this->ripInvoice->status_xml->description(),

            'path_xml' => $this->ripInvoice->path_xml,
        ];
    }
}
