<?php

namespace App\Http\Resources\RipServiceProcedure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceProcedurePaginateResource extends JsonResource
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
            'consecutivo' => $this->consecutivo,
            'fechaInicioAtencion' => $this->fechaInicioAtencion,
            'idMIPRES' => $this->idMIPRES,
            'numAutorizacion' => $this->numAutorizacion,
            'codProcedimiento' => $this->codProcedimiento ? $this->procedimiento?->codigo.'-'.$this->procedimiento?->nombre : '',
            'viaIngresoServicioSalud' => $this->viaIngresoServicioSalud ? $this->viaIngresoServicioSaludRelation?->codigo.'-'.$this->viaIngresoServicioSaludRelation?->nombre : '',
            'modalidadGrupoServicioTecSal' => $this->modalidadGrupoServicioTecSal ? $this->modalidadGrupoServicioTecSalRelation?->codigo.'-'.$this->modalidadGrupoServicioTecSalRelation?->nombre : '',
            'grupoServicios' => $this->grupoServicios ? $this->grupoServiciosRelation?->codigo.'-'.$this->grupoServiciosRelation?->nombre : '',
            'codServicio' => $this->codServicio ? $this->servicio?->codigo.'-'.$this->servicio?->nombre : '',
            'finalidadTecnologiaSalud' => $this->finalidadTecnologiaSalud ? $this->finalidadTecnologiaSaludRelation?->codigo.'-'.$this->finalidadTecnologiaSaludRelation?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.'-'.$this->diagnosticoPrincipal?->nombre : '',
            'codDiagnosticoRelacionado' => $this->codDiagnosticoRelacionado ? $this->diagnosticoRelacionado?->codigo.'-'.$this->diagnosticoRelacionado?->nombre : '',
            'codComplicacion' => $this->codComplicacion ? $this->complicacion?->codigo.'-'.$this->complicacion?->nombre : '',
            'conceptoRecaudo' => $this->conceptoRecaudo ? $this->conceptoRecaudoRelation?->codigo.'-'.$this->conceptoRecaudoRelation?->nombre : '',

            'valorPagoModerador' => formatNumber($this->valorPagoModerador),
            'vrServicio' => formatNumber($this->vrServicio),
        ];
    }
}
