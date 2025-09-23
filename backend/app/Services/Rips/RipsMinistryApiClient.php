<?php

namespace App\Services\Rips;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Rip; // Asume tu modelo

class RipsMinistryApiClient
{
    protected $baseUrl;
    protected $authCredentials;

    public function __construct()
    {
        $this->baseUrl = env('API_BASE_URL', 'https://localhost:9443');

        // Credenciales hardcoded (migra a .env: AUTH_CLAVE, etc.)
        $this->authCredentials = [
            'persona' => [
                'identificacion' => [
                    'tipo' => 'CC',
                    'numero' => '34559561',
                ],
            ],
            'clave' => 'R3dif@rma2025*5',
            'nit' => '901028978',
        ];
    }

    /**
     * Obtiene el token de autenticación, verificando la estructura específica.
     *
     * @return string|null El token o null si falla.
     */
    public function getAuthToken()
    {
        $url = $this->baseUrl . '/api/Auth/LoginSISPRO';

        try {
            $response = Http::post($url, $this->authCredentials);

            if ($response->successful()) {
                $data = $response->json();

                // Verificación específica basada en tu ejemplo
                if (
                    isset($data['login']) && $data['login'] === true &&
                    isset($data['registrado']) && $data['registrado'] === true &&
                    (empty($data['errors']) || $data['errors'] === null) &&
                    isset($data['token'])
                ) {
                    return $data['token'];
                } else {
                    Log::error('Autenticación inválida: ' . json_encode($data));
                    return null;
                }
            } else {
                Log::error('Error HTTP en autenticación: ' . $response->status() . ' - ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción en autenticación: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida un RIP, estructurando la respuesta para fácil verificación.
     *
     * @param int $ripId ID del Rip.
     * @param string $token Token (se obtiene si no se pasa).
     * @return array { 'success' => bool, 'data' => array, 'errors' => array }
     */
    public function validateRip($ripId, $token = null)
    {
        $rip = Rip::find($ripId);
        if (!$rip) {
            return ['success' => false, 'data' => [], 'errors' => ['Rip no encontrado']];
        }

        if (!$token) {
            $token = $this->getAuthToken();
            if (!$token) {
                return ['success' => false, 'data' => [], 'errors' => ['Autenticación fallida']];
            }
        }

        // Carga archivos (igual que antes)
        $jsonContent = Storage::get($rip->path_json);
        if (!$jsonContent) {
            return ['success' => false, 'data' => [], 'errors' => ['JSON no encontrado']];
        }
        $ripsData = json_decode($jsonContent, true);

        $xmlContent = Storage::get($rip->path_xml);
        if (!$xmlContent) {
            return ['success' => false, 'data' => [], 'errors' => ['XML no encontrado']];
        }
        $xmlBase64 = base64_encode($xmlContent);

        $payload = [
            'rips' => $ripsData,
            'xmlFevFile' => $xmlBase64,
        ];

        $url = $this->baseUrl . '/api/PaquetesFevRips/CargarFevRips';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Verificación basada en tu ejemplo
                $success = isset($data['ResultState']) && $data['ResultState'] === true;
                $errors = [];

                if (!$success && isset($data['ResultadosValidacion'])) {
                    // Agrupa errores para mejor legibilidad
                    foreach ($data['ResultadosValidacion'] as $err) {
                        $errors[] = [
                            'clase' => $err['Clase'] ?? 'Desconocido',
                            'codigo' => $err['Codigo'] ?? 'Desconocido',
                            'descripcion' => $err['Descripcion'] ?? '',
                            'observaciones' => $err['Observaciones'] ?? '',
                            'path' => $err['PathFuente'] ?? '',
                        ];
                    }
                }

                return [
                    'success' => $success,
                    'data' => $data, // Toda la respuesta original
                    'errors' => $errors,
                ];
            } else {
                // Si falla (e.g., 401), intenta refrescar token una vez
                if ($response->status() === 401) {
                    $newToken = $this->getAuthToken();
                    if ($newToken) {
                        return $this->validateRip($ripId, $newToken); // Reintento
                    }
                }
                Log::error('Error en validación: ' . $response->status() . ' - ' . $response->body());
                return ['success' => false, 'data' => [], 'errors' => ['Validación fallida: ' . $response->body()]];
            }
        } catch (\Exception $e) {
            Log::error('Excepción en validación: ' . $e->getMessage());
            return ['success' => false, 'data' => [], 'errors' => ['Excepción: ' . $e->getMessage()]];
        }
    }

    /**
     * Valida múltiples RIPs.
     *
     * @param array $ripIds
     * @return array Resultados por ID.
     */
    public function validateMultipleRips(array $ripIds)
    {
        $token = $this->getAuthToken();
        if (!$token) {
            return ['error' => 'Autenticación fallida']; // Global error
        }

        $results = [];
        foreach ($ripIds as $id) {
            $results[$id] = $this->validateRip($id, $token);
        }

        return $results;
    }
}
