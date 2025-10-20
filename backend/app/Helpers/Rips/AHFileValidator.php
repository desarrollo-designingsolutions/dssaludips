<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class AHFileValidator
{
    /**
     * Valida el archivo AH y sus columnas.
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
            'Columna 5: Vía de ingreso a la institución',
            'Columna 6: Fecha de ingreso del usuario a la institución',
            'Columna 7: Hora de ingreso del usuario a la institución',
            'Columna 8: Número de autorización',
            'Columna 9: Causa externa',
            'Columna 10: Diagnóstico principal de ingreso',
            'Columna 11: Diagnóstico principal de egreso',
            'Columna 12: Diagnóstico relacionado Nro. 1 de egreso',
            'Columna 13: Diagnóstico relacionado Nro. 2 de egreso',
            'Columna 14: Diagnóstico relacionado Nro. 3 de egreso',
            'Columna 15: Diagnóstico de la complicación',
            'Columna 16: Estado a la salida',
            'Columna 17: Diagnóstico de la causa básica de muerte',
            'Columna 18: Fecha de egreso del usuario a la institución',
            'Columna 19: Hora de egreso del usuario de la institución',
        ];

        // 1. Número de la factura (columna 0)
        // Valor obligatorio
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AH_ERROR_001['message'],
                ErrorCodes::FILE_AH_ERROR_001['code'],
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
                ErrorCodes::FILE_AH_ERROR_002['message'],
                ErrorCodes::FILE_AH_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // 3. Vía de ingreso a la institución (columna 4)
        // Valor obligatorio
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AH_ERROR_003['message'],
                ErrorCodes::FILE_AH_ERROR_003['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // Únicamente los valores permitidos
        $allowedPrefixes = ['1', '2', '3', '4'];
        if (!in_array($rowData[4] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AH_ERROR_004['message'],
                ErrorCodes::FILE_AH_ERROR_004['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // 4. Fecha de ingreso del usuario a la institución (columna 5)
        // Valor obligatorio
        if (empty($rowData[5] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[5],
                ErrorCodes::FILE_AH_ERROR_005['message'],
                ErrorCodes::FILE_AH_ERROR_005['code'],
                $rowData[5] ?? '',
                $fileName
            );
        }

        // 5. Hora de ingreso del usuario a la institución (columna 6)
        // Valor obligatorio
        if (empty($rowData[6] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_AH_ERROR_006['message'],
                ErrorCodes::FILE_AH_ERROR_006['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // 6. Causa externa (columna 7)
        // Valor obligatorio
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AH_ERROR_007['message'],
                ErrorCodes::FILE_AH_ERROR_007['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // Únicamente los valores permitidos
        $allowedPrefixes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if (!in_array($rowData[7] ?? '', $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AH_ERROR_008['message'],
                ErrorCodes::FILE_AH_ERROR_008['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // 7. Fecha de egreso del usuario a la institución (columna 16)
        // Valor obligatorio
        if (empty($rowData[16] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[16],
                ErrorCodes::FILE_AH_ERROR_009['message'],
                ErrorCodes::FILE_AH_ERROR_009['code'],
                $rowData[16] ?? '',
                $fileName
            );
        }

        // 8. Hora de egreso del usuario de la institución (columna 17)
        // Valor obligatorio
        if (empty($rowData[17] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[17],
                ErrorCodes::FILE_AH_ERROR_010['message'],
                ErrorCodes::FILE_AH_ERROR_010['code'],
                $rowData[17] ?? '',
                $fileName
            );
        }
    }
}
