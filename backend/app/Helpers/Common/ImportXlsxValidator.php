<?php

namespace App\Helpers\Common;

use App\Helpers\Common\ErrorCollector;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportXlsxValidator
{
    /**
     * Columnas esperadas (en el mismo idioma/forma que llegarán en el archivo).
     * Se valida de forma flexible: trim, minúsculas y espacios colapsados.
     */
    protected static array $expectedHeaders = [
        'tipo de documento',
        'documento',
        'tipo de usuario',
        'fecha de nacimiento',
        'sexo',
        'pais de residencia',
        'municipio de residencia',
        'zona territorial de residencia',
        'incapacidad',
        'pais de origen',
        'primer nombre',
        'segundo nombre',
        'primer apellido',
        'segundo apellido',
    ];

    /**
     * Valida únicamente la cabecera (primera fila) de un XLSX.
     *
     * @param  string $filePath       Ruta del archivo XLSX en disco.
     * @param  string $keyErrorRedis  Clave para almacenar errores en Redis.
     * @param  string $prefix         Prefijo opcional para mensajes o trazas.
     * @return bool                   true si la cabecera es válida; false en caso contrario.
     */
    public static function validate(
        string $batchId,
        string $filePath,
    ): bool {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Detectar última columna con datos (en la fila 1)
            $lastCol = $sheet->getHighestColumn();
            if (!$lastCol) {
                ErrorCollector::addError(
                    $batchId,
                    0,
                    null,
                    'No se pudo detectar la cabecera. Verifique que la primera fila tenga columnas.',
                    'PATIENT_ERROR_001',
                    'No se pudo detectar la cabecera',
                    null,
                );
                return false;
            }

            // Obtener solo la fila 1 (cabecera)
            $headerRow = $sheet->rangeToArray('A1:' . $lastCol . '1', null, true, false)[0] ?? [];
        } catch (\Exception $e) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                'No se pudo leer el archivo Excel. Verifique que el archivo esté bien formado.',
                'PATIENT_ERROR_002',
                'No se pudo leer el archivo Excel',
                null,
            );
            return false;
        }

        // Normalización: trim, minúsculas y colapso de espacios internos
        $normalize = static function ($v): string {
            if (!is_string($v)) return '';
            $v = mb_strtolower(trim($v));
            $v = str_replace(['_', '-'], ' ', $v);        // ← aquí aceptamos underscores
            $v = preg_replace('/\s+/', ' ', $v);          // colapsa espacios
            return $v ?? '';
        };

        $headers = array_map($normalize, $headerRow);
        // Remover columnas vacías al final, si las hay
        $headers = array_values(array_filter($headers, fn($h) => $h !== ''));

        $expected = array_map($normalize, self::$expectedHeaders);

        // Detectar faltantes y desconocidos (orden no importa)
        $missingHeaders = array_values(array_diff($expected, $headers));
        $extraHeaders   = array_values(array_diff($headers, $expected));

        if (!empty($missingHeaders) || !empty($extraHeaders)) {
            $errorMessage = '';
            $columnName = null;
            if (!empty($missingHeaders)) {
                $errorMessage .= 'Faltan las siguientes columnas: ' . implode(', ', $missingHeaders) . '. ';
                $columnName = implode(',', $missingHeaders);
            }
            if (!empty($extraHeaders)) {
                $errorMessage .= 'Estas columnas no son reconocidas: ' . implode(', ', $extraHeaders) . '.';
                $columnName = ($columnName ? $columnName . ';' : '') . implode(',', $extraHeaders);
            }

            ErrorCollector::addError(
                $batchId,
                1,
                null,
                trim($errorMessage),
                'PATIENT_ERROR_003',
                'Las columnas no coinciden con el formato esperado',
                null,
            );
            return false;
        }

        // Si llegamos aquí, la cabecera es válida
        return true;
    }
}
