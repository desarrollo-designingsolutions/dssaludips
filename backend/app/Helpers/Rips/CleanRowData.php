<?php

namespace App\Helpers\Rips;

class CleanRowData
{
    /**
     * Limpia todos los elementos de tipo cadena en un array aplicando trim.
     *
     * @param array $rowData Array con los datos de una fila
     * @return array Array con los elementos de tipo cadena limpiados
     */
    public static function execute(array $rowData): array
    {
        return array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $rowData);
    }
}
