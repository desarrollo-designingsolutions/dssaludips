<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceOtherService extends Model
{
    use Cacheable, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function tipoOSRelation()
    {
        return $this->belongsTo(TipoOtrosServicios::class, 'tipoOS', 'codigo');
    }

    public function tecnologiaSalud()
    {
        return $this->belongsTo(CupsRips::class, 'codTecnologiaSalud', 'codigo');
    }

    public function conceptoRecaudoRelation()
    {
        return $this->belongsTo(ConceptoRecaudo::class, 'conceptoRecaudo', 'codigo');
    }
}
