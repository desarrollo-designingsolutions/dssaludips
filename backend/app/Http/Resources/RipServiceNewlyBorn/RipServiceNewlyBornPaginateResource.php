<?php

namespace App\Http\Resources\RipServiceNewlyBorn;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RipServiceNewlyBornPaginateResource extends JsonResource
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
            'fechaNacimiento' => $this->fechaNacimiento,
            'edadGestacional' => $this->edadGestacional,
            'numConsultasCPrenatal' => $this->numConsultasCPrenatal,
            'peso' => $this->peso,
            'fechaEgreso' => $this->fechaEgreso,
            'codSexoBiologico' => $this->codSexoBiologico ? $this->sexoBiologico?->codigo.'-'.$this->sexoBiologico?->nombre : '',
            'codDiagnosticoPrincipal' => $this->codDiagnosticoPrincipal ? $this->diagnosticoPrincipal?->codigo.'-'.$this->diagnosticoPrincipal?->nombre : '',
            'condicionDestinoUsuarioEgreso' => $this->condicionDestinoUsuarioEgreso ? $this->condicionDestinoUsuarioEgresoRelation?->codigo.'-'.$this->condicionDestinoUsuarioEgresoRelation?->nombre : '',
            'codDiagnosticoCausaMuerte' => $this->codDiagnosticoCausaMuerte ? $this->diagnosticoCausaMuerte?->codigo.'-'.$this->diagnosticoCausaMuerte?->nombre : '',
        ];
    }
}
