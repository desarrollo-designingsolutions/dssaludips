<?php

namespace App\Services;

use App\Enums\License\LicenseInvoiceStatusEnum;
use App\Models\CompanyLicense;
use App\Models\CompanyLicenseModule;
use App\Models\LicenseInvoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LicenseService
{
    public static function renew(string $oldLicenseId, Carbon $newStartDate): CompanyLicense
    {
        $oldLicense = CompanyLicense::with('modules')->findOrFail($oldLicenseId);

        // 1. Cerrar licencia vieja
        $oldLicense->update([
            'status' => \App\Enums\License\LicenseStatusEnum::LICENSE_STATUS_003, // expired
        ]);

        // 2. Crear nueva licencia
        $newLicense = CompanyLicense::create([
            'id' => (string) Str::uuid(),
            'company_id' => $oldLicense->company_id,
            'license_id' => $oldLicense->license_id,
            'custom_name' => $oldLicense->custom_name . ' - ' . $newStartDate->format('M Y'),
            'start_date' => $newStartDate,
            'end_date' => $newStartDate->copy()->addMonths(6),
            'renewal_date' => $newStartDate->copy()->addMonths(6)->addDay(),
            'status' => \App\Enums\License\LicenseStatusEnum::LICENSE_STATUS_001, // draft
            'is_paid' => false,
            'total_due' => 0, // se calcula después
        ]);

        // 3. Copiar módulos personalizados
        $totalDue = 0;
        foreach ($oldLicense->modules as $oldModule) {
            $newModule = CompanyLicenseModule::create([
                'id' => (string) Str::uuid(),
                'company_license_id' => $newLicense->id,
                'module_name' => $oldModule->module_name,
                'max_records' => $oldModule->max_records,
                'package_price' => $oldModule->package_price,
                'current_count' => 0, // ← RESETEADO
                'last_reset_at' => now(),
            ]);
            $totalDue += $newModule->package_price;
        }

        // 4. Actualizar total_due
        $newLicense->update(['total_due' => $totalDue]);

        // 5. Generar factura
        LicenseInvoice::create([
            'id' => (string) Str::uuid(),
            'company_license_id' => $newLicense->id,
            'invoice_number' => 'INV-' . $newStartDate->format('Y') . '-' . str_pad(LicenseInvoice::count() + 1, 3, '0', STR_PAD_LEFT),
            'period_start' => $newStartDate,
            'period_end' => $newStartDate->copy()->addMonths(6)->subDay(),
            'total_due' => $totalDue,
            'status' => LicenseInvoiceStatusEnum::INVOICE_STATUS_001, // pending
        ]);

        return $newLicense;
    }
}
