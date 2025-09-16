<?php

namespace App\Helpers\Rips;

use App\Enums\Rip\RipStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Exports\RipXlsExport;
use App\Helpers\Constants;
use App\Models\Rip;
use App\Models\RipInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class GenerateRipInfo
{
    public static function saveReloadDataRips($ripId, $arrayJsonInvoices)
    {
        DB::beginTransaction();

        $rip = Rip::find($ripId);

        //tomamos y hacemos un clon exacto de $arraySuccessfulInvoices
        $buildDataFinal = json_decode(collect($arrayJsonInvoices), 1);

        //quitamos los campos que se necesitan por ahora  (numDocumentoIdentificacion,numFEVPagoModerador de de AH , AN,AU)
        self::deleteFieldsPerzonalizedJson($buildDataFinal);

        //se guarda el xls nuevo, el json general y los json independientes en la bd
        self::saveReloadDataInvoices($rip->id, $buildDataFinal);

        DB::commit();
    }

    //genero el excel y json global
    public static function generateDataJsonAndExcel($ripId, $type = RipTypeEnum::RIP_TYPE_001->value)
    {
        //generamos el archivo xls con los campos que faltan para todas las facturas

        $rip = Rip::find($ripId);

        $jsonContents = [];

        if (isset($rip->ripInvoices) && count($rip->ripInvoices) > 0) {
            foreach ($rip->ripInvoices as $key => $value) {
                $jsonContents[] = openFileJson($value->path_json);
            }
        }

        //EXCELES
        $nameFile = 'rips_' . $rip->id . '.xlsx';
        $path_excel = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/' . $nameFile; // Ruta donde se guardará la carpeta
        Excel::store(new RipXlsExport($jsonContents), $path_excel, 'public', \Maatwebsite\Excel\Excel::XLSX);

        //JSONS
        // Nombre del archivo en el sistema de archivos
        $nameFile = 'rips_' . $rip->id . '.json';
        // Guarda el JSON en el sistema de archivos usando el disco predeterminado (puede configurar otros discos si es necesario)
        $path_json = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/' . $nameFile; // Ruta donde se guardará la carpeta
        $jsonContents = array_values($jsonContents); //reindexo el array
        $json = json_encode($jsonContents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk(Constants::DISK_FILES)->put($path_json, $json); //guardo el archivo

        //actualizo el registro del rip en la bd
        $rip->sumVr = self::sumVrServicioRips($jsonContents); //actualizo la suma de los campos vrservicio de los servicios
        $rip->path_json = $path_json;
        $rip->path_excel = $path_excel;
        $rip->save(); //actualizo el registro
    }


    //suma todos los valores VRSERVICE DE TODAS LAS FACTURAS
    private static function sumVrServicioRips($invoices)
    {
        $sumVrRips = 0;
        foreach ($invoices as $invoice) {
            $sumVrRips += self::sumVrServicio($invoice);
        }
        return $sumVrRips;
    }


    private static function sumVrServicio($valueJsonInvoice)
    {
        //suma todos los valores VRSERVICE DE TODAS LAS FACTURAS
        $sumVrInvoice = 0;
        if (isset($valueJsonInvoice['usuarios']) && count($valueJsonInvoice['usuarios']) > 0) {
            foreach ($valueJsonInvoice['usuarios'] as $user) {

                $elements = ['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosServicios', 'hospitalizacion', 'recienNacidos'];
                foreach ($elements as $ele) {
                    if (isset($user['servicios'][$ele]) && count($user['servicios'][$ele]) > 0) {
                        foreach ($user['servicios'][$ele] as $query) {
                            $vrServicio = 0;
                            if (isset($query['vrServicio'])) {
                                $vrServicio = str_replace('.', '', $query['vrServicio']);
                            }
                            if (intval($vrServicio) > 0) {
                                $sumVrInvoice += intval($vrServicio);
                            }
                        }
                    }
                }
            }
        }

        return $sumVrInvoice;
    }

    private static function deleteFieldsPerzonalizedJson(&$buildDataFinal)
    {
        foreach ($buildDataFinal as &$invoice) {
            foreach ($invoice['usuarios'] as &$user) {
                $services = ['hospitalizacion', 'recienNacidos', 'urgencias'];
                foreach ($services as $service) {
                    if (isset($user['servicios'][$service]) && count($user['servicios'][$service]) > 0) {
                        foreach ($user['servicios'][$service] as $keyH => &$value) {
                            unset($value['numDocumentoIdentificacion']);
                            unset($value['numFEVPagoModerador']);
                        }
                    }
                }
            }
        }
    }

    private static function saveReloadDataInvoices($ripId, $jsonData)
    {
        //se generan los json y excel por cada factura y se guarda el archivo
        foreach ($jsonData as $key => $value) {
            self::saveReloadDataInvoice($ripId, $value);
        }
    }

    private static function saveReloadDataInvoice($ripId, $valueJsonInvoice, $counErrorExcelInvoice = 'sinValidarExcel')
    {
        $rip = Rip::find($ripId);
        $type = $rip->type->value;

        $nameFile = $valueJsonInvoice['numFactura'] . '.xlsx';
        $routeXls = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/invoices/{$valueJsonInvoice['numFactura']}/{$nameFile}"; // Ruta donde se guardará la carpeta
        Excel::store(new RipXlsExport([$valueJsonInvoice]), $routeXls, 'public', \Maatwebsite\Excel\Excel::XLSX);

        $nameFile = $valueJsonInvoice['numFactura'] . '.json';
        $routeJson = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $valueJsonInvoice['numFactura'] . '/' . $nameFile; // Ruta donde se guardará la carpeta
        Storage::disk('public')->put($routeJson, json_encode($valueJsonInvoice)); //guardo el archivo

        //se guarda el registro en la BD tabla invoice
        $ripInvoice = RipInvoice::where(function ($query) use ($ripId, $valueJsonInvoice) {
            $query->where('rip_id', $ripId);
            $query->where('invoice_number', $valueJsonInvoice['numFactura']);
        })->first();

        //si la factura no existe la creo una instancia nueva
        if (!$ripInvoice) {
            $ripInvoice = RipInvoice::newModelInstance();
        }

        if ($counErrorExcelInvoice == 'sinValidarExcel') {
            $ripInvoice->status = RipStatusEnum::RIP_STATUS_001;
        } else if ($counErrorExcelInvoice > 0) {
            $ripInvoice->status = RipStatusEnum::RIP_STATUS_003;
        } else if ($counErrorExcelInvoice == 0) {
            $ripInvoice->status = RipStatusEnum::RIP_STATUS_002;
        }

        $ripInvoice->rip_id = $ripId;
        $ripInvoice->path_json = $routeJson;
        $ripInvoice->path_excel = $routeXls;
        $ripInvoice->company_id = $rip->company_id;

        $ripInvoice->invoice_number = $valueJsonInvoice['numFactura'];
        $ripInvoice->sumVr = self::sumVrServicio($valueJsonInvoice);
        $ripInvoice->count_users = $valueJsonInvoice['usuarios'] ? count($valueJsonInvoice['usuarios']) : 0;
        $ripInvoice->note_type = $valueJsonInvoice['TipoNota'] ?? null;
        $ripInvoice->note_number = $valueJsonInvoice['numNota'] ?? null;

        $ripInvoice->save();
    }
}
