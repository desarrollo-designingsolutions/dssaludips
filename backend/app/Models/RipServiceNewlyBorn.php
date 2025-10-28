<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceNewlyBorn extends Model
{
    use Cacheable, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function sexoBiologico()
    {
        return $this->belongsTo(Sexo::class, 'codSexoBiologico', 'codigo');
    }

    public function diagnosticoPrincipal()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipal', 'codigo');
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
