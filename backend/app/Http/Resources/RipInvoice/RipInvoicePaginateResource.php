<?php

namespace App\Http\Resources\RipInvoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class RipInvoicePaginateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rip_id' => $this->rip_id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'status_xml' => $this->status_xml,
            'users_count' => $this->users_count,
            'path_json' => $this->path_json,
            'path_excel' => $this->path_excel,
        ];
    }
}
