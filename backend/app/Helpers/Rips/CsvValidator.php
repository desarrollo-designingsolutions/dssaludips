<?php

namespace App\Helpers\Rips;

use App\Helpers\Constants;
use App\Helpers\Rips\ErrorCodes;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Str;

/**
 * CsvValidator
 *
 * Clase utilitaria que encapsula la validación de estructura del CSV.
 * Método principal:
 *   public static function validateAll(string $batchId): array
 *
 * Nota: no persiste resultados. Devuelve un array con el resultado para que
 * el Job que la invoque decida qué hacer (guardar errores, cambiar estados, etc).
 */
class CsvValidator
{
    /**
     * Validar todo el CSV (solo estructura: existencia + headers).
     *
     * @param string $batchId
     * @param array|null $expectedHeaders Si no se pasa, se usa null y la llamada decide
     * @return array Resultado estructurado
     */
    public static function validateAll(string $batchId, ?array $expectedHeaders = null): array
    {
        $result = [
            'ok' => false,
            'file_path' => null,
            'file_exists' => false,
            'detected_headers' => [],
            'chosen_delimiter' => null,
            'errors' => [],
        ];

        try {
            // 1) Leer metadata desde Redis (fallback a BD no lo hace aquí, solo Redis)
            $redis = Redis::connection('redis_6380');
            $redisKey = "batch:{$batchId}:metadata";
            $filePath = $redis->hget($redisKey, 'file_path') ?: null;

            if (empty($filePath)) {
                // devolver error (no persistir)
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_011['code'],
                    'message' => ErrorCodes::RIP_CSV_011['message'],
                    'details' => null,
                ];
                return $result;
            }

            $result['file_path'] = $filePath;

            // 2) Determinar disco usado
            $diskName = defined('\\App\\Helpers\\Constants::DISK_FILES') ? Constants::DISK_FILES : config('filesystems.default');
            $disk = Storage::disk($diskName);

            // 3) Verificar existencia
            if (! $disk->exists($filePath)) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_012['code'],
                    'message' => sprintf(ErrorCodes::RIP_CSV_012['message'], $filePath),
                    'details' => ['file_path' => $filePath, 'disk' => $diskName],
                ];
                return $result;
            }

            $result['file_exists'] = true;

            // 4) Abrir stream y leer primera línea
            $stream = $disk->readStream($filePath);
            if ($stream === false) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_013['code'],
                    'message' => sprintf(ErrorCodes::RIP_CSV_013['message'], $filePath),
                    'details' => null,
                ];
                return $result;
            }

            $rawLine = fgets($stream);
            fclose($stream);

            if ($rawLine === false || trim($rawLine) === '') {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_001['code'],
                    'message' => ErrorCodes::RIP_CSV_001['message'],
                    'details' => null,
                ];
                return $result;
            }

            // 5) Quitar BOM y normalizar rawLine
            $rawLine = preg_replace('/^\xEF\xBB\xBF/', '', $rawLine);
            $rawLine = trim($rawLine);

            // 6) Detectar delimitador
            $possibleDelimiters = [',', ';', '|', "\t"];
            $chosenDelimiter = null;
            $bestCount = 0;
            foreach ($possibleDelimiters as $delimiter) {
                $parts = str_getcsv($rawLine, $delimiter);
                if (count($parts) > $bestCount) {
                    $bestCount = count($parts);
                    $chosenDelimiter = $delimiter;
                }
            }

            if ($chosenDelimiter === null) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_005['code'],
                    'message' => ErrorCodes::RIP_CSV_005['message'],
                    'details' => null,
                ];
                return $result;
            }

            $result['chosen_delimiter'] = $chosenDelimiter;

            // 7) Parsear y normalizar headers
            $detectedRaw = str_getcsv($rawLine, $chosenDelimiter);
            $detectedHeaders = array_map(function ($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);
                $h = trim($h);
                $h = mb_strtolower($h);
                $h = preg_replace('/\s+/', '_', $h);
                $h = preg_replace('/[^a-z0-9_]/u', '', $h);
                return $h;
            }, $detectedRaw);

            $result['detected_headers'] = $detectedHeaders;

            // 8) Si no se pasan expectedHeaders, devolvemos detección y dejamos decidir al Job
            if (is_null($expectedHeaders)) {
                $result['ok'] = true;
                return $result;
            }

            // 9) Comparar con expectedHeaders
            $missing = array_values(array_diff($expectedHeaders, $detectedHeaders));
            $unexpected = array_values(array_diff($detectedHeaders, $expectedHeaders));

            if (! empty($missing)) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_002['code'],
                    'message' => sprintf(ErrorCodes::RIP_CSV_002['message'], implode(', ', $missing)),
                    'details' => implode(', ', $detectedHeaders),
                ];
            }

            if (count($detectedHeaders) !== count($expectedHeaders)) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_003['code'],
                    'message' => sprintf(ErrorCodes::RIP_CSV_003['message'], count($expectedHeaders), count($detectedHeaders)),
                    'details' => implode(', ', $detectedHeaders),
                ];
            }

            if (! empty($unexpected)) {
                $result['errors'][] = [
                    'code' => ErrorCodes::RIP_CSV_004['code'],
                    'message' => sprintf(ErrorCodes::RIP_CSV_004['message'], implode(', ', $unexpected)),
                    'details' => implode(', ', $detectedHeaders),
                ];
            }

            // 10) Resultado final
            $result['ok'] = empty($result['errors']);
            return $result;
        } catch (Throwable $e) {
            $result['errors'][] = [
                'code' => ErrorCodes::RIP_CSV_010['code'],
                'message' => sprintf(ErrorCodes::RIP_CSV_010['message'], $e->getMessage()),
                'details' => ['exception' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 1000)],
            ];
            return $result;
        }
    }
}
