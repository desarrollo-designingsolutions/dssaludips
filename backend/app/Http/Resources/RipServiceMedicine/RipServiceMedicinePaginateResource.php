<?php

namespace App\Http\Resources\RipServiceMedicine;

use App\Models\CatalogoCum;
use App\Models\Ium;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceMedicinePaginateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->codTecnologiaSaludable_type && $this->codTecnologiaSaludable_id) {
                if ($this->codTecnologiaSaludable_type === 'CatalogoCum') {
                    $codTecnologia = CatalogoCum::where('id', $this->codTecnologiaSaludable_id)->first();
                }
                if ($this->codTecnologiaSaludable_type === 'Ium') {
                    $codTecnologia = Ium::where('id', $this->codTecnologiaSaludable_id)->first();
                }
            }

        return [
            'id' => $this->id,
            'numAutorizacion' => $this->numAutorizacion,
            'idMIPRES' => $this->idMIPRES,
            'fechaDispensAdmon' => $this->fechaDispensAdmon,
            'diasTratamiento' => $this->diasTratamiento,
            'cantidadMedicamento' => $this->cantidadMedicamento,
            'concentracionMedicamento' => $this->concentracionMedicamento,
            'codTecnologiaSalud' => $this->codTecnologiaSaludable_type && $this->codTecnologiaSaludable_id ? $codTecnologia?->codigo.' - ' .$codTecnologia?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.' - ' .$this->diagnosticoPrincipal?->nombre : '',
            'codDiagnosticoRelacionado' => $this->codDiagnosticoRelacionado ? $this->diagnosticoRelacionado?->codigo.' - ' .$this->diagnosticoRelacionado?->nombre : '',
            'tipoMedicamento' => $this->tipoMedicamento ? $this->conceptoRecaudoRelation?->codigo.' - ' .$this->conceptoRecaudoRelation?->nombre : '',
            'nomTecnologiaSalud' => $this->nomTecnologiaSalud ? $this->tipoMedicamentoRelation?->codigo.' - ' .$this->tipoMedicamentoRelation?->nombre : '',
            'unidadMedida' => $this->unidadMedida ? $this->nomTecnologiaSaludRelation?->codigo.' - ' .$this->nomTecnologiaSaludRelation?->nombre : '',
            'formaFarmaceutica' => $this->formaFarmaceutica ? $this->unidadMedidaRelation?->codigo.' - ' .$this->unidadMedidaRelation?->nombre : '',
            'unidadMinDispensa' => $this->unidadMinDispensa ? $this->formaFarmaceuticaRelation?->codigo.' - ' .$this->formaFarmaceuticaRelation?->nombre : '',
            'conceptoRecaudo' => $this->conceptoRecaudo ? $this->unidadMinDispensaRelation?->codigo.' - ' .$this->unidadMinDispensaRelation?->nombre : '',
            'vrUnitMedicamento' => formatNumber($this->vrUnitMedicamento),
            'valorPagoModerador' => formatNumber($this->valorPagoModerador),
            'vrServicio' => formatNumber($this->vrServicio),
        ];
    }
}
