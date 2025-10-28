<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipServiceMedicine extends Model
{
    use Cacheable, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function codTecnologiaSaludable()
    {
        return $this->morphTo(__FUNCTION__, 'codTecnologiaSaludable_type', 'codTecnologiaSaludable_id');
    }

    public function diagnosticoPrincipal()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoPrincipal', 'codigo');
    }

    public function diagnosticoRelacionado()
    {
        return $this->belongsTo(Cie10::class, 'codDiagnosticoRelacionado', 'codigo');
    }

    public function conceptoRecaudoRelation()
    {
        return $this->belongsTo(ConceptoRecaudo::class, 'conceptoRecaudo', 'codigo');
    }

    public function tipoMedicamentoRelation()
    {
        return $this->belongsTo(TipoMedicamentoPosVersion2::class, 'tipoMedicamento', 'codigo');
    }

    public function nomTecnologiaSaludRelation()
    {
        return $this->belongsTo(Dci::class, 'nomTecnologiaSalud', 'codigo');
    }

    public function unidadMedidaRelation()
    {
        return $this->belongsTo(Umm::class, 'unidadMedida', 'codigo');
    }

    public function formaFarmaceuticaRelation()
    {
        return $this->belongsTo(Ffm::class, 'formaFarmaceutica', 'codigo');
    }

    public function unidadMinDispensaRelation()
    {
        return $this->belongsTo(Upr::class, 'unidadMinDispensa', 'codigo');
    }
}
