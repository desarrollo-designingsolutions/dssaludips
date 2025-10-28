<?php

namespace App\Http\Resources\RipServiceQuery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceQueryPaginateResource extends JsonResource
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
            'numAutorizacion' => $this->numAutorizacion,
            'codConsulta' => $this->codConsulta ? $this->consulta?->codigo.'-'.$this->consulta?->nombre : '',
            'modalidadGrupoServicioTecSal' => $this->modalidadGrupoServicioTecSal ? $this->modalidadGrupoServicioTecSalRelation?->codigo.'-'.$this->modalidadGrupoServicioTecSalRelation?->nombre : '',

            'grupoServicios' => $this->grupoServicios ? $this->grupoServiciosRelation?->codigo.'-'.$this->grupoServiciosRelation?->nombre : '',
            'codServicio' => $this->codServicio ? $this->servicio?->codigo.'-'.$this->servicio?->nombre : '',
            'finalidadTecnologiaSalud' => $this->finalidadTecnologiaSalud ? $this->finalidadTecnologiaSaludRelation?->codigo.'-'.$this->finalidadTecnologiaSaludRelation?->nombre : '',
            'causaMotivoAtencion' => $this->causaMotivoAtencion ? $this->causaMotivoAtencionRelation?->codigo.'-'.$this->causaMotivoAtencionRelation?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.'-'.$this->diagnosticoPrincipal?->nombre : '',
            'codDiagnosticoRelacionado1' => $this->codDiagnosticoRelacionado1 ? $this->diagnosticoRelacionado1?->codigo.'-'.$this->diagnosticoRelacionado1?->nombre : '',
            'codDiagnosticoRelacionado2' => $this->codDiagnosticoRelacionado2 ? $this->diagnosticoRelacionado2?->codigo.'-'.$this->diagnosticoRelacionado2?->nombre : '',
            'codDiagnosticoRelacionado3' => $this->codDiagnosticoRelacionado3 ? $this->diagnosticoRelacionado3?->codigo.'-'.$this->diagnosticoRelacionado3?->nombre : '',
            'tipoDiagnosticoPrincipal' => $this->tipoDiagnosticoPrincipal ? $this->tipoDiagnosticoPrincipalRelation?->codigo.'-'.$this->tipoDiagnosticoPrincipalRelation?->nombre : '',
            'conceptoRecaudo' => $this->conceptoRecaudo ? $this->conceptoRecaudoRelation?->codigo.'-'.$this->conceptoRecaudoRelation?->nombre : '',

            'valorPagoModerador' => formatNumber($this->valorPagoModerador),
            'vrServicio' => formatNumber($this->vrServicio),
        ];
    }
}
