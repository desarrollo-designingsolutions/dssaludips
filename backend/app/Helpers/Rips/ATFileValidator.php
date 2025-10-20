<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class ATFileValidator
{
    /**
     * Valida el archivo AT y sus columnas.
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
            'Columna 6: Tipo de servicio',
            'Columna 7: Código del servicio',
            'Columna 8: Nombre del servicio',
            'Columna 9: Cantidad',
            'Columna 10: Valor unitario del material e insumo',
            'Columna 11: Valor total del material e insumo',
        ];

        // 1. Número de la factura (columna 0)
        // Valor obligatorio
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AT_ERROR_001['message'],
                ErrorCodes::FILE_AT_ERROR_001['code'],
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
                ErrorCodes::FILE_AT_ERROR_002['message'],
                ErrorCodes::FILE_AT_ERROR_002['code'],
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
                ErrorCodes::FILE_AT_ERROR_003['message'],
                ErrorCodes::FILE_AT_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // 4. Tipo de servicio (columna 5)
        // Valor obligatorio
        if (empty($rowData[5] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[5],
                ErrorCodes::FILE_AT_ERROR_004['message'],
                ErrorCodes::FILE_AT_ERROR_004['code'],
                $rowData[5] ?? '',
                $fileName
            );
        }

        // 5. Nombre del servicio (columna 7)
        // Valor obligatorio
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AT_ERROR_005['message'],
                ErrorCodes::FILE_AT_ERROR_005['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }
    }
}
