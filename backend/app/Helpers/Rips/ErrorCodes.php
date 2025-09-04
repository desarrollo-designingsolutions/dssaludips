<?php

namespace App\Helpers\Rips;

class ErrorCodes
{
    // Errores de estructura en ZIP
    const ZIP_STR_001 = ['code' => 'ZIP_STR_001', 'message' => 'El archivo no es un ZIP válido.'];
    const ZIP_STR_002 = ['code' => 'ZIP_STR_002', 'message' => 'El ZIP contiene carpetas (no permitido).'];
    const ZIP_STR_003 = ['code' => 'ZIP_STR_003', 'message' => 'El ZIP no tiene la cantidad mínima de archivos TXT (se requieren al menos 4).'];
    const ZIP_STR_004 = ['code' => 'ZIP_STR_004', 'message' => 'El ZIP tiene más archivos TXT de los permitidos (máximo 10).'];

    // Errores de validación en ZIP
    const ZIP_VAL_001 = ['code' => 'ZIP_VAL_001', 'message' => 'Falta el archivo AF requerido.'];
    const ZIP_VAL_002 = ['code' => 'ZIP_VAL_002', 'message' => 'Falta el archivo US requerido.'];
    const ZIP_VAL_003 = ['code' => 'ZIP_VAL_003', 'message' => 'Falta el archivo CT requerido.'];
    const ZIP_VAL_004 = ['code' => 'ZIP_VAL_004', 'message' => 'Falta al menos uno de los archivos AC/AP/AM/AT.'];

    // Errores de formato en ZIP
    const ZIP_FMT_001 = ['code' => 'ZIP_FMT_001', 'message' => 'El ZIP contiene archivos con extensiones no permitidas (solo se permite TXT).'];

    // Errores de contenido en TXT (para archivos dentro del ZIP)
    const TXT_STR_001 = ['code' => 'TXT_STR_001', 'message' => 'El archivo TXT está vacío.'];
    // Errores de validación en TXT (para longitud de elementos)
    const TXT_VAL_002 = ['code' => 'TXT_VAL_002', 'message' => 'El elemento en la línea %d debe tener %d elementos y tiene %d.'];


    // Método para obtener el mensaje formateado
    public static function getMessage(string $code): string
    {
        $error = constant("self::$code");
        return $error['message'];
    }

    // Método para obtener todos los códigos de error (opcional, para documentación)
    public static function getAllErrorCodes(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        return array_filter($constants, function ($value) {
            return is_array($value) && isset($value['code']);
        });
    }
}
