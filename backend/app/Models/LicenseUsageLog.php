<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseUsageLog extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    // Relación polimórfica
    public function record()
    {
        return $this->morphTo();
    }

    // Relaciones
    public function companyLicense()
    {
        return $this->belongsTo(CompanyLicense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
