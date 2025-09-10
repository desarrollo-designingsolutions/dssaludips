<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;
use App\Events\ImportProgressEvent;
use Illuminate\Support\Facades\Log;

class USFileValidator
{
    /**
     * Valida el archivo US y sus columnas.
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
            'Columna 1: Tipo de identificación del usuario.',
            'Columna 2: Número de identificación del usuario del sistema.',
            'Columna 3: Código entidad administradora.',
            'Columna 4: Tipo de usuario.',
            'Columna 5: Primer apellido del usuario.',
            'Columna 6: Segundo apellido del usuario.',
            'Columna 7: Primer nombre del usuario.',
            'Columna 8: Segundo nombre del usuario.',
            'Columna 9: Edad.',
            'Columna 10: Unidad de medida de la edad.',
            'Columna 11: Sexo.',
            'Columna 12: Código del departamento de residencia habitual.',
            'Columna 13: Código del municipio de residencia habitual.',
            'Columna 14: Zona de residencia habitual.',
        ];

        // Validar columna 0: Tipo de identificación del usuario
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        $typeId = trim($rowData[0] ?? '');
        $idNumber = trim($rowData[1] ?? ''); // Columna 2: Número de identificación
        $ageUnit = trim($rowData[9] ?? ''); // Columna 10: Unidad de medida

        // Verificar si el tipo de identificación es válido
        if (!in_array($typeId, $allowedTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_US_ERROR_001['message'],
                ErrorCodes::FILE_US_ERROR_001['code'],
                $typeId,
                $fileName
            );
        } else {
            // Validaciones específicas por tipo de identificación
            switch ($typeId) {
                case 'CC':
                    if ($ageUnit !== '1') {
                        ErrorCollector::addError(
                            $batchId,
                            $rowNumber,
                            $titleColumn[9],
                            ErrorCodes::FILE_US_ERROR_003['message'],
                            ErrorCodes::FILE_US_ERROR_003['code'],
                            $ageUnit,
                            $fileName
                        );
                    }
                    break;
                case 'CE':
                    if ($ageUnit !== '1') {
                        ErrorCollector::addError(
                            $batchId,
                            $rowNumber,
                            $titleColumn[9],
                            ErrorCodes::FILE_US_ERROR_004['message'],
                            ErrorCodes::FILE_US_ERROR_004['code'],
                            $ageUnit,
                            $fileName
                        );
                    }
                    break;
                case 'TI':
                    if ($ageUnit !== '1') {
                        ErrorCollector::addError(
                            $batchId,
                            $rowNumber,
                            $titleColumn[9],
                            ErrorCodes::FILE_US_ERROR_006['message'],
                            ErrorCodes::FILE_US_ERROR_006['code'],
                            $ageUnit,
                            $fileName
                        );
                    }
                    break;
                case 'CN':
                    if ($ageUnit !== '3') {
                        ErrorCollector::addError(
                            $batchId,
                            $rowNumber,
                            $titleColumn[9],
                            ErrorCodes::FILE_US_ERROR_007['message'],
                            ErrorCodes::FILE_US_ERROR_007['code'],
                            $ageUnit,
                            $fileName
                        );
                    }
                    break;
                case 'AS':
                    if ($ageUnit !== '1') {
                        ErrorCollector::addError(
                            $batchId,
                            $rowNumber,
                            $titleColumn[9],
                            ErrorCodes::FILE_US_ERROR_008['message'],
                            ErrorCodes::FILE_US_ERROR_008['code'],
                            $ageUnit,
                            $fileName
                        );
                    }
                    break;
            }
        }

        // Validar Número de identificación del usuario del sistema
        if (in_array($typeId, ['CC', 'TI']) && !empty($idNumber) && !ctype_digit($idNumber)) {
            $errorCode = $typeId == 'CC' ? ErrorCodes::FILE_US_ERROR_002 : ErrorCodes::FILE_US_ERROR_017;
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                $errorCode['message'],
                $errorCode['code'],
                $idNumber,
                $fileName
            );
        }

        // Validar longitud del número de identificación según el tipo
        $lengthRules = [
            'CC' => ['max' => 10, 'code' => ErrorCodes::FILE_US_ERROR_009],
            'CE' => ['max' => 6, 'code' => ErrorCodes::FILE_US_ERROR_010],
            'CD' => ['max' => 16, 'code' => ErrorCodes::FILE_US_ERROR_011],
            'PA' => ['max' => 16, 'code' => ErrorCodes::FILE_US_ERROR_012],
            'SC' => ['max' => 16, 'code' => ErrorCodes::FILE_US_ERROR_013],
            'PE' => ['max' => 15, 'code' => ErrorCodes::FILE_US_ERROR_014],
            'RE' => ['max' => 15, 'code' => ErrorCodes::FILE_US_ERROR_015],
            'RC' => ['max' => 11, 'code' => ErrorCodes::FILE_US_ERROR_016],
            'TI' => ['max' => 11, 'code' => ErrorCodes::FILE_US_ERROR_018],
            'CN' => ['max' => 9, 'code' => ErrorCodes::FILE_US_ERROR_019],
            'AS' => ['max' => 10, 'code' => ErrorCodes::FILE_US_ERROR_020],
            'MS' => ['max' => 12, 'code' => ErrorCodes::FILE_US_ERROR_021],
        ];
        if (isset($lengthRules[$typeId]) && !empty($idNumber) && strlen($idNumber) > $lengthRules[$typeId]['max']) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                $lengthRules[$typeId]['code']['message'],
                $lengthRules[$typeId]['code']['code'],
                $idNumber,
                $fileName
            );
        }

        // Validar Tipo de usuario
        $allowedUserTypes = [1, 2, 3, 4, 5, 6, 7, 8];
        if (!in_array($rowData[3] ?? '', $allowedUserTypes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[3],
                ErrorCodes::FILE_US_ERROR_023['message'],
                ErrorCodes::FILE_US_ERROR_023['code'],
                $rowData[3] ?? '',
                $fileName
            );
        }

        // Validar Primer apellido del usuario
        if (empty($rowData[4] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[4],
                ErrorCodes::FILE_US_ERROR_024['message'],
                ErrorCodes::FILE_US_ERROR_024['code'],
                $rowData[4] ?? '',
                $fileName
            );
        }

        // Validar Primer nombre del usuario
        if (empty($rowData[6] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[6],
                ErrorCodes::FILE_US_ERROR_025['message'],
                ErrorCodes::FILE_US_ERROR_025['code'],
                $rowData[6] ?? '',
                $fileName
            );
        }

        // Validar Edad
        if (empty($rowData[8] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[8],
                ErrorCodes::FILE_US_ERROR_026['message'],
                ErrorCodes::FILE_US_ERROR_026['code'],
                $rowData[8] ?? '',
                $fileName
            );
        }

        // Validar Unidad de medida de la edad
        $allowedAgeUnits = [1, 2, 3];
        if (!in_array($rowData[9] ?? '', $allowedAgeUnits)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[9],
                ErrorCodes::FILE_US_ERROR_028['message'],
                ErrorCodes::FILE_US_ERROR_028['code'],
                $rowData[9] ?? '',
                $fileName
            );
        }
        if (empty($rowData[9] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[9],
                ErrorCodes::FILE_US_ERROR_029['message'],
                ErrorCodes::FILE_US_ERROR_029['code'],
                $rowData[9] ?? '',
                $fileName
            );
        }

        // Validar Sexo
        $allowedSexes = ['M', 'F'];
        if (empty($rowData[10] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[10],
                ErrorCodes::FILE_US_ERROR_030['message'],
                ErrorCodes::FILE_US_ERROR_030['code'],
                $rowData[10] ?? '',
                $fileName
            );
        } elseif (!in_array($rowData[10] ?? '', $allowedSexes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[10],
                ErrorCodes::FILE_US_ERROR_031['message'],
                ErrorCodes::FILE_US_ERROR_031['code'],
                $rowData[10] ?? '',
                $fileName
            );
        }

        // Validar Código del departamento de residencia habitual
        if (empty($rowData[11] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[11],
                ErrorCodes::FILE_US_ERROR_032['message'],
                ErrorCodes::FILE_US_ERROR_032['code'],
                $rowData[11] ?? '',
                $fileName
            );
        }

        // Validar Código del municipio de residencia habitual
        if (empty($rowData[12] ?? '')) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[12],
                ErrorCodes::FILE_US_ERROR_033['message'],
                ErrorCodes::FILE_US_ERROR_033['code'],
                $rowData[12] ?? '',
                $fileName
            );
        }

        // Validar Zona de residencia habitual
        $allowedZones = ['U', 'R'];
        if (empty($rowData[13])) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[13],
                ErrorCodes::FILE_US_ERROR_034['message'],
                ErrorCodes::FILE_US_ERROR_034['code'],
                $rowData[13],
                $fileName
            );
        } elseif (!in_array($rowData[13], $allowedZones,true)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[13],
                ErrorCodes::FILE_US_ERROR_035['message'],
                ErrorCodes::FILE_US_ERROR_035['code'],
                $rowData[13],
                $fileName
            );
        }
    }
}
