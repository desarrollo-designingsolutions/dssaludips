<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class AMFileValidator
{
    /**
     * Valida el archivo AM y sus columnas.
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
            'Columna 5: Número de autorización',
            'Columna 6: Código del medicamento',
            'Columna 7: Tipo de medicamento',
            'Columna 8: Nombre genérico del medicamento',
            'Columna 9: Forma farmacéutica',
            'Columna 10: Concentración del medicamento',
            'Columna 11: Unidad de medida del medicamento',
            'Columna 12: Número de unidades',
            'Columna 13: Valor unitario de medicamento',
            'Columna 14: Valor total de medicamento',
        ];

        // 1. Número de la factura (columna 0)
        // Valor obligatorio
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AM_ERROR_001['message'],
                ErrorCodes::FILE_AM_ERROR_001['code'],
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
                ErrorCodes::FILE_AM_ERROR_002['message'],
                ErrorCodes::FILE_AM_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // 3. Tipo de identificación del usuario (columna 2)
        // Únicamente los valores permitidos
        $allowedPrefixes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($rowData[2] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AM_ERROR_003['message'],
                ErrorCodes::FILE_AM_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // 4. Tipo de medicamento (columna 7)
        // Valor obligatorio
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AM_ERROR_004['message'],
                ErrorCodes::FILE_AM_ERROR_004['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // Únicamente los valores permitidos
        $allowedPrefixes = ['1', '2'];
        if (!in_array($rowData[7] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AM_ERROR_005['message'],
                ErrorCodes::FILE_AM_ERROR_005['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // 5. Nombre genérico del medicamento (columna 8)
        // Valor obligatorio
        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AM_ERROR_006['message'],
                ErrorCodes::FILE_AM_ERROR_006['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        // 6. Forma farmacéutica (columna 9)
        // Valor obligatorio
        if (empty($rowData[9] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[9],
                ErrorCodes::FILE_AM_ERROR_007['message'],
                ErrorCodes::FILE_AM_ERROR_007['code'],
                $rowData[9] ?? '',
                $fileName
            );
        }

        // 7. Concentración del medicamento (columna 10)
        // Valor obligatorio
        if (empty($rowData[10] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[10],
                ErrorCodes::FILE_AM_ERROR_008['message'],
                ErrorCodes::FILE_AM_ERROR_008['code'],
                $rowData[10] ?? '',
                $fileName
            );
        }

        // 8. Unidad de medida del medicamento (columna 11)
        // Valor obligatorio
        if (empty($rowData[11] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[11],
                ErrorCodes::FILE_AM_ERROR_009['message'],
                ErrorCodes::FILE_AM_ERROR_009['code'],
                $rowData[11] ?? '',
                $fileName
            );
        }

        // 9. Número de unidades (columna 12)
        // Valor obligatorio
        if (empty($rowData[12] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[12],
                ErrorCodes::FILE_AM_ERROR_010['message'],
                ErrorCodes::FILE_AM_ERROR_010['code'],
                $rowData[12] ?? '',
                $fileName
            );
        }

        // 10. Valor total de medicamento (columna 13)
        // Valor obligatorio
        if (empty($rowData[13] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[13],
                ErrorCodes::FILE_AM_ERROR_011['message'],
                ErrorCodes::FILE_AM_ERROR_011['code'],
                $rowData[13] ?? '',
                $fileName
            );
        }
    }
}
