<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AFFileValidator
{
    /**
     * Valida el archivo AF y sus columnas.
     *
     * @param  string  $fileName  Nombre del archivo
     * @param  array   $rowData   Datos de la fila del txt a validar
     * @param  int     $rowNumber Número de la fila del txt a validar
     * @param  string  $batchId   ID del proceso
     */
    public static function validate(string $fileName, array $rowData, int $rowNumber, string $batchId): void
    {
        // Limpiar todos los elementos de $rowData
        $rowData = CleanRowData::execute($rowData);

        $titleColumn = [
            'Columna 1: Código del prestador de servicios de salud',
            'Columna 2: Razón social o apellidos y nombre del prestador de servicios de salud',
            'Columna 3: Tipo de identificación del prestador de servicios de salud',
            'Columna 4: Número de identificación del prestador',
            'Columna 5: Número de la factura',
            'Columna 6: Fecha de expedición de la factura',
            'Columna 7: Fecha de inicio',
            'Columna 8: Fecha final',
            'Columna 9: Código entidad administradora',
            'Columna 10: Nombre entidad administradora',
            'Columna 11: Número del contrato',
            'Columna 12: Plan de beneficios',
            'Columna 13: Número de la póliza',
            'Columna 14: Valor total del pago compartido (copago)',
            'Columna 15: Valor de la comisión',
            'Columna 16: Valor total de descuentos',
            'Columna 17: Valor neto a pagar por la entidad contratante',
        ];

        // 1. Validar código del prestador de servicios de salud (columna 0)
        // Valor obligatorio
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AF_ERROR_001['message'],
                ErrorCodes::FILE_AF_ERROR_001['code'],
                $rowData[0] ?? '',
                $fileName
            );
        }

        // Que sea el mismo registrado en el archivo de control
        $numberInvoiceCT = self::getNumberInvoiceCT($batchId);
        if ($numberInvoiceCT !== null) {

            if ($rowData[0] !== $numberInvoiceCT) {
                ErrorCollector::addError(
                    $batchId,
                    $rowNumber,
                    $titleColumn[0],
                    ErrorCodes::FILE_AF_ERROR_002['message'],
                    ErrorCodes::FILE_AF_ERROR_002['code'],
                    $rowData[0] ?? '',
                    $fileName
                );
            }
        } else {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AF_ERROR_002['message'],
                ErrorCodes::FILE_AF_ERROR_002['code'],
                $rowData[0] ?? '',
                $fileName
            );
        }

        // 2. Razón social o apellidos y nombre del prestador de servicios de salud (columna 1)
        // Valor obligatorio
        if (empty($rowData[1] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_AF_ERROR_003['message'],
                ErrorCodes::FILE_AF_ERROR_003['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // 3. Tipo de identificación del prestador de servicios de salud (columna 2)
        // Valor obligatorio
        if (empty($rowData[2] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AF_ERROR_004['message'],
                ErrorCodes::FILE_AF_ERROR_004['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // Únicamente los valores permitidos
        $allowedPrefixes = ['NI', 'CC', 'CE', 'CD', 'PA', 'PE'];
        if (!in_array($rowData[2] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AF_ERROR_005['message'],
                ErrorCodes::FILE_AF_ERROR_005['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // 4. Número de identificación del prestador (columna 3)
        // Valor obligatorio
        if (empty($rowData[3] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[3],
                ErrorCodes::FILE_AF_ERROR_006['message'],
                ErrorCodes::FILE_AF_ERROR_006['code'],
                $rowData[3] ?? '',
                $fileName
            );
        }

        // 5. Número de la factura (columna 4)
        // Valor obligatorio
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AF_ERROR_007['message'],
                ErrorCodes::FILE_AF_ERROR_007['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // 6. Fecha de expedición de la factura (columna 5)
        // Valor obligatorio
        if (empty($rowData[5] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[5],
                ErrorCodes::FILE_AF_ERROR_008['message'],
                ErrorCodes::FILE_AF_ERROR_008['code'],
                $rowData[5] ?? '',
                $fileName
            );
        }

        // 7. Fecha de inicio (columna 6)
        // Valor obligatorio
        if (empty($rowData[6] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_AF_ERROR_009['message'],
                ErrorCodes::FILE_AF_ERROR_009['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // 8. Fecha final (columna 7)
        // Valor obligatorio
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AF_ERROR_010['message'],
                ErrorCodes::FILE_AF_ERROR_010['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // 9. Código entidad administradora (columna 8)
        // Valor obligatorio
        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AF_ERROR_011['message'],
                ErrorCodes::FILE_AF_ERROR_011['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }
    }

    private static function getNumberInvoiceCT(string $batchId): ?string
    {
        $redis = Redis::connection('redis_6380');
        $contentDataArrayCt = json_decode($redis->get("rip_batch:{$batchId}:CT"), true);

        // Log::info("ContentDataArrayCT for batch {$batchId}: " . print_r($contentDataArrayCt, true));

        if (!is_array($contentDataArrayCt)) {
            // Log::error("No valid data found in Redis for batch {$batchId}");
            return null;
        }

        $afFile = array_filter($contentDataArrayCt, function ($item) {
            return isset($item[2]) && strpos($item[2], 'AF') === 0;
        });

        $afFile = reset($afFile);

        if ($afFile === false || !isset($afFile[0])) {
            // Log::error("No AF file found or invalid format for batch {$batchId}");
            return null;
        }

        $positionZero = $afFile[0];
        // Log::info("Position Zero for batch {$batchId}: " . $positionZero);

        return $positionZero;
    }

}
