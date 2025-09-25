<?php

namespace App\Events;

use App\Models\Rip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RipRowUpdatedNow implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rip;

    /**
     * Create a new event instance.
     */
    public function __construct($rip_id)
    {
        $this->rip = Rip::find($rip_id);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        // Define el canal que usará el evento para emitir
        return new Channel("rip.{$this->rip->id}");
    }

    public function broadcastAs()
    {
        return 'RipRowUpdatedNow'; // Nombre del evento que escuchará el frontend
    }

    public function broadcastWith()
    {
        // Aquí puedes incluir los datos que deseas enviar al frontend

        return [
            'id' => $this->rip->id,

            'status' => $this->rip->status,
            'status_backgroundColor' => $this->rip->status->backgroundColor(),
            'status_description' => $this->rip->status->description(),
        ];
    }
}
