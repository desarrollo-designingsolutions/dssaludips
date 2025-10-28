<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceProcedure extends Model
{
    use Cacheable, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function procedimiento()
    {
        return $this->belongsTo(CupsRips::class, 'codProcedimiento', 'codigo');
    }

    public function viaIngresoServicioSaludRelation()
    {
        return $this->belongsTo(ViaIngresoUsuario::class, 'viaIngresoServicioSalud', 'codigo');
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

    public function diagnosticoPrincipal()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipal', 'codigo');
    }

    public function diagnosticoRelacionado()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionado', 'codigo');
    }

    public function complicacion()
    {
        return $this->belongsTo(Cie10::class, 'codComplicacion', 'codigo');
    }

    public function conceptoRecaudoRelation()
    {
        return $this->belongsTo(ConceptoRecaudo::class, 'conceptoRecaudo', 'codigo');
    }

}
