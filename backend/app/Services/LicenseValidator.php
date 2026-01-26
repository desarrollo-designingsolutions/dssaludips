<?php

namespace App\Services;

use App\Enums\License\LicenseStatusEnum;
use App\Models\CompanyLicense;
use App\Models\CompanyLicenseModule;
use Carbon\Carbon;

class LicenseValidator
{
    /**
     * Verifica si una compañía puede crear un registro en un módulo
     */
    public static function canCreate(string $moduleName, string $companyId): bool
    {
        // 1. Buscar licencias activas de la compañía
        $activeLicenses = CompanyLicense::where('company_id', $companyId)
            ->where('status', LicenseStatusEnum::LICENSE_STATUS_002)
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->with('modules')
            ->get();

        if ($activeLicenses->isEmpty()) {
            return false; // No hay licencia activa
        }

        // 2. Verificar que TODAS estén pagadas
        foreach ($activeLicenses as $license) {
            if (!$license->is_paid) {
                return false; // Bloquea si alguna no está pagada
            }
        }

        // 3. Sumar límites y uso del módulo
        $totalMax = 0;
        $totalUsed = 0;

        foreach ($activeLicenses as $license) {
            $module = $license->modules()
                ->where('module_name', $moduleName)
                ->first();

            if ($module) {
                $totalMax += $module->max_records;
                $totalUsed += $module->current_count;
            }
        }

        // Si no existe el módulo en ninguna licencia → bloquea
        if ($totalMax === 0) {
            return false;
        }

        // 4. ¿Hay cupo?
        return $totalUsed < $totalMax;
    }

    /**
     * Incrementa el contador después de crear
     */
    public static function incrementUsage(string $moduleName, string $companyId): void
    {
        $activeLicenses = CompanyLicense::where('company_id', $companyId)
            ->where('status', LicenseStatusEnum::LICENSE_STATUS_002)
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->with('modules')
            ->get();

        foreach ($activeLicenses as $license) {
            $module = $license->modules()
                ->where('module_name', $moduleName)
                ->first();

            if ($module) {
                $module->increment('current_count');
                $module->touch('last_reset_at');
                break; // Solo uno por licencia activa
            }
        }
    }
}
