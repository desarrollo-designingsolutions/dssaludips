<?php

namespace App\Http\Resources\TipoOtrosServicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoOtrosServiciosSelectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'value' => $this->id,
            'title' => $this->codigo.' - '.$this->nombre,
        ];
    }
}
