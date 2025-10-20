<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class AUFileValidator
{
    /**
     * Valida el archivo AU y sus columnas.
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
            'Columna 5: Fecha de ingreso del usuario a observación',
            'Columna 6: Hora de ingreso del usuario a observación',
            'Columna 7: Número de autorización',
            'Columna 8: Causa externa',
            'Columna 9: Diagnóstico a la salida',
            'Columna 10: Diagnóstico relacionado Nro. 1 a la salida',
            'Columna 11: Diagnóstico relacionado Nro. 2 a la salida',
            'Columna 12: Diagnóstico relacionado Nro. 3 a la salida',
            'Columna 13: Destino del usuario a la salida de observación',
            'Columna 14: Estado a la salida',
            'Columna 15: Causa básica de muerte en urgencias',
            'Columna 16: Fecha de la salida del usuario en observación',
            'Columna 17: Hora de la salida del usuario en observación',
        ];

        // VALIDAR Número de la factura
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AU_ERROR_001['message'],
                ErrorCodes::FILE_AU_ERROR_001['code'],
                $rowData[0] ?? '',
                $fileName
            );
        }

        // VALIDAR Código del prestador de servicios de salud
        if (empty($rowData[1] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_AU_ERROR_002['message'],
                ErrorCodes::FILE_AU_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // VALIDAR Tipo de identificación del usuario
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($rowData[2] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AU_ERROR_003['message'],
                ErrorCodes::FILE_AU_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // VALIDAR Fecha de ingreso del usuario a observación
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AU_ERROR_004['message'],
                ErrorCodes::FILE_AU_ERROR_004['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // VALIDAR Hora de ingreso del usuario a observación
        if (empty($rowData[5] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[5],
                ErrorCodes::FILE_AU_ERROR_005['message'],
                ErrorCodes::FILE_AU_ERROR_005['code'],
                $rowData[5] ?? '',
                $fileName
            );
        }

        // VALIDAR Causa externa
        $allowedTypes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if (!in_array($rowData[7] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AU_ERROR_006['message'],
                ErrorCodes::FILE_AU_ERROR_006['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AU_ERROR_007['message'],
                ErrorCodes::FILE_AU_ERROR_007['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // VALIDAR Diagnóstico a la salida
        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AU_ERROR_008['message'],
                ErrorCodes::FILE_AU_ERROR_008['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        // VALIDAR Destino del usuario a la salida de observación
        if (empty($rowData[12] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[12],
                ErrorCodes::FILE_AU_ERROR_009['message'],
                ErrorCodes::FILE_AU_ERROR_009['code'],
                $rowData[12] ?? '',
                $fileName
            );
        }

        // VALIDAR Fecha de la salida del usuario en observación
        if (empty($rowData[15] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[15],
                ErrorCodes::FILE_AU_ERROR_010['message'],
                ErrorCodes::FILE_AU_ERROR_010['code'],
                $rowData[15] ?? '',
                $fileName
            );
        }

        // VALIDAR Hora de la salida del usuario en observación
        if (empty($rowData[16] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[16],
                ErrorCodes::FILE_AU_ERROR_011['message'],
                ErrorCodes::FILE_AU_ERROR_011['code'],
                $rowData[16] ?? '',
                $fileName
            );
        }
    }
}
