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

    public static function openXls($file)
    {
        // Leer el archivo XLS usando Laravel Excel
        $data = Excel::toArray([], $file);

        // Procesar los datos obtenidos del archivo XLS
        $keys = $data[0][0]; // Los títulos se encuentran en la primera fila
        $excelData = array_slice($data[0], 1); // Eliminar la primera fila (encabezados)

        // Crear una colección con los datos del XLS junto con los números de fila
        $xlsCollection = collect($excelData)->map(function ($row, $index) use ($keys, $file) {
            $dataWithKeys = array_combine($keys, $row);
            // $dataWithKeys['row'] = $index + 2; // Sumar 2 para ajustar al número de fila real
            // $dataWithKeys['file'] = $file->getClientOriginalName();

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
        // return$groupedData;
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
                        $requiredFields = ['tipoNota', 'numNota'];
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
                        $requiredFields = ['codPaisOrigen', 'fechaNacimiento', 'codPaisResidencia', 'codZonaTerritorialResidencia', 'incapacidad', 'consecutivo','tipoUsuario','codMunicipioResidencia'];
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
                        $requiredFields = ['conceptoRecaudo', 'fechaInicioAtencion', 'modalidadGrupoServicioTecSal', 'grupoServicios', 'codServicio', 'tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'tipoPagoModerador', 'valorPagoModerador', 'numFEVPagoModerador', 'consecutivo'];
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
                        $requiredFields = ['conceptoRecaudo', 'fechaInicioAtencion', 'idMIPRES', 'modalidadGrupoServicioTecSal', 'grupoServicios', 'codServicio', 'tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'tipoPagoModerador', 'valorPagoModerador', 'numFEVPagoModerador', 'consecutivo','codDiagnosticoPrincipal','codComplicacion','codDiagnosticoRelacionado','finalidadTecnologiaSalud','viaIngresoServicioSalud','codPrestador','codProcedimiento'];
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
                        $requiredFields = ['conceptoRecaudo', 'idMIPRES', 'fechaDispensAdmon', 'codDiagnosticoPrincipal', 'codDiagnosticoRelacionado', 'formaFarmaceutica', 'unidadMinDispensa', 'diasTratamiento', 'tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'vrUnitMedicamento', 'tipoPagoModerador', 'valorPagoModerador', 'numFEVPagoModerador', 'consecutivo'];
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
                        $requiredFields = ['conceptoRecaudo', 'idMIPRES', 'fechaSuministroTecnologia', 'tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'tipoPagoModerador', 'valorPagoModerador', 'numFEVPagoModerador', 'consecutivo'];
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
                        $requiredFields = ['consecutivo', 'fechaInicioAtencion'];
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
                        $requiredFields = ['consecutivo', 'fechaInicioAtencion'];
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
                        $requiredFields = ['tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'numConsultasCPrenatal', 'consecutivo'];
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
        $dataExtra = ['numFactura' => null];
        $errorMessages = [];
        foreach ($arrayInfo as $indexInvoice => $data) {

            // Asignar una cadena vacía en lugar de null si $data['numNota'] es cero
            $arrayData[$indexInvoice]['numNota'] = ($data['numNota'] === "00") ? '' : (string)$data['numNota'];

            // Asignar una cadena vacía en lugar de null si $data['numNota'] es cero, si viene otro valor ejecuta la validacion
            if (($data['tipoNota'] === "00")) {
                $arrayData[$indexInvoice]['tipoNota'] = '';
            } else {
                $arrayData[$indexInvoice]['tipoNota'] = ($data['tipoNota'] === "00") ? '' : (string)$data['tipoNota'];
            }

            if (isset($data['usuarios']) && count($data['usuarios']) > 0) {
                foreach ($data['usuarios'] as $indexUser => $usuario) {

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codPaisOrigen'] = (string)$usuario['codPaisOrigen'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codPaisResidencia'] = (string)$usuario['codPaisResidencia'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codZonaTerritorialResidencia'] = (string)$usuario['codZonaTerritorialResidencia'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['incapacidad'] = (string)$usuario['incapacidad'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['fechaNacimiento'] = (string)$usuario['fechaNacimiento'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['tipoUsuario'] = (string)$usuario['tipoUsuario'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codMunicipioResidencia'] = (string)$usuario['codMunicipioResidencia'];

                    //CONSULTAS
                    if (isset($usuario['servicios']['consultas']) && count($usuario['servicios']['consultas']) > 0) {
                        foreach ($usuario['servicios']['consultas'] as $indexQuery => $consulta) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['tipoDocumentoIdentificacion'] = (string)$consulta['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['numDocumentoIdentificacion'] = (string)$consulta['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['tipoPagoModerador'] = (string)$consulta['tipoPagoModerador'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['numFEVPagoModerador'] = (string)$consulta['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['conceptoRecaudo'] = (string)$consulta['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['modalidadGrupoServicioTecSal'] = (string)$consulta['modalidadGrupoServicioTecSal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['grupoServicios'] = (string)$consulta['grupoServicios'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['codServicio'] = (int)$consulta['codServicio'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['fechaInicioAtencion'] = (string)$consulta['fechaInicioAtencion'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['valorPagoModerador'] = (float)$consulta['valorPagoModerador'];
                        }
                    }

                    //PROCEDIMIENTOS
                    if (isset($usuario['servicios']['procedimientos']) && count($usuario['servicios']['procedimientos']) > 0) {
                        foreach ($usuario['servicios']['procedimientos'] as $indexProcedure => $procedimiento) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['tipoDocumentoIdentificacion'] = (string)$procedimiento['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['numDocumentoIdentificacion'] = (string)$procedimiento['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['numFEVPagoModerador'] = (string)$procedimiento['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['conceptoRecaudo'] = (string)$procedimiento['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['idMIPRES'] = (string)$procedimiento['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['valorPagoModerador'] = (float)$procedimiento['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['modalidadGrupoServicioTecSal'] = (string)$procedimiento['modalidadGrupoServicioTecSal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['grupoServicios'] = (string)$procedimiento['grupoServicios'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codServicio'] = (int)$procedimiento['codServicio'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['fechaInicioAtencion'] = (string)$procedimiento['fechaInicioAtencion'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['vrServicio'] = (float)$procedimiento['vrServicio'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codDiagnosticoPrincipal'] = (string)$procedimiento['codDiagnosticoPrincipal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codComplicacion'] = (string)$procedimiento['codComplicacion'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codDiagnosticoRelacionado'] = (string)$procedimiento['codDiagnosticoRelacionado'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['finalidadTecnologiaSalud'] = (string)$procedimiento['finalidadTecnologiaSalud'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['viaIngresoServicioSalud'] = (string)$procedimiento['viaIngresoServicioSalud'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codPrestador'] = (string)$procedimiento['codPrestador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codProcedimiento'] = (string)$procedimiento['codProcedimiento'];
                        }
                    }

                    //OTROS SERVICIOS
                    if (isset($usuario['servicios']['otrosServicios']) && count($usuario['servicios']['otrosServicios']) > 0) {
                        foreach ($usuario['servicios']['otrosServicios'] as $indexOtherService => $otrosServicio) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['tipoDocumentoIdentificacion'] = (string)$otrosServicio['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['numDocumentoIdentificacion'] = (string)$otrosServicio['numDocumentoIdentificacion'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['numFEVPagoModerador'] = (string)$otrosServicio['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['conceptoRecaudo'] = (string)$otrosServicio['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['idMIPRES'] = (string)$otrosServicio['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['valorPagoModerador'] = (float)$otrosServicio['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['fechaSuministroTecnologia'] = (string)$otrosServicio['fechaSuministroTecnologia'];
                        }
                    }

                    //URGENCIAS
                    if (isset($usuario['servicios']['urgencias']) && count($usuario['servicios']['urgencias']) > 0) {
                        foreach ($usuario['servicios']['urgencias'] as $indexUrgency => $urgencia) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['urgencias'][$indexUrgency]['fechaEgreso'] = (string)$urgencia['fechaEgreso'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['urgencias'][$indexUrgency]['fechaInicioAtencion'] = (string)$urgencia['fechaInicioAtencion'];
                        }
                    }

                    //HOSPITALIZACION
                    if (isset($usuario['servicios']['hospitalizacion']) && count($usuario['servicios']['hospitalizacion']) > 0) {
                        foreach ($usuario['servicios']['hospitalizacion'] as $indexHospitalization => $hospitalizacion) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['hospitalizacion'][$indexHospitalization]['fechaEgreso'] = (string)$hospitalizacion['fechaEgreso'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['hospitalizacion'][$indexHospitalization]['fechaInicioAtencion'] = (string)$hospitalizacion['fechaInicioAtencion'];
                        }
                    }

                    //RECIEN NACIDOS
                    if (isset($usuario['servicios']['recienNacidos']) && count($usuario['servicios']['recienNacidos']) > 0) {
                        foreach ($usuario['servicios']['recienNacidos'] as $indexNewlyBorn => $recienNacido) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['tipoDocumentoIdentificacion'] = (string)$recienNacido['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['numConsultasCPrenatal'] = (string)$recienNacido['numConsultasCPrenatal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['fechaNacimiento'] = (string)$recienNacido['fechaNacimiento'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['fechaEgreso'] = (string)$recienNacido['fechaEgreso'];
                        }
                    }

                    //MEDICAMENTOS
                    if (isset($usuario['servicios']['medicamentos']) && count($usuario['servicios']['medicamentos']) > 0) {
                        foreach ($usuario['servicios']['medicamentos'] as $indexMedicine => $medicamento) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['formaFarmaceutica'] = (string)$medicamento['formaFarmaceutica'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['unidadMinDispensa'] = (string)$medicamento['unidadMinDispensa'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['tipoDocumentoIdentificacion'] = (string)$medicamento['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['numDocumentoIdentificacion'] = (string)$medicamento['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['vrUnitMedicamento'] = (string)$medicamento['vrUnitMedicamento'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['tipoPagoModerador'] = (string)$medicamento['tipoPagoModerador'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['numFEVPagoModerador'] = (string)$medicamento['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['conceptoRecaudo'] = (string)$medicamento['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['idMIPRES'] = (string)$medicamento['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['valorPagoModerador'] = (float)$medicamento['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['diasTratamiento'] = (string)$medicamento['diasTratamiento'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['fechaDispensAdmon'] = (string)$medicamento['fechaDispensAdmon'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['codDiagnosticoPrincipal'] = (string)$medicamento['codDiagnosticoPrincipal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['codDiagnosticoRelacionado'] = (string)$medicamento['codDiagnosticoRelacionado'];
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
}
