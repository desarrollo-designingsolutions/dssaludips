<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseValidator;
use Symfony\Component\HttpFoundation\Response;

class CheckLicenseLimit
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // 1. OBTENER company_id DESDE EL REQUEST
        // Puedes pasarlo en header, body, route param, etc.
        $companyId = $request->header('X-Company-ID')
                  ?? $request->input('company_id')
                  ?? $request->route('company_id');

        if (!$companyId) {
            return response()->json([
                'error' => 'company_id es requerido.'
            ], 400);
        }

        // 2. Validar que sea UUID válido (opcional pero recomendado)
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $companyId)) {
            return response()->json([
                'error' => 'company_id debe ser un UUID válido.'
            ], 400);
        }

        // 3. Validar licencia
        if (!LicenseValidator::canCreate($module, $companyId)) {
            return response()->json([
                'message' => "No puedes crear más registros en '{$module}'. " .
                          "Límite alcanzado, licencia expirada o no pagada.",
                'code' => 500,
                'module' => $module,
                'company_id' => $companyId,
            ], 500);
        }

        // 4. PASAR company_id al controlador (opcional)
        $request->attributes->set('company_id', $companyId);

        return $next($request);
    }
}
