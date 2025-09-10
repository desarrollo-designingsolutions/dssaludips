<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class ANFileValidator
{
    /**
     * Valida el archivo AN y sus columnas.
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
            'Columna 3: Tipo de identificación de la madre',
            'Columna 4: Número de identificación de la madre en el sistema',
            'Columna 5: Fecha de nacimiento del recién nacido',
            'Columna 6: Hora de nacimiento',
            'Columna 7: Edad gestacional',
            'Columna 8: Control prenatal',
            'Columna 9: Sexo',
            'Columna 10: Peso',
            'Columna 11: Diagnóstico del recién nacido',
            'Columna 12: Causa básica de muerte',
            'Columna 13: Fecha de muerte del recién nacido',
            'Columna 14: Hora de muerte del recién nacido',
        ];

        // Validar Número de la factura
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AN_ERROR_001['message'],
                ErrorCodes::FILE_AN_ERROR_001['code'],
                $rowData[0] ?? '',
                $fileName
            );
        }

        // Validar Código del prestador de servicios de salud
        if (empty($rowData[1] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_AN_ERROR_002['message'],
                ErrorCodes::FILE_AN_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // Validar Tipo de identificación de la madre
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($rowData[2] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AN_ERROR_003['message'],
                ErrorCodes::FILE_AN_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // Validar Fecha de nacimiento del recién nacido
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AN_ERROR_004['message'],
                ErrorCodes::FILE_AN_ERROR_004['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // Validar Hora de nacimiento
        if (empty($rowData[5] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[5],
                ErrorCodes::FILE_AN_ERROR_005['message'],
                ErrorCodes::FILE_AN_ERROR_005['code'],
                $rowData[5] ?? '',
                $fileName
            );
        }

        // Validar Edad gestacional
        if (empty($rowData[6] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_AN_ERROR_006['message'],
                ErrorCodes::FILE_AN_ERROR_006['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // Validar Control prenatal
        if (empty($rowData[7] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AN_ERROR_007['message'],
                ErrorCodes::FILE_AN_ERROR_007['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        $allowedTypes = ['1', '2'];
        if (!in_array($rowData[7] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AN_ERROR_008['message'],
                ErrorCodes::FILE_AN_ERROR_008['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // Validar Sexo
        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AN_ERROR_009['message'],
                ErrorCodes::FILE_AN_ERROR_009['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        $allowedTypes = ['1', '2'];
        if (!in_array($rowData[8] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AN_ERROR_010['message'],
                ErrorCodes::FILE_AN_ERROR_010['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        // Validar Peso
        if (empty($rowData[9] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[9],
                ErrorCodes::FILE_AN_ERROR_011['message'],
                ErrorCodes::FILE_AN_ERROR_011['code'],
                $rowData[9] ?? '',
                $fileName
            );
        }
    }
}
