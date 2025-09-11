<?php

namespace App\Helpers\Rips;

class FormatDataTxt
{
    public static function execute($contenido, $function = null, $delimitador = ",")
    {
         $dataArray = [];
        $lineas = explode("\n", $contenido);

        foreach ($lineas as $linea) {
            // Ignorar líneas vacías o que solo contengan espacios
            if (trim($linea) === '') {
                continue;
            }

            $datos = explode($delimitador, $linea);

            if ($function) {
                $dataArray[] = $function($datos);
            } else {
                $dataArray[] = $datos;
            }
        }

        return $dataArray;
    }
}
