<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceHospitalization extends Model
{
    use Cacheable, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function viaIngresoServicioSaludRelation()
    {
        return $this->belongsTo(ViaIngresoUsuario::class, 'viaIngresoServicioSalud', 'codigo');
    }

    public function causaMotivoAtencionRelation()
    {
        return $this->belongsTo(RipsCausaExternaVersion2::class, 'causaMotivoAtencion', 'codigo');
    }

    public function diagnosticoPrincipal()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipal', 'codigo');
    }

    public function diagnosticoPrincipalE()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipalE', 'codigo');
    }

    public function diagnosticoRelacionadoE1()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionadoE1', 'codigo');
    }

    public function diagnosticoRelacionadoE2()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionadoE2', 'codigo');
    }

    public function diagnosticoRelacionadoE3()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionadoE3', 'codigo');
    }

    public function complicacion()
    {
        return $this->belongsTo(Cie10::class, 'codComplicacion', 'codigo');
    }

    public function condicionDestinoUsuarioEgresoRelation()
    {
        return $this->belongsTo(CondicionyDestinoUsuarioEgreso::class, 'condicionDestinoUsuarioEgreso', 'codigo');
    }

    public function diagnosticoCausaMuerte()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoCausaMuerte', 'codigo');
    }
}
