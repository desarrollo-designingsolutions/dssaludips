<?php

namespace App\Helpers\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Exports\Rips\RipXlsExport;
use App\Helpers\Constants;
use App\Models\Rip;
use App\Models\RipInvoice;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


class GenerateRipInfo
{
    const CHUNK_SIZE = 100; // Tamaño del chunk configurable
    const REDIS_KEY_PREFIX = 'rip_invoice_batch:';

    public static function saveReloadDataRips($ripId, $arrayJsonInvoices, $batchId = null)
    {
        DB::beginTransaction();

        $rip = Rip::find($ripId);

        //tomamos y hacemos un clon exacto de $arraySuccessfulInvoices
        $buildDataFinal = json_decode(collect($arrayJsonInvoices), 1);

        //quitamos los campos que se necesitan por ahora  (numDocumentoIdentificacion,numFEVPagoModerador de de AH , AN,AU)
        self::deleteFieldsPerzonalizedJson($buildDataFinal);

        //se guarda el xls nuevo, el json general y los json independientes en la bd
        self::saveReloadDataInvoices($rip->id, $buildDataFinal, $batchId);

        DB::commit();
    }

    //genero el excel y json global
    public static function generateDataJsonAndExcel($ripId, $type = RipTypeEnum::RIP_TYPE_001->value, $fileExcel = false)
    {
        //generamos el archivo xls con los campos que faltan para todas las facturas

        $rip = Rip::find($ripId);

        $jsonContents = [];

        if (isset($rip->ripInvoices) && count($rip->ripInvoices) > 0) {
            foreach ($rip->ripInvoices as $key => $value) {
                $jsonContents[] = openFileJson($value->path_json);
            }
        }


        $path_excel = null;

        if ($fileExcel === true) {
            //EXCELES
            $nameFile = 'rips_' . $rip->id . '.xlsx';
            $path_excel = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/' . $nameFile; // Ruta donde se guardará la carpeta
            Excel::store(new RipXlsExport($jsonContents), $path_excel, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

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
                            if(isset($value['numDocumentoIdentificacion'])){
                                unset($value['numDocumentoIdentificacion']);
                            }
                            if(isset($value['numDocumentoIdentificacion'])){
                                unset($value['numDocumentoIdentificacion']);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function saveReloadDataInvoices($ripId, $jsonData, $batchId = null)
    {
        $cacheService = app(CacheService::class);
        $cacheKey = $cacheService->generateKey("GenerateRipInfo:saveReloadDataInvoice:rip_{$ripId}", [
            'select' => ["id", "company_id", "type"]
        ], 'string');

        $rip = $cacheService->remember($cacheKey, function () use ($ripId) {
            return Rip::select(["id", "company_id", "type"])->find($ripId);
        });


        $arrayNumFactura = collect($jsonData)->pluck("numFactura")->toArray();


        //se guarda el registro en la BD tabla invoice
        $cacheKey = $cacheService->generateKey("GenerateRipInfo:saveReloadDataInvoice:RipInvoice", [
            'rip_id' => $ripId,
            'invoice_number' => $arrayNumFactura,
        ], 'string');


        // Dividir los datos en chunks
        $chunks = array_chunk($jsonData, self::CHUNK_SIZE);
        $totalChunks = count($chunks);

        Log::info("Procesando {$totalChunks} chunks para RIP {$ripId}");


        foreach ($chunks as $chunkIndex => $chunkData) {
            self::processChunk($rip, $chunkData, $chunkIndex, $totalChunks, $batchId);
        }
    }

    public static function processChunk($rip, $chunkData, $chunkIndex, $totalChunks, $batchId = null)
    {
        $batchData = [];
        $invoiceNumbers = [];

        foreach ($chunkData as $invoiceData) {

            $type = $rip->type?->value;

            // $nameFile = $invoiceData['numFactura'] . '.xlsx';
            $routeXls = null;
            // $routeXls = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/invoices/{$invoiceData['numFactura']}/{$nameFile}"; // Ruta donde se guardará la carpeta
            // Excel::store(new RipXlsExport([$invoiceData]), $routeXls, Constants::DISK_FILES, \Maatwebsite\Excel\Excel::XLSX);


            $nameFile = $invoiceData['numFactura'] . '.json';
            $routeJson = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $invoiceData['numFactura'] . '/' . $nameFile; // Ruta donde se guardará la carpeta

            Storage::disk(Constants::DISK_FILES)->put($routeJson, json_encode($invoiceData)); //guardo el archivo

            $invoiceNumber = $invoiceData['numFactura'];
            $invoiceNumbers[] = $invoiceNumber;

            // Preparar datos para inserción masiva
            $batchData[$invoiceNumber] = [
                'rip_id' => $rip->id,
                'company_id' => $rip->company_id,
                'invoice_number' => $invoiceNumber,
                'sumVr' => self::sumVrServicio($invoiceData),
                'count_users' => $invoiceData['usuarios'] ? count($invoiceData['usuarios']) : 0,
                'note_type' => $invoiceData['tipoNota'] ?? null,
                'note_number' => $invoiceData['numNota'] ?? null,
                'status' => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002,
                'status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002,
                'path_json' => $routeJson,
                'path_excel' => $routeXls,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Guardar JSON individual en Redis temporalmente
            $redisKey = self::REDIS_KEY_PREFIX . $rip->id . ':' . $invoiceNumber;
            Redis::setex($redisKey, 3600, json_encode($invoiceData)); // Expira en 1 hora
        }

        // Insertar o actualizar registros en la base de datos
        // Upsert masivo usando Eloquent
        self::bulkUpsertSimple($batchData, $rip->id);

        if ($batchId) {
            $redis = Redis::connection('redis_6380');
            $metadata = $redis->hgetall("batch:{$batchId}:metadata");
            $totalErrors = $metadata['total_errors'] ?? 0;

            event(new ImportProgressEvent(
                $batchId,
                "$metadata[total_rows]/$metadata[total_rows]", // Todos los registros procesados
                "Guardando registros, exceles y jsons...", // Todos los registros procesados
                $totalErrors, // Total de errores
                'active',
                "Guardando registros... (" . ($chunkIndex + 1) . "/$totalChunks)" // Progreso
            ));
        }
    }

    private static function bulkUpsertSimple($batchData, $ripId)
    {
        $invoiceNumbers = array_keys($batchData);

        // Obtener existentes
        $existing = RipInvoice::where('rip_id', $ripId)
            ->whereIn('invoice_number', $invoiceNumbers)
            ->get()
            ->keyBy('invoice_number');

        $toInsert = [];
        $toUpdate = [];

        foreach ($batchData as $invoiceNumber => $data) {
            if (isset($existing[$invoiceNumber])) {
                // Actualizar existente
                $existing[$invoiceNumber]->update($data);
                $toUpdate[] = $existing[$invoiceNumber]->id;

                Log::info("Actualizada la factura {$invoiceNumber} del RIP {$ripId}");
            } else {
                // Insertar nuevo - agregar UUID manualmente
                $data["id"] = Str::uuid();
                $toInsert[] = $data;
            }
        }

        // Insert masivo
        if (!empty($toInsert)) {
            RipInvoice::insert($toInsert);
            Log::info("Insertadas " . count($toInsert) . " nuevas facturas para RIP {$ripId}");
        }

        // Disparar eventos para actualizados
        foreach ($toUpdate as $invoiceId) {
            RipInvoiceRowUpdatedNow::dispatch($invoiceId);
        }
    }



    // public static function saveReloadDataInvoice($rip, $valueJsonInvoice, $ripInvoice)
    // {

    //     $type = $rip->type?->value;

    //     $nameFile = $valueJsonInvoice['numFactura'] . '.xlsx';
    //     $routeXls = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/invoices/{$valueJsonInvoice['numFactura']}/{$nameFile}"; // Ruta donde se guardará la carpeta

    //     Excel::store(new RipXlsExport([$valueJsonInvoice]), $routeXls, Constants::DISK_FILES, \Maatwebsite\Excel\Excel::XLSX);

    //     $nameFile = $valueJsonInvoice['numFactura'] . '.json';
    //     $routeJson = 'companies/company_' . $rip->company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $valueJsonInvoice['numFactura'] . '/' . $nameFile; // Ruta donde se guardará la carpeta

    //     Storage::disk(Constants::DISK_FILES)->put($routeJson, json_encode($valueJsonInvoice)); //guardo el archivo

    //     //si la factura no existe la creo una instancia nueva
    //     if (!$ripInvoice) {
    //         $ripInvoice = RipInvoice::newModelInstance();
    //     }

    //     $ripInvoice->rip_id = $rip->id;
    //     $ripInvoice->path_json = $routeJson;
    //     $ripInvoice->path_excel = $routeXls;
    //     $ripInvoice->company_id = $rip->company_id;
    //     $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002;

    //     $ripInvoice->invoice_number = $valueJsonInvoice['numFactura'];
    //     $ripInvoice->sumVr = self::sumVrServicio($valueJsonInvoice);
    //     $ripInvoice->count_users = $valueJsonInvoice['usuarios'] ? count($valueJsonInvoice['usuarios']) : 0;
    //     $ripInvoice->note_type = $valueJsonInvoice['tipoNota'] ?? null;
    //     $ripInvoice->note_number = $valueJsonInvoice['numNota'] ?? null;
    //     $ripInvoice->status_xml = RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002;



    //     Log::info("Se ha guardado/actualizado la factura {$ripInvoice->invoice_number} del RIP {$rip->id}");

    //     RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
    // }
}
