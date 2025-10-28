<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceQuery extends Model
{
    use Cacheable, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function consulta()
    {
        return $this->belongsTo(CupsRips::class, 'codConsulta', 'codigo');
    }

    public function modalidadGrupoServicioTecSalRelation()
    {
        return $this->belongsTo(ModalidadAtencion::class, 'modalidadGrupoServicioTecSal', 'codigo');
    }

    public function grupoServiciosRelation()
    {
        return $this->belongsTo(GrupoServicio::class, 'grupoServicios', 'codigo');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'codServicio', 'codigo');
    }

    public function finalidadTecnologiaSaludRelation()
    {
        return $this->belongsTo(RipsFinalidadConsultaVersion2::class, 'finalidadTecnologiaSalud', 'codigo');
    }

    public function causaMotivoAtencionRelation()
    {
        return $this->belongsTo(RipsCausaExternaVersion2::class, 'causaMotivoAtencion', 'codigo');
    }

    public function diagnosticoPrincipal()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipal', 'codigo');
    }

    public function diagnosticoRelacionado1()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionado1', 'codigo');
    }

    public function diagnosticoRelacionado2()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionado2', 'codigo');
    }

    public function diagnosticoRelacionado3()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionado3', 'codigo');
    }

    public function tipoDiagnosticoPrincipalRelation()
    {
        return $this->belongsTo(RipsTipoDiagnosticoPrincipalVersion2::class, 'tipoDiagnosticoPrincipal', 'codigo');
    }

    public function conceptoRecaudoRelation()
    {
        return $this->belongsTo(ConceptoRecaudo::class, 'conceptoRecaudo', 'codigo');
    }

}
