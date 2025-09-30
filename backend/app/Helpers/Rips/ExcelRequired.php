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
                        $requiredFields = ['codPaisOrigen', 'fechaNacimiento', 'codPaisResidencia', 'codZonaTerritorialResidencia', 'incapacidad', 'consecutivo'];
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
                        $requiredFields = ['conceptoRecaudo', 'fechaInicioAtencion', 'idMIPRES', 'modalidadGrupoServicioTecSal', 'grupoServicios', 'codServicio', 'tipoDocumentoIdentificacion', 'numDocumentoIdentificacion', 'tipoPagoModerador', 'valorPagoModerador', 'numFEVPagoModerador', 'consecutivo'];
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
            $dataExtra = ['numFactura' => $data['numFactura']];

            // Asignar una cadena vacía en lugar de null si $data['numNota'] es cero
            $arrayData[$indexInvoice]['numNota'] = ($data['numNota'] === "00") ? '' : $data['numNota'];

            // Asignar una cadena vacía en lugar de null si $data['numNota'] es cero, si viene otro valor ejecuta la validacion
            if (($data['tipoNota'] === "00")) {
                $arrayData[$indexInvoice]['tipoNota'] = '';
            } else {
                $arrayData[$indexInvoice]['tipoNota'] = ($data['tipoNota'] === "00") ? '' : $data['tipoNota'];
            }

            if (isset($data['usuarios']) && count($data['usuarios']) > 0) {
                foreach ($data['usuarios'] as $indexUser => $usuario) {

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codPaisOrigen'] = $usuario['codPaisOrigen'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codPaisResidencia'] = $usuario['codPaisResidencia'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['codZonaTerritorialResidencia'] = $usuario['codZonaTerritorialResidencia'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['incapacidad'] = $usuario['incapacidad'];

                    $arrayData[$indexInvoice]['usuarios'][$indexUser]['fechaNacimiento'] = $usuario['fechaNacimiento'];

                    //CONSULTAS
                    if (isset($usuario['servicios']['consultas']) && count($usuario['servicios']['consultas']) > 0) {
                        foreach ($usuario['servicios']['consultas'] as $indexQuery => $consulta) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['tipoDocumentoIdentificacion'] = $consulta['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['numDocumentoIdentificacion'] = $consulta['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['tipoPagoModerador'] = $consulta['tipoPagoModerador'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['numFEVPagoModerador'] = $consulta['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['conceptoRecaudo'] = $consulta['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['modalidadGrupoServicioTecSal'] = $consulta['modalidadGrupoServicioTecSal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['grupoServicios'] = $consulta['grupoServicios'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['codServicio'] = $consulta['codServicio'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['fechaInicioAtencion'] = $consulta['fechaInicioAtencion'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['consultas'][$indexQuery]['valorPagoModerador'] = $consulta['valorPagoModerador'];
                        }
                    }

                    //PROCEDIMIENTOS
                    if (isset($usuario['servicios']['procedimientos']) && count($usuario['servicios']['procedimientos']) > 0) {
                        foreach ($usuario['servicios']['procedimientos'] as $indexProcedure => $procedimiento) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['tipoDocumentoIdentificacion'] = $procedimiento['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['numDocumentoIdentificacion'] = $procedimiento['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['numFEVPagoModerador'] = $procedimiento['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['conceptoRecaudo'] = $procedimiento['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['idMIPRES'] = $procedimiento['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['valorPagoModerador'] = $procedimiento['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['modalidadGrupoServicioTecSal'] = $procedimiento['modalidadGrupoServicioTecSal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['grupoServicios'] = $procedimiento['grupoServicios'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['codServicio'] = $procedimiento['codServicio'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['procedimientos'][$indexProcedure]['fechaInicioAtencion'] = $procedimiento['fechaInicioAtencion'];
                        }
                    }

                    //OTROS SERVICIOS
                    if (isset($usuario['servicios']['otrosServicios']) && count($usuario['servicios']['otrosServicios']) > 0) {
                        foreach ($usuario['servicios']['otrosServicios'] as $indexOtherService => $otrosServicio) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['tipoDocumentoIdentificacion'] = $otrosServicio['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['numDocumentoIdentificacion'] = $otrosServicio['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['tipoPagoModerador'] = $otrosServicio['tipoPagoModerador'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['numFEVPagoModerador'] = $otrosServicio['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['conceptoRecaudo'] = $otrosServicio['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['idMIPRES'] = $otrosServicio['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['valorPagoModerador'] = $otrosServicio['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['otrosServicios'][$indexOtherService]['fechaSuministroTecnologia'] = $otrosServicio['fechaSuministroTecnologia'];
                        }
                    }

                    //URGENCIAS
                    if (isset($usuario['servicios']['urgencias']) && count($usuario['servicios']['urgencias']) > 0) {
                        foreach ($usuario['servicios']['urgencias'] as $indexUrgency => $urgencia) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['urgencias'][$indexUrgency]['fechaEgreso'] = $urgencia['fechaEgreso'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['urgencias'][$indexUrgency]['fechaInicioAtencion'] = $urgencia['fechaInicioAtencion'];
                        }
                    }

                    //HOSPITALIZACION
                    if (isset($usuario['servicios']['hospitalizacion']) && count($usuario['servicios']['hospitalizacion']) > 0) {
                        foreach ($usuario['servicios']['hospitalizacion'] as $indexHospitalization => $hospitalizacion) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['hospitalizacion'][$indexHospitalization]['fechaEgreso'] = $hospitalizacion['fechaEgreso'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['hospitalizacion'][$indexHospitalization]['fechaInicioAtencion'] = $hospitalizacion['fechaInicioAtencion'];
                        }
                    }

                    //RECIEN NACIDOS
                    if (isset($usuario['servicios']['recienNacidos']) && count($usuario['servicios']['recienNacidos']) > 0) {
                        foreach ($usuario['servicios']['recienNacidos'] as $indexNewlyBorn => $recienNacido) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['tipoDocumentoIdentificacion'] = $recienNacido['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['numConsultasCPrenatal'] = $recienNacido['numConsultasCPrenatal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['fechaNacimiento'] = $recienNacido['fechaNacimiento'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['recienNacidos'][$indexNewlyBorn]['fechaEgreso'] = $recienNacido['fechaEgreso'];
                        }
                    }

                    //MEDICAMENTOS
                    if (isset($usuario['servicios']['medicamentos']) && count($usuario['servicios']['medicamentos']) > 0) {
                        foreach ($usuario['servicios']['medicamentos'] as $indexMedicine => $medicamento) {

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['formaFarmaceutica'] = $medicamento['formaFarmaceutica'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['unidadMinDispensa'] = $medicamento['unidadMinDispensa'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['tipoDocumentoIdentificacion'] = $medicamento['tipoDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['numDocumentoIdentificacion'] = $medicamento['numDocumentoIdentificacion'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['vrUnitMedicamento'] = $medicamento['vrUnitMedicamento'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['tipoPagoModerador'] = $medicamento['tipoPagoModerador'];
                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['numFEVPagoModerador'] = $medicamento['numFEVPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['conceptoRecaudo'] = $medicamento['conceptoRecaudo'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['idMIPRES'] = $medicamento['idMIPRES'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['valorPagoModerador'] = $medicamento['valorPagoModerador'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['diasTratamiento'] = $medicamento['diasTratamiento'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['fechaDispensAdmon'] = $medicamento['fechaDispensAdmon'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['codDiagnosticoPrincipal'] = $medicamento['codDiagnosticoPrincipal'];

                            $arrayData[$indexInvoice]['usuarios'][$indexUser]['servicios']['medicamentos'][$indexMedicine]['codDiagnosticoRelacionado'] = $medicamento['codDiagnosticoRelacionado'];
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
