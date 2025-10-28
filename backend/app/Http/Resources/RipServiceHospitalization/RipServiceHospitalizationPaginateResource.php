<?php

namespace App\Http\Resources\RipServiceHospitalization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceHospitalizationPaginateResource extends JsonResource
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
            'fechaInicioAtencion' => $this->fechaInicioAtencion,
            'fechaEgreso' => $this->fechaEgreso,
            'numAutorizacion' => $this->numAutorizacion,

            'viaIngresoServicioSalud' => $this->viaIngresoServicioSalud ? $this->viaIngresoServicioSaludRelation?->codigo.'-'.$this->viaIngresoServicioSaludRelation?->nombre : '',
            'causaMotivoAtencion' => $this->causaMotivoAtencion ? $this->causaMotivoAtencionRelation?->codigo.'-'.$this->causaMotivoAtencionRelation?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.'-'.$this->diagnosticoPrincipal?->nombre : '',
            'codDiagnosticoPrincipalE' => $this->codDiagnosticoPrincipalE ? $this->diagnosticoPrincipalE?->codigo.'-'.$this->diagnosticoPrincipalE?->nombre : '',
            'codDiagnosticoRelacionadoE1' => $this->codDiagnosticoRelacionadoE1 ? $this->diagnosticoRelacionadoE1?->codigo.'-'.$this->diagnosticoRelacionadoE1?->nombre : '',
            'codDiagnosticoRelacionadoE2' => $this->codDiagnosticoRelacionadoE2 ? $this->diagnosticoRelacionadoE2?->codigo.'-'.$this->diagnosticoRelacionadoE2?->nombre : '',
            'codDiagnosticoRelacionadoE3' => $this->codDiagnosticoRelacionadoE3 ? $this->diagnosticoRelacionadoE3?->codigo.'-'.$this->diagnosticoRelacionadoE3?->nombre : '',
            'codComplicacion' => $this->codComplicacion ? $this->complicacion?->codigo.'-'.$this->complicacion?->nombre : '',
            'condicionDestinoUsuarioEgreso' => $this->condicionDestinoUsuarioEgreso ? $this->condicionDestinoUsuarioEgresoRelation?->codigo.'-'.$this->condicionDestinoUsuarioEgresoRelation?->nombre : '',
            'codDiagnosticoCausaMuerte' => $this->codDiagnosticoCausaMuerte ? $this->diagnosticoCausaMuerte?->codigo.'-'.$this->diagnosticoCausaMuerte?->nombre : '',
        ];
    }
}
