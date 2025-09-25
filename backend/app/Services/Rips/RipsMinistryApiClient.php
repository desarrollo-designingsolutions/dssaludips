<?php

namespace App\Services\Rips;

use App\Helpers\Constants;
use App\Models\Rip;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\RipInvoice;
use App\Services\CacheService;
use Illuminate\Support\Facades\Redis;

class RipsMinistryApiClient
{
    protected $baseUrl;
    protected $authCredentials;
    protected $cacheKeyRedis;
    protected $cacheService;


    public function __construct()
    {
        $this->cacheService = app(CacheService::class);


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

        // Generar una clave única para Redis basada en la info del usuario
        $this->cacheKeyRedis = $this->cacheService->generateKey("rips:auth:token:", $this->authCredentials, 'string');
    }

    /**
     * Obtiene el token de autenticación, usando Redis para caché.
     *
     * @return array|null El token o null si falla.
     */
    public function getAuthToken()
    {
        // Intentar obtener el token desde Redis
        $cachedToken = $this->cacheService->getDataFromRedis($this->cacheKeyRedis, 'string');

        Log::info('Intentando obtener token desde Redis: ' . $this->cacheKeyRedis);
        Log::info('Token en caché: ' . ($cachedToken ? 'Encontrado' : 'No encontrado'));

        // Verificar si el token en caché es válido

        if ($cachedToken) {
            Log::info('LLAVE recuperado desde Redis: ' . $this->cacheKeyRedis);
            Log::info('DATA recuperado desde Redis: ' , [$cachedToken]);
            Log::info('DATA recuperado desde Redis: ' , [unserialize($cachedToken)]);

            $cachedToken = unserialize($cachedToken);
            return $cachedToken;
        }

        // Si no hay token en caché o es inválido, solicitar uno nuevo
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
                    // Guardar el token en Redis con un TTL (por ejemplo, 1 hora = 3600 segundos)
                    $cachedToken = $this->cacheService->storeDataInRedis($this->cacheKeyRedis, $data, 'string', 3600);

                    Log::info('Token almacenado en Redis: ' . $this->cacheKeyRedis);
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
                    'status_code' => 401
                ];
            }
            $token = $tokenData['token'];
        }

        Log::info("Validando factura ID: {$invoiceId} con token: {$token}");

        $pathJson = $invoice->path_json;

        if (empty($pathJson) || !Storage::disk(Constants::DISK_FILES)->exists($pathJson)) {
            $jsonContent = null;
        } else {
            $jsonContent = Storage::disk(Constants::DISK_FILES)->get($pathJson);
        }

        $ripsData = json_decode($jsonContent, true);

        $pathXml = $invoice->path_xml;

        if (empty($pathXml) || !Storage::disk(Constants::DISK_FILES)->exists($pathXml)) {
            $xmlContent = null;
        } else {
            $xmlContent = Storage::disk(Constants::DISK_FILES)->get($pathXml);
        }

        $xmlBase64 = base64_encode($xmlContent ?? '');
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

            // Guarda los resultados en la base de datos
            $invoice->validation_metadata = $data;
            $invoice->save();

            // Si la petición falla con 401, elimina el token de Redis y reintenta
            if ($statusCode === 401) {
                $this->cacheService->clearByPrefix($this->cacheKeyRedis);

                Log::info('Token eliminado de Redis por error 401: ' . $this->cacheKeyRedis);

                // Reintentar la validación con un nuevo token
                $tokenData = $this->getAuthToken();
                if (!$tokenData) {
                    return [
                        'success' => false,
                        'data' => [],
                        'errors' => ['Autenticación fallida tras reintento'],
                        'status_code' => 401
                    ];
                }
                $token = $tokenData['token'];

                // Reintentar la petición
                $response = Http::ripsApi()->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])->post($url, $payload);

                $data = $response->json();
                $statusCode = $response->status();

                // Guardar nuevamente los resultados
                $invoice->validation_metadata = $data;
                $invoice->save();
            }

            // Si la petición HTTP falla (después del reintento, si aplica)
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'data' => $data,
                    'errors' => ['Error HTTP: ' . $statusCode . ' - ' . $response->body()],
                    'status_code' => $statusCode
                ];
            }

            // Si la petición HTTP es exitosa
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
