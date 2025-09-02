<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Glosa extends Model
{
    use Cacheable, HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $guarded = [];

    /**
     * Boot del modelo para registrar eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se crea una glosa
        static::created(function ($glosa) {
            changeServiceData($glosa->service_id);
        });

        // Cuando se actualiza una glosa
        static::updated(function ($glosa) {
            changeServiceData($glosa->service_id);
        });

        // Cuando se elimina una glosa
        static::deleted(function ($glosa) {
            changeServiceData($glosa->service_id);
        });

        static::saved(function ($glosa) {
            changeServiceData($glosa->service_id);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function code_glosa()
    {
        return $this->belongsTo(CodeGlosa::class);
    }
}
