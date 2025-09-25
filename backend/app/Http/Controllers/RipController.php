<?php

namespace App\Http\Controllers;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipValidationStatusUpdated;
use App\Enums\Rip\RipStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Events\RipRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\ExcelRequired;
use App\Helpers\Rips\GenerateRipInfo;
use App\Http\Requests\Rip\RipUploadFileZipRequest;
use App\Http\Resources\Rip\RipPaginateResource;
use App\Jobs\Rips\BuildJsonJob;
use App\Jobs\Rips\ProcessZipFilesJob;
use App\Jobs\Rips\SaveErrorsJob;
use App\Jobs\Rips\ValidateRipInvoiceJob;
use App\Jobs\Rips\ValidateZipJob;
use App\Models\ProcessBatch;
use App\Models\RipInvoice;
use App\Repositories\RipInvoiceRepository;
use App\Repositories\RipRepository;
use App\Services\ProcessBatchService;
use App\Services\Rips\RipsMinistryApiClient;
use App\Traits\HttpResponseTrait;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RipController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipRepository $ripRepository,
        private RipInvoiceRepository $ripInvoiceRepository,
        private RipsMinistryApiClient $ripsMinistryApiClient,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripRepository->paginate($request->all());
            $tableData = RipPaginateResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function uploadFileZip(RipUploadFileZipRequest $request)
    {
        return $this->runTransaction(function () use ($request) {
            $company_id = $request->input('company_id');
            $user_id = $request->input('user_id');
            $uploadedFile = $request->file('file');
            $batchId = Str::uuid();

            $fileNameWithExtension = strtolower($uploadedFile->getClientOriginalName());
            $fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
            $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $uniqueFileName = $fileName . '_' . time() . '.' . $fileExtension;
            $tempSubfolder = 'temp/rips/' . $batchId;
            $filePath = $uploadedFile->storeAs($tempSubfolder, $uniqueFileName, Constants::DISK_FILES);
            $fullPath = storage_path('app/public/' . $filePath);

            $metadata = [
                'file_name' => $uniqueFileName,
                'file_size' => $uploadedFile->getSize(),
                'started_at' => now()->toDateTimeString(),
                'total_rows' => 0,
                'total_sheets' => 1,
                'current_sheet' => 1,
                'user_id' => $user_id,
                'company_id' => $company_id,
            ];
            $redis = Redis::connection('redis_6380');
            $redis->hmset("batch:{$batchId}:metadata", $metadata);
            $redis->hmset("rip_batch:{$batchId}", [
                'status' => 'uploaded',
                'file_path' => $filePath,
                'user_id' => $user_id,
                'company_id' => $company_id,
                'process_batch_id' => $batchId,
                'type' => RipTypeEnum::RIP_TYPE_001->value,
            ]);
            $redis->expire("rip_batch:{$batchId}", 86400);

            // Log::info("ZIP uploaded for batch {$batchId}: Path {$filePath}");

            ProcessBatch::create([
                'id' => $batchId,
                'batch_id' => $batchId,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'total_records' => 0,
                'error_count' => 0,
                'status' => 'active',
                'metadata' => json_encode($metadata),
            ]);

            try {
                // Seleccionar una cola disponible
                $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_IMPORTS_RIPS_ZIP);
                logMessage("Selected queue for batch {$batchId}: {$selectedQueue}");

                Bus::chain([
                    new ValidateZipJob($fullPath, $batchId, $user_id, $company_id, $selectedQueue),
                    new ProcessZipFilesJob($fullPath, $batchId, $selectedQueue),
                    new SaveErrorsJob($batchId, $selectedQueue),
                    new BuildJsonJob($batchId, $selectedQueue),
                ])
                    ->catch(function (\Throwable $e) use ($batchId, $selectedQueue) {
                        Log::error("Validation failed for batch {$batchId}: {$e->getMessage()}");
                        ErrorCollector::saveErrorsToDatabase($batchId, 'failed');
                        event(new ImportProgressEvent($batchId, 0, 'Error en validación', count(ErrorCollector::getErrors($batchId)), 'failed', 'error'));
                    })
                    ->onQueue($selectedQueue)
                    ->dispatch();
            } catch (\Exception $e) {
                Log::error("No se pudo seleccionar una cola disponible: " . $e->getMessage());
                // Manejar el error (ej: reintentar o notificar al usuario)
            }


            return [
                'code' => 200,
                'message' => 'Archivo ZIP subido y encolado para validación.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }

    public function downloadJson($id)
    {
        // Buscar el registro en el repositorio
        $rip = $this->ripRepository->find($id);

        // Verificar si existe el registro
        if (!$rip) {
            return response()->json(['message' => 'RIP no encontrado.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $rip->path_json);

        // Verificar si existe el archivo JSON
        if (!$rip->path_json || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo JSON no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($rip->path_json);

        // Retornar la respuesta con el archivo JSON para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function downloadExcel($id)
    {
        // Buscar el registro en el repositorio
        $rip = $this->ripRepository->find($id);

        // Verificar si existe el registro
        if (!$rip) {
            return response()->json(['message' => 'RIP no encontrado.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $rip->path_excel);

        // Verificar si existe el archivo Excel
        if (!$rip->path_excel || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo Excel no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($rip->path_excel);

        // Retornar la respuesta con el archivo Excel para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function getValidationMetadata(Request $request)
    {
        return $this->execute(function () use ($request) {
            $request->validate(['ids' => 'required|array']);
            $invoices = [];

            foreach ($request->ids as $id) {
                $invoice = $this->ripInvoiceRepository->find($id, select: ["id", "validation_metadata", "invoice_number", "status"]);
                if ($invoice) {
                    $metadata = null;
                    if ($invoice->validation_metadata) {
                        $metadata = json_decode($invoice->validation_metadata, true);
                    }

                    $invoices[] = [
                        "id" => $invoice->id,
                        "invoice_number" => $invoice->invoice_number,
                        "metadata" => $metadata,
                        "status" => $invoice->status,
                        "status_backgroundColor" => $invoice->status?->backgroundColor(),
                        "status_description" => $invoice->status?->description(),
                    ];
                }
            }

            return [
                "code" => 200,
                "invoices" => $invoices  // Cambiar esto: poner los invoices dentro de 'data'
            ];
        });
    }

    public function validateRips(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $invoiceIds = $request->ids;
        $batchId = uniqid('batch_' . time() . '_');

        // Seleccionar una cola disponible
        $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_VALIDATION_RIPS_MINISTERY);

        $jobs = collect($invoiceIds)->map(function ($invoiceId) use ($batchId) {
            return new ValidateRipInvoiceJob($invoiceId, $batchId);
        })->toArray();

        $batch = Bus::batch($jobs)
            ->before(function () use ($invoiceIds, $batchId) {
                // ✅ ANTES de que empiece el batch: cambiar todas a estado 5 visualmente
                foreach ($invoiceIds as $invoiceId) {
                    event(new RipValidationStatusUpdated(
                        $invoiceId,
                        RipInvoiceStatusEnum::RIP_INVOICE_STATUS_005,
                        null,
                        null,
                        $batchId,
                    ));
                }

                $this->ripInvoiceRepository->changeStateArray($invoiceIds, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006, "status");
            })
            ->then(function (Batch $batch) use ($batchId) {
                // Opcional: evento de batch completado (podrías enviarlo a un canal general)
            })
            ->name("RIP Validation Batch {$batchId}")
            ->onQueue($selectedQueue)
            ->dispatch();

        return response()->json([
            'code' => 200,
            'batch_id' => $batchId,
            'batch_job_id' => $batch->id,
            'message' => 'Validación en proceso',
            'total_jobs' => count($jobs),
            'invoice_ids' => $invoiceIds // Para que el frontend sepa qué facturas escuchar
        ]);
    }

    public function uploadExcel(Request $request)
    {
        return $this->runTransaction(function () use ($request) {
            $batchId    = Str::uuid();
            $excelFile  = $request->file('file');
            $invoice_id = $request->input('invoice_id');   // => modo independiente si viene
            $rip_id     = $request->input('rip_id');       // => modo global si NO hay invoice_id
            $user_id    = $request->input('user_id');
            $company_id = $request->input('company_id');
            $ripInvoice = null;
            $rip        = null;

            $xlsCollection = ExcelRequired::openXls($excelFile);
            if ($xlsCollection->isEmpty()) {
                return ['code' => 422, 'status' => 'error', 'message' => 'El archivo no contiene filas.'];
            }

            // Validar estructura mínima (encabezados). Sin helpers extra.
            $required = ['num_factura', 'id_usuario', 'num_identificacion', 'id_servicio', 'servicio', 'campo', 'valor'];
            $normalize = fn($s) => Str::of($s)->lower()->replace(' ', '')->toString();
            $headers = collect(array_keys($xlsCollection->first()))->map($normalize);
            $missing = collect($required)->diff($headers);
            if ($missing->isNotEmpty()) {
                // Convierte claves faltantes a nombres legibles
                $cols = $missing->map(fn($k) => $k)->values()->all();

                // Formatea: "a, b y c"
                $last = array_pop($cols);
                $colsStr = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                return [
                    'code'    => 422,
                    'status'  => 'error',
                    'message' => "Estructura inválida en el Excel. Faltan columnas requeridas: {$colsStr}.",
                ];
            }

            // Agrupar por número de factura
            $groupedByFactura = ExcelRequired::groupByNumFactura($xlsCollection); // Collection keyed by num_facti

            // Lógica de alcance
            if ($invoice_id) { // INDEPENDIENTE: solo la factura seleccionada
                $ripInvoice = $this->ripInvoiceRepository->find($invoice_id, "rip");
                $rip = $ripInvoice->rip;
                if (!$ripInvoice) {
                    return ['code' => 404, 'status' => 'error', 'message' => 'Factura seleccionada no existe.'];
                }
                $numFacturaSel = (string) $ripInvoice->invoice_number;

                if (!$groupedByFactura->has($numFacturaSel)) {
                    return [
                        'code'    => 422,
                        'status' => 'error',
                        'message' => 'La factura seleccionada no aparece en el Excel.',
                    ];
                }

                // Deja en $xlsCollection SOLO ese grupo (aunque el archivo sea masivo)
                $xlsCollection = collect([$numFacturaSel => $groupedByFactura->get($numFacturaSel)]);
            } else {
                // GLOBAL: valida que TODAS las facturas del Excel existan y pertenezcan al RIP
                $rip = $this->ripRepository->find($rip_id);
                if (!$rip) {
                    return ['code' => 404, 'status' => 'error', 'message' => 'RIP no encontrado.'];
                }

                // Facturas detectadas en el Excel (claves del groupBy)
                $excelInvoices = $groupedByFactura->keys()->map(fn($n) => (string) $n)->values();

                if ($excelInvoices->isEmpty()) {
                    return ['code' => 422, 'status' => 'error', 'message' => 'No se encontraron números de factura en el Excel.'];
                }

                // --- 1) Verificar que EXISTAN en BD (en cualquier RIP) ---
                $existingAny = RipInvoice::query()
                    ->pluck('invoice_number')
                    ->map(fn($n) => (string) $n);

                $notFound = $excelInvoices->diff($existingAny);

                if ($notFound->isNotEmpty()) {
                    // formatea "a, b y c"
                    $cols = $notFound->values()->all();
                    $last = array_pop($cols);
                    $list = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                    return [
                        'code'    => 422,
                        'status'  => 'error',
                        'message' => "Las siguientes facturas del Excel no existen en el sistema: {$list}.",
                    ];
                }

                // --- 2) Verificar que PERTENEZCAN al RIP seleccionado ---
                $validForRip = RipInvoice::query()
                    ->where('rip_id', $rip_id)
                    ->pluck('invoice_number')
                    ->map(fn($n) => (string) $n);

                $foreign = $excelInvoices->diff($validForRip);
                if ($foreign->isNotEmpty()) {
                    $cols = $foreign->values()->all();
                    $last = array_pop($cols);
                    $list = $last ? (count($cols) ? implode(', ', $cols) . ' y ' . $last : $last) : '';

                    return [
                        'code'    => 422,
                        'status'  => 'error',
                        'message' => "El Excel contiene facturas que no pertenecen al RIP seleccionado: {$list}.",
                    ];
                }

                $xlsCollection = $groupedByFactura;
            }

            if ($invoice_id) {
                $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
                $ripInvoice->save();
                RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
                $jsonData = openFileJson($ripInvoice->path_json);
                $jsonData = [$jsonData]; // aqui lo hacemos asi para que me siga funcionando la funcion proccessData cunado es independiente


            } else {
                $rip->status = RipStatusEnum::RIP_STATUS_004->value;
                $rip->save();
                RipRowUpdatedNow::dispatch($rip->id);
                $jsonData = openFileJson($rip->path_json);



                $firstKey = $xlsCollection->keys()->first();
                if (is_int($firstKey)) {
                    $xlsCollection = $xlsCollection->groupBy(fn($r) => (string)($r['num_factura'] ?? $r['num_facti'] ?? ''));
                }

                $invoiceNumbers = $xlsCollection->keys()->map(fn($n) => (string) $n)->values();

                $idsByNumber = RipInvoice::query()
                    ->where('rip_id', $rip_id)
                    ->whereIn('invoice_number', $invoiceNumbers)
                    ->pluck('id', 'invoice_number');

                foreach ($xlsCollection as $numFactura => $rows) {
                    $ripInvoiceId = $idsByNumber->get((string) $numFactura);
                    $ripInvoice = $this->ripInvoiceRepository->find($ripInvoiceId);

                    $ripInvoice->status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_004->value;
                    $ripInvoice->save();
                    RipInvoiceRowUpdatedNow::dispatch($ripInvoice->id);
                }
            }

            //aqui pasamos toda la data encontrada en el archivo xls al array general de las facturas
            $jsonInfo = ExcelRequired::processData($jsonData, $xlsCollection);

            $validationExcel = ExcelRequired::validateDataFilesExcel($jsonInfo, $jsonData);

            if ($validationExcel['totalErrorMessages'] > 0) {
                return [
                    'code'    => 422,
                    'status'  => 'error',
                    'message' => "Se encontraron {$validationExcel['totalErrorMessages']} errores en la validacion del excel.",
                ];
            }

            //Aqui se traspasa la informacion que esta bien segun las validaciones de excel
            $jsonInvoices = $jsonData;
            foreach ($jsonInvoices as $key => $value) {
                DB::beginTransaction();
                //se guarda el xls nuevo y json independientes en la bd
                GenerateRipInfo::saveReloadDataInvoice($rip->id, $value, $validationExcel['totalErrorMessages']);

                DB::commit();
            }

            //informacion del resultado de las validaciones Excel

            GenerateRipInfo::generateDataJsonAndExcel($rip->id);
            ExcelRequired::validateRipsStatus($rip->id);
            RipRowUpdatedNow::dispatch($rip->id);

            return $xlsCollection;

            // // $fileNameWithExtension = strtolower($excelFile->getClientOriginalName());
            // // $fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
            // // $fileExtension = strtolower($excelFile->getClientOriginalExtension());
            // // $uniqueFileName = $fileName . '_' . time() . '.' . $fileExtension;
            // // $tempSubfolder = 'temp/rips/' . $batchId;
            // // $filePath = $excelFile->storeAs($tempSubfolder, $uniqueFileName, Constants::DISK_FILES);
            // // $fullPath = storage_path('app/public/' . $filePath);

            // // // abrimos el archivo xls
            // // // Obtener los datos procesados del CSV como una colección

            // return $metadata = [
            //     'file_name' => $uniqueFileName,
            //     'file_size' => $excelFile->getSize(),
            //     'started_at' => now()->toDateTimeString(),
            //     'total_rows' => 0,
            //     'user_id' => $user_id,
            //     'company_id' => $company_id,
            //     'rip' => $rip,
            //     'ripInvoice' => $ripInvoice,
            //     'xlsCollection' => $xlsCollection
            // ];
            // $redis = Redis::connection('redis_6380');
            // $redis->hmset("batch:{$batchId}:metadata", $metadata);
            // // $redis->hmset("batch:{$batchId}:xlsCollection", $xlsCollection);

            // ProcessValidationExcel::dispatch([
            //     'rip_id' => $request->input('rip_id'),
            //     'invoice_id' => $request->input('invoice_id'),
            //     'xlsCollection' => $xlsCollection,
            // ], $this->auth);

            return [
                'code' => 200,
                'message' => 'Archivo Excel subido y encolado para procesar.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }
}
