<?php

namespace App\Services\Rips;

use App\Helpers\Constants;
use App\Models\Rip;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\RipInvoice; // Asumimos que existe este modelo para facturas

class RipsMinistryApiClient
{
    protected $baseUrl;
    protected $authCredentials;

    public function __construct()
    {
        $this->baseUrl = env('API_BASE_URL', 'https://fevrips-api:5100');

        // Configura el cliente HTTP globalmente
        Http::macro('ripsApi', function () {
            return Http::withOptions([
                'verify' => env('API_VERIFY_SSL', false),
            ]);
        });

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
     * @return array|null El token o null si falla.
     */
    public function getAuthToken()
    {
        $url = $this->baseUrl . '/api/Auth/LoginSISPRO';

        try {
            $response = Http::ripsApi()->post($url, $this->authCredentials);

            if ($response->successful()) {
                $data = $response->json();

                // Verificación específica basada en tu ejemplo
                if (
                    isset($data['login']) && $data['login'] === true &&
                    isset($data['registrado']) && $data['registrado'] === true &&
                    (empty($data['errors']) || $data['errors'] === null) &&
                    isset($data['token'])
                ) {
                    return $data;
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
     * Valida una factura, estructurando la respuesta para fácil verificación.
     *
     * @param int $invoiceId ID de la factura (RipInvoice).
     * @param string|null $token Token (se obtiene si no se pasa).
     * @return array {
     *   'success' => bool,
     *   'data' => array,
     *   'errors' => array,
     *   'status_code' => int|null // Código HTTP de la respuesta
     * }
     */
    public function validateInvoice($invoiceId, $token = null)
    {
        $invoice = RipInvoice::find($invoiceId);
        if (!$invoice) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Factura no encontrada'],
                'status_code' => 422
            ];
        }
        if (!$token) {
            $tokenData = $this->getAuthToken();
            if (!$tokenData) {
                return [
                    'success' => false,
                    'data' => [],
                    'errors' => ['Autenticación fallida'],
                    'status_code' => 401 // Usamos 401 porque es un error de autenticación
                ];
            }
            $token = $tokenData['token'];
        }

        $jsonContent = Storage::disk(Constants::DISK_FILES)->get($invoice->path_json);
        if (!$jsonContent) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['JSON no encontrado'],
                'status_code' => 422
            ];
        }
        $ripsData = json_decode($jsonContent, true);
        $xmlContent = Storage::disk(Constants::DISK_FILES)->get($invoice->path_xml);
        if (!$xmlContent) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['XML no encontrado'],
                'status_code' => 422
            ];
        }
        $xmlBase64 = base64_encode($xmlContent);
        $payload = [
            'rips' => $ripsData,
            'xmlFevFile' => $xmlBase64,
        ];
        $url = $this->baseUrl . '/api/PaquetesFevRips/CargarFevRips';

        try {
            $response = Http::ripsApi()->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $data = $response->json();
            $statusCode = $response->status();

            // guarda los resultados en la base de datos
            $invoice->validation_metadata = $data;
            $invoice->save();

            // Si la petición HTTP falla (ej: 400, 401, 500)
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'data' => $data,
                    'errors' => ['Error HTTP: ' . $statusCode . ' - ' . $response->body()],
                    'status_code' => $statusCode
                ];
            }



            // Si la petición HTTP es exitosa, devuelve los datos completos
            return [
                'success' => true,
                'data' => $data,
                'errors' => [],
                'status_code' => $statusCode
            ];
        } catch (\Exception $e) {
            Log::error('Excepción en validación de factura: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Excepción: ' . $e->getMessage()],
                'status_code' => 500
            ];
        }
    }



    /**
     * Valida múltiples facturas.
     *
     * @param array $invoiceIds IDs de las facturas (RipInvoice).
     * @return array Resultados por ID de factura.
     */
    public function validateMultipleInvoices(array $invoiceIds)
    {
        $tokenData = $this->getAuthToken();
        if (!$tokenData) {
            return ['error' => 'Autenticación fallida'];
        }
        $token = $tokenData['token'];

        $results = [];
        foreach ($invoiceIds as $id) {
            $results[$id] = $this->validateInvoice($id, $token);
        }

        return $results;
    }

    /**
     * Valida facturas asociadas a múltiples RIPs.
     *
     * @param array $ripIds IDs de los RIPs.
     * @return array Resultados por ID de factura, agrupados por RIP.
     */
    public function validateInvoicesByRips(array $ripIds)
    {
        $tokenData = $this->getAuthToken();
        if (!$tokenData) {
            return ['error' => 'Autenticación fallida'];
        }
        $token = $tokenData['token'];

        $results = [];
        foreach ($ripIds as $ripId) {
            // Busca el RIP y sus facturas asociadas
            $rip = Rip::with('ripInvoices')->find($ripId);
            if (!$rip) {
                $results[$ripId] = ['success' => false, 'data' => [], 'errors' => ['RIP no encontrado']];
                continue;
            }

            // Si no hay facturas asociadas
            if ($rip->ripInvoices->isEmpty()) {
                $results[$ripId] = ['success' => false, 'data' => [], 'errors' => ['No se encontraron facturas para el RIP']];
                continue;
            }

            // Valida cada factura asociada al RIP
            $results[$ripId] = [];
            foreach ($rip->ripInvoices as $invoice) {
                $results[$ripId][$invoice->id] = $this->validateInvoice($invoice->id, $token);
            }
        }

        return $results;
    }
}
