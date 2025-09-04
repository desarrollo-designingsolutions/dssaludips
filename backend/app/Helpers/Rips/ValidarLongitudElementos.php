<?php
namespace App\Helpers\Rips;

use App\Helpers\Rips\ErrorCodes;
use App\Helpers\Common\ErrorCollector;

class ValidarLongitudElementos
{
    public static function execute(&$array, $file_name, $cantidadEsperada, $batchId)
    {
        foreach ($array as $key => &$elemento) {
            $cantidadActual = count($elemento);
            if ($cantidadActual !== $cantidadEsperada) {
                ErrorCollector::addError(
                    $batchId,
                    $key + 1,
                    null,
                    sprintf(
                        ErrorCodes::getMessage('TXT_VAL_002'),
                        $key + 1,
                        $cantidadEsperada,
                        $cantidadActual
                    ),
                    ErrorCodes::TXT_VAL_002['code'],
                    null,
                    $file_name
                );
            }
        }
    }
}
