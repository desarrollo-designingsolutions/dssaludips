<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;

class ACFileValidator
{
    /**
     * Valida el archivo AC y sus columnas.
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
            'Columna 5: Fecha de la consulta',
            'Columna 6: Número de autorización',
            'Columna 7: Código de la consulta',
            'Columna 8: Finalidad de la consulta',
            'Columna 9: Causa externa',
            'Columna 10: Código de diagnóstico principal',
            'Columna 11: Código del diagnóstico relacionado No. 1',
            'Columna 12: Código del diagnóstico relacionado No. 2',
            'Columna 13: Código del diagnóstico relacionado No. 3',
            'Columna 14: Tipo de diagnóstico principal',
            'Columna 15: Valor de la consulta',
            'Columna 16: Valor de la cuota moderadora',
            'Columna 17: Valor neto a pagar',
        ];

        // Validar Número de la factura
        if (empty($rowData[0] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_AC_ERROR_001['message'],
                ErrorCodes::FILE_AC_ERROR_001['code'],
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
                ErrorCodes::FILE_AC_ERROR_002['message'],
                ErrorCodes::FILE_AC_ERROR_002['code'],
                $rowData[1] ?? '',
                $fileName
            );
        }

        // Validar Tipo de identificación del usuario
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($rowData[2] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_AC_ERROR_003['message'],
                ErrorCodes::FILE_AC_ERROR_003['code'],
                $rowData[2] ?? '',
                $fileName
            );
        }

        // Validar Fecha de la consulta
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_AC_ERROR_004['message'],
                ErrorCodes::FILE_AC_ERROR_004['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // Validar Código de la consulta
        $allowedTypes = [
            '890201', '890202', '890301', '890302', '890701', '890702', '890101', '890102', '890203', '890204',
            '890304', '890303', '890703', '890704', '890205', '890305', '890105', '890206', '890208', '890207',
            '890209', '890210', '890211', '890212', '890213', '890306', '890307', '890308', '890309', '890310',
            '890311', '890312', '890313', '890402', '890403', '890404', '890405', '890406', '890408', '890409',
            '890410', '890411', '890412', '890413', '890501', '890502', '890503', '890214', '890314', '890284',
            '890285', '890308', '890309', '890384', '890385', '890302', '940100', '940200', '940301', '940700',
            '940900', '941100', '941301', '941400', '942600', '943101', '943102', '943500', '944001', '944002',
            '944101', '944102', '944201', '944202', '944901', '944902', '944903', '944904', '944905', '944906',
            '944910', '944915'
        ];
        if (!in_array($rowData[6] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_AC_ERROR_005['message'],
                ErrorCodes::FILE_AC_ERROR_005['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // Validar Finalidad de la consulta
        $allowedTypes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
        if (!in_array($rowData[7] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[7],
                ErrorCodes::FILE_AC_ERROR_006['message'],
                ErrorCodes::FILE_AC_ERROR_006['code'],
                $rowData[7] ?? '',
                $fileName
            );
        }

        // Validar Causa externa
        $allowedTypes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if (!in_array($rowData[8] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AC_ERROR_007['message'],
                ErrorCodes::FILE_AC_ERROR_007['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_AC_ERROR_008['message'],
                ErrorCodes::FILE_AC_ERROR_008['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        // Validar Código de diagnóstico principal
        if (empty($rowData[9] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[9],
                ErrorCodes::FILE_AC_ERROR_009['message'],
                ErrorCodes::FILE_AC_ERROR_009['code'],
                $rowData[9] ?? '',
                $fileName
            );
        }

        // Validar Tipo de diagnóstico principal
        if (empty($rowData[13] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[13],
                ErrorCodes::FILE_AC_ERROR_011['message'],
                ErrorCodes::FILE_AC_ERROR_011['code'],
                $rowData[13] ?? '',
                $fileName
            );
        }

        $allowedTypes = [1, 2, 3];
        if (!in_array($rowData[13] ?? '', $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[13],
                ErrorCodes::FILE_AC_ERROR_012['message'],
                ErrorCodes::FILE_AC_ERROR_012['code'],
                $rowData[13] ?? '',
                $fileName
            );
        }

        // Validar Valor de la consulta
        if (empty($rowData[14] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[14],
                ErrorCodes::FILE_AC_ERROR_013['message'],
                ErrorCodes::FILE_AC_ERROR_013['code'],
                $rowData[14] ?? '',
                $fileName
            );
        }

        // Validar Valor neto a pagar
        if (empty($rowData[16] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[16],
                ErrorCodes::FILE_AC_ERROR_014['message'],
                ErrorCodes::FILE_AC_ERROR_014['code'],
                $rowData[16] ?? '',
                $fileName
            );
        }
    }
}
