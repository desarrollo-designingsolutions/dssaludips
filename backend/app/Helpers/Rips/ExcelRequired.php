<?php

namespace App\Helpers\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Enums\Rip\RipStatusEnum;
use App\Events\RipRowUpdatedNow;
use App\Models\Rip;
use App\Models\RipInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExcelRequired
{
    public static function openXls($filePath)
    {
        // Construir la ruta absoluta
        $absolutePath = storage_path('app/public/' . $filePath);

        // Verificar si el archivo existe
        if (!file_exists($absolutePath)) {
            throw new \Exception("El archivo no existe en la ruta: " . $absolutePath);
        }

        // Leer el archivo XLS usando Laravel Excel
        $data = Excel::toArray([], $absolutePath);

        // Procesar los datos obtenidos del archivo XLS
        $keys = $data[0][0]; // Los títulos se encuentran en la primera fila
        $excelData = array_slice($data[0], 1); // Eliminar la primera fila (encabezados)

        // Crear una colección con los datos del XLS
        $xlsCollection = collect($excelData)->map(function ($row, $index) use ($keys) {
            $dataWithKeys = array_combine($keys, $row);
            return $dataWithKeys;
        });

        return $xlsCollection;
    }

    public static function groupByNumFactura($csvCollection)
    {
        // Agrupar por 'num_factura'
        $groupedData = $csvCollection->groupBy('num_factura');

        return $groupedData;
    }

    public static function processData($build, $groupedData)
    {
        $buildData = json_decode(collect($build), true);


        //recorremos el array agrupado del csv
        foreach ($groupedData as $key => $group) {
            //buscamos la posicion de la factura que estamos recorriendo
            //en el array general de facturas
            $index = collect($buildData)->search(function ($objeto, $clave) use ($key) {
                return $objeto['numFactura'] == $key;
            });

            //si existe la factura
            if ($index !== false) {
                foreach ($group as $row) {

                    // Verificar si se está validando una factura o un usuario
                    if (!empty($row['num_factura']) && empty($row['num_identificacion'])) {

                        $requiredFields = self::structureAF();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                if (!empty($row['valor'])) {
                                    $buildData[$index][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && empty($row['servicio'])) {

                        $requiredFields = self::structureUS();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {

                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {

                                    $buildData[$index]['usuarios'][$indexU][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'consultas') {

                        $requiredFields = self::structureAC();


                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {

                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {

                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['consultas'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'procedimientos') {

                        $requiredFields = self::structureAP();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {

                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['procedimientos'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'medicamentos') {

                        $requiredFields = self::structureAM();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {

                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {

                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {

                                    $buildData[$index]['usuarios'][$indexU]['servicios']['medicamentos'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'otrosServicios') {

                        $requiredFields = self::structureAT();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {

                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    // dd($buildData[$index]['usuarios'][$indexU]['servicios']['otrosServicios'][$row['id_servicio'] - 1][$valueF]);
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['otrosServicios'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'urgencias') {

                        $requiredFields = self::structureAU();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {
                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['urgencias'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'hospitalizacion') {

                        $requiredFields = self::structureAH();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {
                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['hospitalizacion'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                    if (!empty($row['num_factura']) && !empty($row['num_identificacion']) && $row['servicio'] == 'recienNacidos') {

                        $requiredFields = self::structureAN();

                        //recorremos los dos campos obligatorios
                        foreach ($requiredFields as $keyF => $valueF) {
                            //recorremos internamente la factura del csv
                            //si existen los key pasamos la data del csv al build general
                            if ($row['campo'] == $valueF) {
                                $indexU = collect($buildData[$index]['usuarios'])->search(function ($objeto, $clave) use ($row) {
                                    return $objeto['numDocumentoIdentificacion'] == $row['num_identificacion'];
                                });

                                if (!empty($row['valor'])) {
                                    $buildData[$index]['usuarios'][$indexU]['servicios']['recienNacidos'][$row['id_servicio'] - 1][$valueF] = $row['valor'];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $buildData;
    }

    public static function validateDataFilesExcel($arrayInfo, &$arrayData)
    {
        $errorMessages = [];

        foreach ($arrayInfo as $indexInvoice => $data) {

            // Obtener claves reales de $data = factura raiz
            $clavesReales = self::structureAF();

            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
            $mapeoTipos = [];

            foreach ($clavesReales as $key) {
                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                $valorCasteado = self::castValue($data[$key] ?? null, $tipo);
                $arrayData[$indexInvoice][$key] = $valorCasteado;
            }

            if (isset($data['usuarios']) && count($data['usuarios']) > 0) {
                foreach ($data['usuarios'] as $indexUser => $usuario) {

                    // Obtener claves reales de $usuario
                    $clavesReales = self::structureUS();

                    // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                    $mapeoTipos = [
                        'consecutivo' => "int",
                    ];

                    foreach ($clavesReales as $key) {

                        $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                        $valorCasteado = self::castValue($usuario[$key] ?? null, $tipo);
                        $arrayData[$indexInvoice]['usuarios'][$indexUser][$key] = $valorCasteado;
                    }

                    //CONSULTAS
                    if (isset($usuario['servicios']['consultas']) && count($usuario['servicios']['consultas']) > 0) {
                        foreach ($usuario['servicios']['consultas'] as $indexQuery => $consulta) {

                            // Obtener claves reales de $consulta
                            $clavesReales = array_keys($consulta);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'consecutivo' => "int",
                                'codServicio' => "int",
                                'valorPagoModerador' => "float",
                                'vrServicio' => "float",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($consulta[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery][$key] = $valorCasteado;
                            }
                        }
                    }


                    //PROCEDIMIENTOS
                    if (isset($usuario['servicios']['procedimientos']) && count($usuario['servicios']['procedimientos']) > 0) {
                        foreach ($usuario['servicios']['procedimientos'] as $indexProcedure => $procedimiento) {

                            // Obtener claves reales de $procedimiento
                            $clavesReales = array_keys($procedimiento);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'consecutivo' => "int",
                                'codServicio' => "int",
                                'valorPagoModerador' => "float",
                                'vrServicio' => "float",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($procedimiento[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure][$key] = $valorCasteado;
                            }
                        }
                    }

                    //OTROS SERVICIOS
                    if (isset($usuario['servicios']['otrosServicios']) && count($usuario['servicios']['otrosServicios']) > 0) {
                        foreach ($usuario['servicios']['otrosServicios'] as $indexOtherService => $otrosServicio) {

                            // Obtener claves reales de $otrosServicio
                            $clavesReales = array_keys($otrosServicio);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'cantidadOS' => "int",
                                'consecutivo' => "int",
                                'vrUnitOS' => "float",
                                'valorPagoModerador' => "float",
                                'vrServicio' => "float",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($otrosServicio[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService][$key] = $valorCasteado;
                            }
                        }
                    }

                    //URGENCIAS
                    if (isset($usuario['servicios']['urgencias']) && count($usuario['servicios']['urgencias']) > 0) {
                        foreach ($usuario['servicios']['urgencias'] as $indexUrgency => $urgencia) {

                            // Obtener claves reales de $urgencia
                            $clavesReales = array_keys($urgencia);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'consecutivo' => "int",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($urgencia[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['urgencias'][$indexUrgency][$key] = $valorCasteado;
                            }
                        }
                    }

                    //HOSPITALIZACION
                    if (isset($usuario['servicios']['hospitalizacion']) && count($usuario['servicios']['hospitalizacion']) > 0) {
                        foreach ($usuario['servicios']['hospitalizacion'] as $indexHospitalization => $hospitalizacion) {

                            // Obtener claves reales de $hospitalizacion
                            $clavesReales = array_keys($hospitalizacion);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'consecutivo' => "int",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($hospitalizacion[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['hospitalizacion'][$indexHospitalization][$key] = $valorCasteado;
                            }
                        }
                    }

                    //RECIEN NACIDOS
                    if (isset($usuario['servicios']['recienNacidos']) && count($usuario['servicios']['recienNacidos']) > 0) {
                        foreach ($usuario['servicios']['recienNacidos'] as $indexNewlyBorn => $recienNacido) {

                            // Obtener claves reales de $recienNacido
                            $clavesReales = array_keys($recienNacido);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'consecutivo' => "int",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($recienNacido[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn][$key] = $valorCasteado;
                            }
                        }
                    }

                    //MEDICAMENTOS
                    if (isset($usuario['servicios']['medicamentos']) && count($usuario['servicios']['medicamentos']) > 0) {
                        foreach ($usuario['servicios']['medicamentos'] as $indexMedicine => $medicamento) {

                            // Obtener claves reales de $medicamento
                            $clavesReales = array_keys($medicamento);

                            // Mapeo de tipos (solo para campos específicos; el resto string por defecto)
                            $mapeoTipos = [
                                'vrUnitMedicamento' => "float",
                                'diasTratamiento' => "int",
                                'cantidadMedicamento' => "int",
                                'unidadMinDispensa' => "int",
                                'unidadMedida' => "int",
                                'concentracionMedicamento' => "int",
                                'consecutivo' => "int",
                                "valorPagoModerador" => "float",
                                'vrServicio' => "float",
                            ];

                            foreach ($clavesReales as $key) {
                                $tipo = $mapeoTipos[$key] ?? "string";  // Default: string
                                $valorCasteado = self::castValue($medicamento[$key] ?? null, $tipo);
                                $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine][$key] = $valorCasteado;
                            }
                        }
                    }
                }
            }
        }

        return [
            'errorMessages' => $errorMessages,
            'totalErrorMessages' => count($errorMessages),
        ];
    }

    public static function validateRipsStatus($ripId)
    {
        $rip = Rip::find($ripId);
        $invoices = $rip->ripInvoices;

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->value)->count() === $invoices->count() && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->value)->count() === $invoices->count()) {
            $rip->status = RipStatusEnum::RIP_STATUS_002->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001->value)->count() === $invoices->count() && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003->value)->count() > 0) {
            $rip->status = RipStatusEnum::RIP_STATUS_005->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001->value)->count() === $invoices->count() && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->value)->count() === $invoices->count()) {
            $rip->status = RipStatusEnum::RIP_STATUS_006->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_003->value)->count() > 0 && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->value)->count() === $invoices->count()) {
            $rip->status = RipStatusEnum::RIP_STATUS_003->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001->value)->count() > 0 && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->value)->count() === $invoices->count()) {
            $rip->status = RipStatusEnum::RIP_STATUS_006->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_003->value)->count() > 0) {
            $rip->status = RipStatusEnum::RIP_STATUS_003->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->value)->count() === $invoices->count() && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003->value)->count() === 0) {
            $rip->status = RipStatusEnum::RIP_STATUS_007->value;
            $rip->save();

            return;
        }

        if ($invoices->where('status', RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->value)->count() === $invoices->count() && $invoices->where('status_xml', RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003->value)->count() > 0) {
            $rip->status = RipStatusEnum::RIP_STATUS_005->value;
            $rip->save();

            return;
        }
    }

    // Helper para castear valores (método privado de la clase)
    private static function castValue($valor, $tipo = 'string')
    {
        switch ($tipo) {
            case 'string':
                return (string) $valor;
            case 'int':
                return (int) $valor;
            case 'float':
                return (float) $valor;
                // Agrega más tipos si necesitas (ej. 'bool' => (bool)$valor)
            default:
                Log::warning("Tipo de casteo no soportado: {$tipo}. Usando string por defecto.");
                return (string) $valor;
        }
    }


    //otrosServicios (listo)
    public static function structureAT(): array
    {
        return [
            'codPrestador',
            'numAutorizacion',
            'idMIPRES',
            'fechaSuministroTecnologia',
            'tipoOS',
            'codTecnologiaSalud',
            'nomTecnologiaSalud',
            'cantidadOS',
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'vrUnitOS',
            'vrServicio',
            'conceptoRecaudo',
            'valorPagoModerador',
            'numFEVPagoModerador',
            'consecutivo',
        ];
    }

    // reciennacidos (listo)
    public static function structureAN(): array
    {
        return [
            'codPrestador',
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'fechaNacimiento',
            'edadGestacional',
            'numConsultasCPrenatal',
            'codSexoBiologico',
            'peso',
            'codDiagnosticoPrincipal',
            'condicionDestinoUsuarioEgreso',
            'codDiagnosticoCausaMuerte',
            'fechaEgreso',
            'consecutivo',
        ];
    }

    // hospitalizacion (listo)
    public static function structureAH(): array
    {
        return [
            'codPrestador',
            'viaIngresoServicioSalud',
            'fechaInicioAtencion',
            'numAutorizacion',
            'causaMotivoAtencion',
            'codDiagnosticoPrincipal',
            'codDiagnosticoPrincipalE',
            'codDiagnosticoRelacionadoE1',
            'codDiagnosticoRelacionadoE2',
            'codDiagnosticoRelacionadoE3',
            'codComplicacion',
            'condicionDestinoUsuarioEgreso',
            'codDiagnosticoCausaMuerte',
            'fechaEgreso',
            'consecutivo',
        ];
    }

    // medicamento (listo)
    public static function structureAM(): array
    {
        return [
            'codPrestador',
            'numAutorizacion',
            'idMIPRES',
            'fechaDispensAdmon',
            'codDiagnosticoPrincipal',
            'codDiagnosticoRelacionado',
            'tipoMedicamento',
            'codTecnologiaSalud',
            'nomTecnologiaSalud',
            'concentracionMedicamento',
            'unidadMedida',
            'formaFarmaceutica',
            'unidadMinDispensa',
            'cantidadMedicamento',
            'diasTratamiento',
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'vrUnitMedicamento',
            'vrServicio',
            'conceptoRecaudo',
            'valorPagoModerador',
            'numFEVPagoModerador',
            'consecutivo',
        ];
    }

    //urgencia (listo)
    public static function structureAU(): array
    {
        return [
            'codPrestador',
            'fechaInicioAtencion',
            'causaMotivoAtencion',
            'codDiagnosticoPrincipal',
            'codDiagnosticoPrincipalE',
            'codDiagnosticoRelacionadoE1',
            'codDiagnosticoRelacionadoE2',
            'codDiagnosticoRelacionadoE3',
            'condicionDestinoUsuarioEgreso',
            'codDiagnosticoCausaMuerte',
            'fechaEgreso',
            'consecutivo',
        ];
    }

    //procedimiento (listo)
    public static function structureAP(): array
    {
        return [
            'codPrestador',
            'fechaInicioAtencion',
            'idMIPRES',
            'numAutorizacion',
            'codProcedimiento',
            'viaIngresoServicioSalud',
            'modalidadGrupoServicioTecSal',
            'grupoServicios',
            'codServicio',
            'finalidadTecnologiaSalud',
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'codDiagnosticoPrincipal',
            'codDiagnosticoRelacionado',
            'codComplicacion',
            'vrServicio',
            'conceptoRecaudo',
            'valorPagoModerador',
            'numFEVPagoModerador',
            'consecutivo',
        ];
    }

    //usuario (listo)
    public static function structureUS(): array
    {
        return [
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'tipoUsuario',
            'fechaNacimiento',
            'codSexo',
            'codPaisResidencia',
            'codMunicipioResidencia',
            'codZonaTerritorialResidencia',
            'incapacidad',
            'codPaisOrigen',
            'consecutivo',
        ];
    }

    //consultas (listo)
    public static function structureAC(): array
    {
        return [
            'codPrestador',
            'fechaInicioAtencion',
            'numAutorizacion',
            'codConsulta',
            'modalidadGrupoServicioTecSal',
            'grupoServicios',
            'codServicio',
            'finalidadTecnologiaSalud',
            'causaMotivoAtencion',
            'codDiagnosticoPrincipal',
            'codDiagnosticoRelacionado1',
            'codDiagnosticoRelacionado2',
            'codDiagnosticoRelacionado3',
            'tipoDiagnosticoPrincipal',
            'tipoDocumentoIdentificacion',
            'numDocumentoIdentificacion',
            'vrServicio',
            'conceptoRecaudo',
            'valorPagoModerador',
            'numFEVPagoModerador',
            'consecutivo',
        ];
    }

    //Factura (listo)
    public static function structureAF(): array
    {
        return [
            'numDocumentoIdObligado',
            'numFactura',
            'tipoNota',
            'numNota',
        ];
    }



}
