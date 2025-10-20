<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Str;

class ExcelValidator
{

    public static function validateAll(string $batchId, $xlsCollection, array $requiredColumns)
    {
        $errors = false;

        //Validando que el excel no este vacio
        if ($xlsCollection->isEmpty()) {

            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('RIP_EXCEL_007'),
                ErrorCodes::RIP_EXCEL_007['code'],
                null,
                ''
            );
            $errors = true;
        }

        // Empieza la validación de columnas
        // Normaliza y verifica columnas requeridas
        $normalize = fn($s) => Str::of($s)->lower()->replace(' ', '')->toString();
        // Obtener las claves del primer elemento y normalizarlas
        $headers = collect(array_keys($xlsCollection->first()))->map($normalize);
        // Comparar con las columnas requeridas
        $missing = collect($requiredColumns)->diff($headers);
        if ($missing->isNotEmpty()) {
            // Convierte claves faltantes a nombres legibles
            $cols = $missing->map(fn($k) => $k)->values()->all();

            // Formatea: "a, b y c"
            $last = array_pop($cols);
            $colsStr = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('RIP_EXCEL_008', $colsStr),
                ErrorCodes::RIP_EXCEL_008['code'],
                null,
                ''
            );
            $errors = true;
        }
        return $errors;
    }
}
