<?php

namespace App\Http\Resources\RipServiceOtherService;

use App\Models\CatalogoCum;
use App\Models\Ium;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceOtherServicePaginateResource extends JsonResource
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
            'numAutorizacion' => $this->numAutorizacion,
            'idMIPRES' => $this->idMIPRES,
            'fechaSuministroTecnologia' => $this->fechaSuministroTecnologia,
            'tipoOS' => $this->tipoOS ? $this->tipoOSRelation?->codigo.' - ' .$this->tipoOSRelation?->nombre : '',
            'codTecnologiaSalud' => $this->codTecnologiaSalud ? $this->tecnologiaSalud?->codigo.' - ' .$this->tecnologiaSalud?->nombre : '',
            'nomTecnologiaSalud' => $this->nomTecnologiaSalud,
            'cantidadOS' => $this->cantidadOS,
            'vrUnitOS' => formatNumber($this->vrUnitOS),
            'valorPagoModerador' => formatNumber($this->valorPagoModerador),
            'vrServicio' => formatNumber($this->vrServicio),
            'conceptoRecaudo' => $this->conceptoRecaudo ? $this->conceptoRecaudoRelation?->codigo.' - ' .$this->conceptoRecaudoRelation?->nombre : '',
        ];
    }
}
