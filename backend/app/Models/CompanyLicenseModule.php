<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLicenseModule extends Model
{
      use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'last_reset_at' => 'datetime',
    ];

    // Relación con la licencia de la compañía
    public function companyLicense()
    {
        return $this->belongsTo(CompanyLicense::class);
    }
}
