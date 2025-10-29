<?php

namespace App\Http\Resources\RipServiceUrgency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceUrgencyPaginateResource extends JsonResource
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
            'causaMotivoAtencion' => $this->causaMotivoAtencion ? $this->causaMotivoAtencionRelation?->codigo.' - ' .$this->causaMotivoAtencionRelation?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.' - ' .$this->diagnosticoPrincipal?->nombre : '',
            'codDiagnosticoPrincipalE' => $this->codDiagnosticoPrincipalE ? $this->diagnosticoPrincipalE?->codigo.' - ' .$this->diagnosticoPrincipalE?->nombre : '',
            'codDiagnosticoRelacionadoE1' => $this->codDiagnosticoRelacionadoE1 ? $this->diagnosticoRelacionadoE1?->codigo.' - ' .$this->diagnosticoRelacionadoE1?->nombre : '',
            'codDiagnosticoRelacionadoE2' => $this->codDiagnosticoRelacionadoE2 ? $this->diagnosticoRelacionadoE2?->codigo.' - ' .$this->diagnosticoRelacionadoE2?->nombre : '',
            'codDiagnosticoRelacionadoE3' => $this->codDiagnosticoRelacionadoE3 ? $this->diagnosticoRelacionadoE3?->codigo.' - ' .$this->diagnosticoRelacionadoE3?->nombre : '',
            'condicionDestinoUsuarioEgreso' => $this->condicionDestinoUsuarioEgreso,
            'codDiagnosticoCausaMuerte' => $this->codDiagnosticoCausaMuerte ? $this->diagnosticoCausaMuerte?->codigo.' - ' .$this->diagnosticoCausaMuerte?->nombre : '',
            'fechaEgreso' => $this->fechaEgreso,
        ];
    }
}
