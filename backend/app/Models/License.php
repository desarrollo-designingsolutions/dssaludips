<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    // Relación con el usuario que creó la plantilla
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con los módulos (próximo paso)
    public function modules()
    {
        return $this->hasMany(LicenseModule::class);
    }
}
