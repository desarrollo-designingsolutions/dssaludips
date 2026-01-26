<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicensePayment extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    // Relación con la licencia
    public function companyLicense()
    {
        return $this->belongsTo(CompanyLicense::class);
    }

    // Relación con el admin que registró
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
