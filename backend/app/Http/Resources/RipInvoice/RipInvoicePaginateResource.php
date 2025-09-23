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
            'invoice_number' => $this->invoice_number,
            'count_users' => $this->count_users,
            'sumVr' => formatNumber($this->sumVr),

            'status' => $this->status,
            'status_description' => $this->status?->description(),
            'status_backgroundColor' => $this->status?->backgroundColor(),

            'status_xml_description' => $this->status_xml?->description(),
            'status_xml_backgroundColor' => $this->status_xml?->backgroundColor(),

            'path_json' => $this->path_json,
            'path_excel' => $this->path_excel,
            'path_xml' => $this->path_xml,
        ];
    }
}
