<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class APFileValidator
{
    /**
     * Valida el archivo AP y sus columnas.
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
            'Columna 1: Número de la factura',
            'Columna 2: Código del prestador de servicios de salud',
            'Columna 3: Tipo de identificación del usuario',
            'Columna 4: Número de identificación del usuario en el sistema',
            'Columna 5: Fecha del procedimiento',
            'Columna 6: Número de autorización',
            'Columna 7: Código del procedimiento',
            'Columna 8: Ámbito de realización del procedimiento',
            'Columna 9: Finalidad del procedimiento',
            'Columna 10: Personal que atiende',
            'Columna 11: Diagnóstico principal',
            'Columna 12: Diagnóstico relacionado',
            'Columna 13: Complicación',
            'Columna 14: Forma de realización del acto quirúrgico',
            'Columna 15: Valor del procedimiento',
        ];

        // 1. Número de la factura (columna 0)
        // Valor obligatorio
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AP_ERROR_001['message'],
                ErrorCodes::FILE_AP_ERROR_001['code'],
                $rowData[0] ?? '',
                $fileName
            );
        }

        // 2. Código del prestador de servicios de salud (columna 1)
        // Valor obligatorio
        if (empty($rowData[1] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_AP_ERROR_002['message'],
                ErrorCodes::FILE_AP_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // 3. Tipo de identificación del usuario (columna 2)
        // Valor obligatorio
        if (empty($rowData[2] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AP_ERROR_003['message'],
                ErrorCodes::FILE_AP_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // Únicamente los valores permitidos
        $allowedPrefixes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($rowData[2] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AP_ERROR_004['message'],
                ErrorCodes::FILE_AP_ERROR_004['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // 4. Número de identificación del usuario en el sistema (columna 3)
        // Valor obligatorio
        if (empty($rowData[3] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[3],
                ErrorCodes::FILE_AP_ERROR_005['message'],
                ErrorCodes::FILE_AP_ERROR_005['code'],
                $rowData[3] ?? '',
                $fileName
            );
        }

        // 5. Fecha del procedimiento (columna 4)
        // Valor obligatorio
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AP_ERROR_006['message'],
                ErrorCodes::FILE_AP_ERROR_006['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // 6. Código del procedimiento (columna 6)
        // Valor obligatorio
        if (empty($rowData[6] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_AP_ERROR_007['message'],
                ErrorCodes::FILE_AP_ERROR_007['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // 7. Ámbito de realización del procedimiento (columna 7)
        // Valor obligatorio
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AP_ERROR_008['message'],
                ErrorCodes::FILE_AP_ERROR_008['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // 8. Valor del procedimiento (columna 14)
        // Valor obligatorio
        if (empty($rowData[14] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[14],
                ErrorCodes::FILE_AP_ERROR_009['message'],
                ErrorCodes::FILE_AP_ERROR_009['code'],
                $rowData[14] ?? '',
                $fileName
            );
        }
    }
}
