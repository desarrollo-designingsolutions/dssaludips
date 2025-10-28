<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipInvoiceUser extends Model
{
    use Cacheable, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoIdPisis::class, 'tipoDocumentoIdentificacion', 'codigo');
    }

    public function tipoUsuarioRelation()
    {
        return $this->belongsTo(RipsTipoUsuarioVersion2::class, 'tipoUsuario', 'codigo');
    }

    public function paisResidencia()
    {
        return $this->belongsTo(Pais::class, 'codPaisResidencia', 'codigo');
    }

    public function municipioResidencia()
    {
        return $this->belongsTo(Municipio::class, 'codMunicipioResidencia', 'codigo');
    }

    public function sexoRelation()
    {
        return $this->belongsTo(Sexo::class, 'codSexo', 'codigo');
    }

    public function zonaResidencia()
    {
        return $this->belongsTo(ZonaVersion2::class, 'codZonaTerritorialResidencia', 'codigo');
    }

    public function paisOrigen()
    {
        return $this->belongsTo(Pais::class, 'codPaisOrigen', 'codigo');
    }

    public function ripInvoice()
    {
        return $this->belongsTo(RipInvoice::class);
    }

    public function queries()
    {
        return $this->hasMany(RipServiceQuery::class);
    }

    public function procedures()
    {
        return $this->hasMany(RipServiceProcedure::class);
    }

    public function urgencies()
    {
        return $this->hasMany(RipServiceUrgency::class);
    }

    public function hospitalizations()
    {
        return $this->hasMany(RipServiceHospitalization::class);
    }

    public function newlyBorns()
    {
        return $this->hasMany(RipServiceNewlyBorn::class);
    }

    public function medicines()
    {
        return $this->hasMany(RipServiceMedicine::class);
    }

    public function otherServices()
    {
        return $this->hasMany(RipServiceOtherService::class);
    }

    public function totalServiceCounts()
    {
        $counts = [
            'queries' => $this->queries()->count(),
            'procedures' => $this->procedures()->count(),
            'urgencies' => $this->urgencies()->count(),
            'hospitalizations' => $this->hospitalizations()->count(),
            'newlyBorns' => $this->newlyBorns()->count(),
            'medicines' => $this->medicines()->count(),
            'otherServices' => $this->otherServices()->count(),
        ];

        $counts['total'] = array_sum($counts);

        return $counts['total'];
    }
}
