<?php

namespace App\Models;

use App\Enums\License\LicenseStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLicense extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'paid_at' => 'datetime',
        'is_paid' => 'boolean',
        'status' => LicenseStatusEnum::class,
    ];

    // Relación con la compañía
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relación con la plantilla
    public function license()
    {
        return $this->belongsTo(License::class);
    }

    // Relación con los módulos personalizados
    public function modules()
    {
        return $this->hasMany(CompanyLicenseModule::class);
    }

    // Relación con pagos, facturas, logs (próximos pasos)
    public function payments()
    {
        return $this->hasMany(LicensePayment::class);
    }

    public function invoices()
    {
        return $this->hasMany(LicenseInvoice::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(LicenseUsageLog::class);
    }

    // En CompanyLicense.php
    public function scopeActiveForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId)
            ->where('status', LicenseStatusEnum::LICENSE_STATUS_002)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());
    }
}
