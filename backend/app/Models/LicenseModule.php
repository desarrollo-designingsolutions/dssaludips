<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseModule extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];



    // Relación con la plantilla
    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
