<?php

namespace App\Http\Controllers;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipValidationStatusUpdated;
use App\Enums\Rip\RipStatusEnum;
use App\Events\RipInvoiceRowUpdatedNow;
use App\Events\RipRowUpdatedNow;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\ExcelRequired;
use App\Helpers\Rips\GenerateRipInfo;
use App\Http\Requests\Rip\RipUploadFileZipRequest;
use App\Http\Resources\Rip\RipPaginateResource;
use App\Http\Requests\Rip\RipCreateManualRequest;
use App\Jobs\Rips\BuildJsonJob;
use App\Jobs\Rips\GenerateExcelGlobalRipJob;
use App\Jobs\Rips\ProcessZipFilesJob;
use App\Jobs\Rips\RipInvoiceValidationJob;
use App\Jobs\Rips\SaveErrorsJob;
use App\Jobs\Rips\ValidateExcelJob;
use App\Jobs\Rips\ValidateRipInvoiceJob;
use App\Jobs\Rips\ValidateZipJob;
use App\Models\ProcessBatch;
use App\Models\RipInvoice;
use App\Repositories\RipInvoiceRepository;
use App\Repositories\RipRepository;
use App\Repositories\ServiceVendorRepository;
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
        private ServiceVendorRepository $serviceVendorRepository,
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
                $invoice = $this->ripInvoiceRepository->find($id, select: ["id", "validation_metadata", "invoice_number", "status", "path_excel"]);
                if ($invoice) {
                    $metadata = null;
                    if ($invoice->validation_metadata) {
                        $metadata = json_decode($invoice->validation_metadata, true);
                    }

                    $invoices[] = [
                        "id" => $invoice->id,
                        "invoice_number" => $invoice->invoice_number,
                        "path_excel" => $invoice->path_excel,
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
        $company_id = $request->input('company_id');
        $user_id = $request->input('user_id');
        $request->validate(['ids' => 'required|array']);
        $invoiceIds = $request->ids;
        $batchId = uniqid('batch_' . time() . '_');
        $ripInvoice = $this->ripInvoiceRepository->find($invoiceIds[0]);

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
            ->then(function (Batch $batch) use ($batchId, $ripInvoice, $company_id, $user_id, $selectedQueue) {
                dispatch(new GenerateExcelGlobalRipJob($batchId, $ripInvoice->rip, $company_id, $user_id, $selectedQueue));
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
            // Seleccionar una cola disponible
            $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_IMPORTS_RIPS_EXCEL);
            $ripInvoice = null;
            $rip        = null;

            $fileNameWithExtension = strtolower($excelFile->getClientOriginalName());
            $fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
            $fileExtension = strtolower($excelFile->getClientOriginalExtension());
            $uniqueFileName = $fileName . '_' . time() . '.' . $fileExtension;
            $tempSubfolder = 'temp/rips/' . $batchId;
            $filePath = $excelFile->storeAs($tempSubfolder, $uniqueFileName, Constants::DISK_FILES);

            // $xlsCollection = ExcelRequired::openXls($excelFile);
            $required = ['num_factura', 'id_usuario', 'num_identificacion', 'id_servicio', 'servicio', 'campo', 'valor'];

            if ($invoice_id) {
                $ripInvoice = $this->ripInvoiceRepository->find($invoice_id, "rip");
                $rip_id = $ripInvoice->rip_id;
            }

            $rip = $this->ripRepository->find($rip_id);

            $metadata = [
                'file_name' => $uniqueFileName,
                'file_size' => $excelFile->getSize(),
                'started_at' => now()->toDateTimeString(),
                'total_rows' => 0,
                'user_id' => $user_id,
                'company_id' => $company_id,
                'rip' => json_encode($rip),
                'rip_id' => $rip_id,
                'ripInvoice' => json_encode($ripInvoice),
                'ripInvoice_id' => $invoice_id,
                'excelFile' => $excelFile,
                'filePath' => $filePath,
                'required' => json_encode($required)
            ];


            $redis = Redis::connection('redis_6380');
            $redis->hmset("batch:{$batchId}:metadata", $metadata);

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

            Bus::chain([
                new ValidateExcelJob($batchId, $selectedQueue),
                new RipInvoiceValidationJob($batchId, $selectedQueue),
            ])
                ->catch(function (\Throwable $e) use ($batchId) {
                    Log::error("Validation failed for batch {$batchId}: {$e->getMessage()}");
                    ErrorCollector::saveErrorsToDatabase($batchId, 'failed');
                    event(new ImportProgressEvent($batchId, 0, 'Error en validación', count(ErrorCollector::getErrors($batchId)), 'failed', 'error'));
                })
                ->onQueue($selectedQueue) // ¡Importante!
                ->dispatch();

            return [
                'code' => 200,
                'message' => 'Archivo Excel subido y encolado para validación.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }

    public function validateRipGlobal(Request $request)
    {
        $ripId = $request->input('rip_id');
        $company_id = $request->input('company_id');
        $user_id = $request->input('user_id');
        $invoiceIds = RipInvoice::where('rip_id', $ripId)->pluck('id')->toArray();

        if (empty($invoiceIds)) {
            return response()->json([
                'code' => 404,
                'message' => 'El RIP no tiene facturas asociadas para validar.'
            ], 404);
        }

        // Crear una Request interna con el payload que espera validateRips: ['ids' => [...]]
        $forward = Request::create('/rip/validateRips', 'POST', ['ids' => $invoiceIds, 'company_id' => $company_id, 'user_id' => $user_id]);

        // Conservar el contexto de usuario/autenticación/resolución de ruta
        $forward->setUserResolver($request->getUserResolver());
        $forward->setRouteResolver($request->getRouteResolver());

        return $this->validateRips($forward);
    }


    public function createRipManual(RipCreateManualRequest $request)
    {
        return $this->runTransaction(function () use ($request) {

            $companyId = $request->input('company_id');
            $userId = $request->input('user_id');
            $serviceVendorId = $request->input('service_vendor_id');
            $serviceVendor = $this->serviceVendorRepository->find($serviceVendorId);

            $status = RipStatusEnum::RIP_STATUS_002;
            $type = RipTypeEnum::RIP_TYPE_002;

            $rip = $this->ripRepository->store([
                'company_id' => $companyId,
                'user_id' => $userId,
                'process_batch_id' => null,
                'path_zip' => null,
                'nit' => $serviceVendor->nit,
                'numInvoices' => 0,
                'successfulInvoices' => 0,
                'failedInvoices' => 0,
                'type' => $type,
                'sumVr' => 0,
                'status' => $status,
            ]);

            return [
                'code' => 200,
                'rip' => $rip,
            ];
        });
    }

}
