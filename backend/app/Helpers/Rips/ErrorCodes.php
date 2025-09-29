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

    // Errores de archivo CT
    const FILE_CT_ERROR_001 = ['code' => 'FILE_CT_ERROR_001', 'message' => 'El valor registrado no es numérico.'];
    const FILE_CT_ERROR_002 = ['code' => 'FILE_CT_ERROR_002', 'message' => 'El dato registrado contiene una longitud diferente a 12 caracteres.'];
    const FILE_CT_ERROR_003 = ['code' => 'FILE_CT_ERROR_003', 'message' => 'El dato registrado no usa el formato de fecha establecido.'];
    const FILE_CT_ERROR_004 = ['code' => 'FILE_CT_ERROR_004', 'message' => 'La fecha registrada es mayor a la fecha actual.'];
    const FILE_CT_ERROR_005 = ['code' => 'FILE_CT_ERROR_005', 'message' => 'El código de archivo no cumple con el formato permitido.'];
    const FILE_CT_ERROR_006 = ['code' => 'FILE_CT_ERROR_006', 'message' => 'El código de archivo solo puede ser registrado una vez por cada tipo.'];
    const FILE_CT_ERROR_007 = ['code' => 'FILE_CT_ERROR_007', 'message' => 'El valor registrado no es numérico.'];
    const FILE_CT_ERROR_008 = ['code' => 'FILE_CT_ERROR_008', 'message' => 'No se encontró el archivo correspondiente al código %s. Verifique que exista en el ZIP.'];
    const FILE_CT_ERROR_009 = ['code' => 'FILE_CT_ERROR_009', 'message' => 'El total de registros (%s) no coincide con las filas encontradas (%s) en el archivo %s. Ajuste el valor o el archivo.'];

    // Errores de archivo US
    const FILE_US_ERROR_001 = ['code' => 'FILE_US_ERROR_001', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_US_ERROR_002 = ['code' => 'FILE_US_ERROR_002', 'message' => 'El número de identificación del usuario es obligatorio.'];
    const FILE_US_ERROR_003 = ['code' => 'FILE_US_ERROR_003', 'message' => 'La unidad de medida de la edad debe ser 1 para el tipo de identificación CC.'];
    const FILE_US_ERROR_004 = ['code' => 'FILE_US_ERROR_004', 'message' => 'La unidad de medida de la edad debe ser 1 para el tipo de identificación CE.'];
    const FILE_US_ERROR_006 = ['code' => 'FILE_US_ERROR_006', 'message' => 'La unidad de medida de la edad debe ser 1 para el tipo de identificación TI.'];
    const FILE_US_ERROR_007 = ['code' => 'FILE_US_ERROR_007', 'message' => 'La unidad de medida de la edad debe ser 3 para el tipo de identificación CN.'];
    const FILE_US_ERROR_008 = ['code' => 'FILE_US_ERROR_008', 'message' => 'La unidad de medida de la edad debe ser 1 para el tipo de identificación AS.'];
    const FILE_US_ERROR_009 = ['code' => 'FILE_US_ERROR_009', 'message' => 'El número de identificación excede la longitud máxima de 10 caracteres para CC.'];
    const FILE_US_ERROR_010 = ['code' => 'FILE_US_ERROR_010', 'message' => 'El número de identificación excede la longitud máxima de 6 caracteres para CE.'];
    const FILE_US_ERROR_011 = ['code' => 'FILE_US_ERROR_011', 'message' => 'El número de identificación excede la longitud máxima de 16 caracteres para CD.'];
    const FILE_US_ERROR_012 = ['code' => 'FILE_US_ERROR_012', 'message' => 'El número de identificación excede la longitud máxima de 16 caracteres para PA.'];
    const FILE_US_ERROR_013 = ['code' => 'FILE_US_ERROR_013', 'message' => 'El número de identificación excede la longitud máxima de 16 caracteres para SC.'];
    const FILE_US_ERROR_014 = ['code' => 'FILE_US_ERROR_014', 'message' => 'El número de identificación excede la longitud máxima de 15 caracteres para PE.'];
    const FILE_US_ERROR_015 = ['code' => 'FILE_US_ERROR_015', 'message' => 'El número de identificación excede la longitud máxima de 15 caracteres para RE.'];
    const FILE_US_ERROR_016 = ['code' => 'FILE_US_ERROR_016', 'message' => 'El número de identificación excede la longitud máxima de 11 caracteres para RC.'];
    const FILE_US_ERROR_017 = ['code' => 'FILE_US_ERROR_017', 'message' => 'El número de identificación debe ser numérico para el tipo de identificación TI.'];
    const FILE_US_ERROR_018 = ['code' => 'FILE_US_ERROR_018', 'message' => 'El número de identificación excede la longitud máxima de 11 caracteres para TI.'];
    const FILE_US_ERROR_019 = ['code' => 'FILE_US_ERROR_019', 'message' => 'El número de identificación excede la longitud máxima de 9 caracteres para CN.'];
    const FILE_US_ERROR_020 = ['code' => 'FILE_US_ERROR_020', 'message' => 'El número de identificación excede la longitud máxima de 10 caracteres para AS.'];
    const FILE_US_ERROR_021 = ['code' => 'FILE_US_ERROR_021', 'message' => 'El número de identificación excede la longitud máxima de 12 caracteres para MS.'];
    const FILE_US_ERROR_023 = ['code' => 'FILE_US_ERROR_023', 'message' => 'El tipo de usuario no es un valor permitido.'];
    const FILE_US_ERROR_024 = ['code' => 'FILE_US_ERROR_024', 'message' => 'El primer apellido del usuario es obligatorio.'];
    const FILE_US_ERROR_025 = ['code' => 'FILE_US_ERROR_025', 'message' => 'El primer nombre del usuario es obligatorio.'];
    const FILE_US_ERROR_026 = ['code' => 'FILE_US_ERROR_026', 'message' => 'La edad del usuario es obligatoria.'];
    const FILE_US_ERROR_028 = ['code' => 'FILE_US_ERROR_028', 'message' => 'La unidad de medida de la edad no es un valor permitido.'];
    const FILE_US_ERROR_029 = ['code' => 'FILE_US_ERROR_029', 'message' => 'La unidad de medida de la edad es obligatoria.'];
    const FILE_US_ERROR_030 = ['code' => 'FILE_US_ERROR_030', 'message' => 'El sexo del usuario es obligatorio.'];
    const FILE_US_ERROR_031 = ['code' => 'FILE_US_ERROR_031', 'message' => 'El sexo del usuario no es un valor permitido.'];
    const FILE_US_ERROR_032 = ['code' => 'FILE_US_ERROR_032', 'message' => 'El código del departamento de residencia habitual es obligatorio.'];
    const FILE_US_ERROR_033 = ['code' => 'FILE_US_ERROR_033', 'message' => 'El código del municipio de residencia habitual es obligatorio.'];
    const FILE_US_ERROR_034 = ['code' => 'FILE_US_ERROR_034', 'message' => 'La zona de residencia habitual es obligatoria.'];
    const FILE_US_ERROR_035 = ['code' => 'FILE_US_ERROR_035', 'message' => 'La zona de residencia habitual no es un valor permitido.'];

    // Errores de archivo AU
    const FILE_AU_ERROR_001 = ['code' => 'FILE_AU_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AU_ERROR_002 = ['code' => 'FILE_AU_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AU_ERROR_003 = ['code' => 'FILE_AU_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AU_ERROR_004 = ['code' => 'FILE_AU_ERROR_004', 'message' => 'La fecha de ingreso del usuario a urgencias es obligatoria.'];
    const FILE_AU_ERROR_005 = ['code' => 'FILE_AU_ERROR_005', 'message' => 'La hora de ingreso del usuario a urgencias es obligatoria.'];
    const FILE_AU_ERROR_006 = ['code' => 'FILE_AU_ERROR_006', 'message' => 'El número de autorización es obligatorio.'];
    const FILE_AU_ERROR_007 = ['code' => 'FILE_AU_ERROR_007', 'message' => 'La causa externa es obligatoria.'];
    const FILE_AU_ERROR_008 = ['code' => 'FILE_AU_ERROR_008', 'message' => 'La causa externa no es un valor permitido.'];
    const FILE_AU_ERROR_009 = ['code' => 'FILE_AU_ERROR_009', 'message' => 'El diagnóstico principal de ingreso es obligatorio.'];
    const FILE_AU_ERROR_010 = ['code' => 'FILE_AU_ERROR_010', 'message' => 'El diagnóstico principal de egreso es obligatorio.'];
    const FILE_AU_ERROR_011 = ['code' => 'FILE_AU_ERROR_011', 'message' => 'El diagnóstico de la complicación es obligatorio.'];
    const FILE_AU_ERROR_012 = ['code' => 'FILE_AU_ERROR_012', 'message' => 'El estado a la salida es obligatorio.'];
    const FILE_AU_ERROR_013 = ['code' => 'FILE_AU_ERROR_013', 'message' => 'El estado a la salida no es un valor permitido.'];
    const FILE_AU_ERROR_014 = ['code' => 'FILE_AU_ERROR_014', 'message' => 'La fecha de egreso del usuario de urgencias es obligatoria.'];
    const FILE_AU_ERROR_015 = ['code' => 'FILE_AU_ERROR_015', 'message' => 'La hora de egreso del usuario de urgencias es obligatoria.'];

    // Errores de archivo AT
    const FILE_AT_ERROR_001 = ['code' => 'FILE_AT_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AT_ERROR_002 = ['code' => 'FILE_AT_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AT_ERROR_003 = ['code' => 'FILE_AT_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AT_ERROR_004 = ['code' => 'FILE_AT_ERROR_004', 'message' => 'El número de autorización es obligatorio.'];
    const FILE_AT_ERROR_005 = ['code' => 'FILE_AT_ERROR_005', 'message' => 'El código del servicio es obligatorio.'];
    const FILE_AT_ERROR_006 = ['code' => 'FILE_AT_ERROR_006', 'message' => 'El tipo de servicio no es un valor permitido.'];
    const FILE_AT_ERROR_007 = ['code' => 'FILE_AT_ERROR_007', 'message' => 'El nombre del servicio es obligatorio.'];
    const FILE_AT_ERROR_008 = ['code' => 'FILE_AT_ERROR_008', 'message' => 'La cantidad es obligatoria.'];
    const FILE_AT_ERROR_009 = ['code' => 'FILE_AT_ERROR_009', 'message' => 'El valor unitario es obligatorio.'];
    const FILE_AT_ERROR_010 = ['code' => 'FILE_AT_ERROR_010', 'message' => 'El valor total es obligatorio.'];

    // Errores de archivo AP
    const FILE_AP_ERROR_001 = ['code' => 'FILE_AP_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AP_ERROR_002 = ['code' => 'FILE_AP_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AP_ERROR_003 = ['code' => 'FILE_AP_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AP_ERROR_004 = ['code' => 'FILE_AP_ERROR_004', 'message' => 'El número de autorización es obligatorio.'];
    const FILE_AP_ERROR_005 = ['code' => 'FILE_AP_ERROR_005', 'message' => 'El código del procedimiento es obligatorio.'];
    const FILE_AP_ERROR_006 = ['code' => 'FILE_AP_ERROR_006', 'message' => 'La finalidad del procedimiento no es un valor permitido.'];
    const FILE_AP_ERROR_007 = ['code' => 'FILE_AP_ERROR_007', 'message' => 'El personal que atiende no es un valor permitido.'];
    const FILE_AP_ERROR_008 = ['code' => 'FILE_AP_ERROR_008', 'message' => 'El diagnóstico principal es obligatorio.'];
    const FILE_AP_ERROR_009 = ['code' => 'FILE_AP_ERROR_009', 'message' => 'El diagnóstico de la complicación es obligatorio.'];
    const FILE_AP_ERROR_010 = ['code' => 'FILE_AP_ERROR_010', 'message' => 'La forma de realización del acto quirúrgico no es un valor permitido.'];
    const FILE_AP_ERROR_011 = ['code' => 'FILE_AP_ERROR_011', 'message' => 'El valor del procedimiento es obligatorio.'];

    // Errores de archivo AN
    const FILE_AN_ERROR_001 = ['code' => 'FILE_AN_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AN_ERROR_002 = ['code' => 'FILE_AN_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AN_ERROR_003 = ['code' => 'FILE_AN_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AN_ERROR_004 = ['code' => 'FILE_AN_ERROR_004', 'message' => 'La fecha de nacimiento del recién nacido es obligatoria.'];
    const FILE_AN_ERROR_005 = ['code' => 'FILE_AN_ERROR_005', 'message' => 'La hora de nacimiento del recién nacido es obligatoria.'];
    const FILE_AN_ERROR_006 = ['code' => 'FILE_AN_ERROR_006', 'message' => 'La edad gestacional es obligatoria.'];
    const FILE_AN_ERROR_007 = ['code' => 'FILE_AN_ERROR_007', 'message' => 'El control prenatal no es un valor permitido.'];
    const FILE_AN_ERROR_008 = ['code' => 'FILE_AN_ERROR_008', 'message' => 'El sexo del recién nacido no es un valor permitido.'];
    const FILE_AN_ERROR_009 = ['code' => 'FILE_AN_ERROR_009', 'message' => 'El peso al nacer es obligatorio.'];
    const FILE_AN_ERROR_010 = ['code' => 'FILE_AN_ERROR_010', 'message' => 'El diagnóstico del recién nacido es obligatorio.'];
    const FILE_AN_ERROR_011 = ['code' => 'FILE_AN_ERROR_011', 'message' => 'La causa básica de muerte es obligatoria.'];
    const FILE_AN_ERROR_012 = ['code' => 'FILE_AN_ERROR_012', 'message' => 'La fecha de egreso del recién nacido es obligatoria.'];
    const FILE_AN_ERROR_013 = ['code' => 'FILE_AN_ERROR_013', 'message' => 'La hora de egreso del recién nacido es obligatoria.'];

    // Errores de archivo AM
    const FILE_AM_ERROR_001 = ['code' => 'FILE_AM_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AM_ERROR_002 = ['code' => 'FILE_AM_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AM_ERROR_003 = ['code' => 'FILE_AM_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AM_ERROR_004 = ['code' => 'FILE_AM_ERROR_004', 'message' => 'El tipo de medicamento es obligatorio.'];
    const FILE_AM_ERROR_005 = ['code' => 'FILE_AM_ERROR_005', 'message' => 'El tipo de medicamento no es un valor permitido.'];
    const FILE_AM_ERROR_006 = ['code' => 'FILE_AM_ERROR_006', 'message' => 'El nombre genérico del medicamento es obligatorio.'];
    const FILE_AM_ERROR_007 = ['code' => 'FILE_AM_ERROR_007', 'message' => 'La forma farmacéutica es obligatoria.'];
    const FILE_AM_ERROR_008 = ['code' => 'FILE_AM_ERROR_008', 'message' => 'La concentración del medicamento es obligatoria.'];
    const FILE_AM_ERROR_009 = ['code' => 'FILE_AM_ERROR_009', 'message' => 'La unidad de medida del medicamento es obligatoria.'];
    const FILE_AM_ERROR_010 = ['code' => 'FILE_AM_ERROR_010', 'message' => 'El número de unidades es obligatorio.'];
    const FILE_AM_ERROR_011 = ['code' => 'FILE_AM_ERROR_011', 'message' => 'El valor total del medicamento es obligatorio.'];

    // Errores de archivo AH
    const FILE_AH_ERROR_001 = ['code' => 'FILE_AH_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AH_ERROR_002 = ['code' => 'FILE_AH_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AH_ERROR_003 = ['code' => 'FILE_AH_ERROR_003', 'message' => 'La vía de ingreso a la institución es obligatoria.'];
    const FILE_AH_ERROR_004 = ['code' => 'FILE_AH_ERROR_004', 'message' => 'La vía de ingreso a la institución no es un valor permitido.'];
    const FILE_AH_ERROR_005 = ['code' => 'FILE_AH_ERROR_005', 'message' => 'La fecha de ingreso del usuario a la institución es obligatoria.'];
    const FILE_AH_ERROR_006 = ['code' => 'FILE_AH_ERROR_006', 'message' => 'La hora de ingreso del usuario a la institución es obligatoria.'];
    const FILE_AH_ERROR_007 = ['code' => 'FILE_AH_ERROR_007', 'message' => 'La causa externa es obligatoria.'];
    const FILE_AH_ERROR_008 = ['code' => 'FILE_AH_ERROR_008', 'message' => 'La causa externa no es un valor permitido.'];
    const FILE_AH_ERROR_009 = ['code' => 'FILE_AH_ERROR_009', 'message' => 'La fecha de egreso del usuario de la institución es obligatoria.'];
    const FILE_AH_ERROR_010 = ['code' => 'FILE_AH_ERROR_010', 'message' => 'La hora de egreso del usuario de la institución es obligatoria.'];

    // Errores de archivo AF
    const FILE_AF_ERROR_001 = ['code' => 'FILE_AF_ERROR_001', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AF_ERROR_002 = ['code' => 'FILE_AF_ERROR_002', 'message' => 'El código del prestador debe coincidir con el registrado en el archivo de control.'];
    const FILE_AF_ERROR_003 = ['code' => 'FILE_AF_ERROR_003', 'message' => 'La razón social o apellidos y nombre del prestador de servicios de salud es obligatoria.'];
    const FILE_AF_ERROR_004 = ['code' => 'FILE_AF_ERROR_004', 'message' => 'El tipo de identificación del prestador de servicios de salud es obligatorio.'];
    const FILE_AF_ERROR_005 = ['code' => 'FILE_AF_ERROR_005', 'message' => 'El tipo de identificación del prestador de servicios de salud no es un valor permitido.'];
    const FILE_AF_ERROR_006 = ['code' => 'FILE_AF_ERROR_006', 'message' => 'El número de identificación del prestador es obligatorio.'];
    const FILE_AF_ERROR_007 = ['code' => 'FILE_AF_ERROR_007', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AF_ERROR_008 = ['code' => 'FILE_AF_ERROR_008', 'message' => 'La fecha de expedición de la factura es obligatoria.'];
    const FILE_AF_ERROR_009 = ['code' => 'FILE_AF_ERROR_009', 'message' => 'La fecha de inicio es obligatoria.'];
    const FILE_AF_ERROR_010 = ['code' => 'FILE_AF_ERROR_010', 'message' => 'La fecha final es obligatoria.'];
    const FILE_AF_ERROR_011 = ['code' => 'FILE_AF_ERROR_011', 'message' => 'El código de la entidad administradora es obligatorio.'];

    // Errores de archivo AC
    const FILE_AC_ERROR_001 = ['code' => 'FILE_AC_ERROR_001', 'message' => 'El número de la factura es obligatorio.'];
    const FILE_AC_ERROR_002 = ['code' => 'FILE_AC_ERROR_002', 'message' => 'El código del prestador de servicios de salud es obligatorio.'];
    const FILE_AC_ERROR_003 = ['code' => 'FILE_AC_ERROR_003', 'message' => 'El tipo de identificación del usuario no es un valor permitido.'];
    const FILE_AC_ERROR_004 = ['code' => 'FILE_AC_ERROR_004', 'message' => 'La fecha de la consulta es obligatoria.'];
    const FILE_AC_ERROR_005 = ['code' => 'FILE_AC_ERROR_005', 'message' => 'El código de la consulta no es un valor permitido.'];
    const FILE_AC_ERROR_006 = ['code' => 'FILE_AC_ERROR_006', 'message' => 'La finalidad de la consulta no es un valor permitido.'];
    const FILE_AC_ERROR_007 = ['code' => 'FILE_AC_ERROR_007', 'message' => 'La causa externa no es un valor permitido.'];
    const FILE_AC_ERROR_008 = ['code' => 'FILE_AC_ERROR_008', 'message' => 'La causa externa es obligatoria.'];
    const FILE_AC_ERROR_009 = ['code' => 'FILE_AC_ERROR_009', 'message' => 'El código de diagnóstico principal es obligatorio.'];
    const FILE_AC_ERROR_010 = ['code' => 'FILE_AC_ERROR_010', 'message' => 'El código del diagnóstico relacionado No. 1 es obligatorio.'];
    const FILE_AC_ERROR_011 = ['code' => 'FILE_AC_ERROR_011', 'message' => 'El tipo de diagnóstico principal es obligatorio.'];
    const FILE_AC_ERROR_012 = ['code' => 'FILE_AC_ERROR_012', 'message' => 'El tipo de diagnóstico principal no es un valor permitido.'];
    const FILE_AC_ERROR_013 = ['code' => 'FILE_AC_ERROR_013', 'message' => 'El valor de la consulta es obligatorio.'];
    const FILE_AC_ERROR_014 = ['code' => 'FILE_AC_ERROR_014', 'message' => 'El valor neto a pagar es obligatorio.'];

    //Errores de validaciones XML
    const FILE_XML_ERROR_001 = ['code' => 'FILE_XML_ERROR_001', 'message' => 'No se pudo leer el archivo XML.'];
    const FILE_XML_ERROR_002 = ['code' => 'FILE_XML_ERROR_002', 'message' => 'el ValidationResultCode debe ser el numero 2.'];
    const FILE_XML_ERROR_003 = ['code' => 'FILE_XML_ERROR_003', 'message' => 'El número de la factura informado en RIPS no coincide con el informado en la factura electrónica de venta.'];
    const FILE_XML_ERROR_004 = ['code' => 'FILE_XML_ERROR_004', 'message' => 'El nit del prestador no coincide.'];

    //Errores de validaciones excel
    const RIP_EXCEL_001 = ['code' => 'RIP_EXCEL_001', 'message' => 'Factura seleccionada no existe.'];


    /**
     * Obtiene el mensaje de error asociado a un código de error, con soporte para parámetros dinámicos.
     *
     * @param string $code Código de error (por ejemplo, 'FILE_CT_ERROR_008')
     * @param mixed ...$args Argumentos para formatear el mensaje (por ejemplo, valores para %d o %s)
     * @return string Mensaje de error formateado
     * @throws \InvalidArgumentException Si el código no existe
     */
    public static function getMessage(string $code, ...$args): string
    {
        if (defined("self::$code")) {
            $error = constant("self::$code");
            $message = $error['message'] ?? 'Mensaje de error no definido.';
            return vsprintf($message, $args);
        }
        throw new \InvalidArgumentException("Código de error no encontrado: $code");
    }


    /**
     * Obtiene todos los códigos de error definidos en la clase.
     *
     * @return array Lista de códigos de error con sus mensajes
     */
    public static function getAllErrorCodes(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        return array_filter($constants, function ($value) {
            return is_array($value) && isset($value['code']);
        });
    }
}
