<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseInvoice extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'total_due' => 'decimal:2',
    ];


    public function companyLicense()
    {
        return $this->belongsTo(CompanyLicense::class);
    }
}
