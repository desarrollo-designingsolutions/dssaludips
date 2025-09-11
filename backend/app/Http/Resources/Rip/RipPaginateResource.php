<?php

namespace App\Http\Resources\Rip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class RipPaginateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $created_at = $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y') : null;

        return [
            'id' => $this->id,
            'numInvoices' => $this->numInvoices,
            'successfulInvoices' => $this->successfulInvoices,
            'failedInvoices' => $this->failedInvoices,
            'type' => $this->type,
            'type_description' => $this->type?->description(),
            'status' => $this->status,
            'status_backgroundColor' => $this->status?->backgroundColor(),
            'status_description' => $this->status?->description(),
            'created_at' => $created_at,
            'user_full_name' => $this->user?->full_name,
        ];
    }
}
