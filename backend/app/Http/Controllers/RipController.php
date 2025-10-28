<?php

namespace App\Http\Controllers;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Events\ImportProgressEvent;
use App\Events\RipValidationStatusUpdated;
use App\Enums\Rip\RipStatusEnum;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Helpers\Rips\GenerateRipInfo;
use App\Http\Requests\Rip\RipUploadFileCsvRequest;
use App\Helpers\Rips\RipsManual;
use App\Helpers\Rips\ServiceMapper;
use App\Http\Requests\Rip\Manual\RipsManualStoreInvoiceRequest;
use App\Http\Requests\Rip\Manual\RipsManualStoreUsersRequest;
use App\Http\Requests\Rip\RipUploadFileZipRequest;
use App\Http\Resources\Rip\RipPaginateResource;
use App\Http\Requests\Rip\RipCreateManualRequest;
use App\Http\Resources\Cie10\Cie10SelectInfiniteResource;
use App\Http\Resources\ConceptoRecaudo\ConceptoRecaudoSelectResource;
use App\Http\Resources\CupsRips\CupsRipsSelectInfiniteResource;
use App\Http\Resources\GrupoServicio\GrupoServicioSelectInfiniteResource;
use App\Http\Resources\ModalidadAtencion\ModalidadAtencionSelectInfiniteResource;
use App\Http\Resources\Municipio\MunicipioSelectResource;
use App\Http\Resources\Pais\PaisSelectResource;
use App\Http\Resources\RipsCausaExternaVersion2\RipsCausaExternaVersion2SelectInfiniteResource;
use App\Http\Resources\RipsFinalidadConsultaVersion2\RipsFinalidadConsultaVersion2SelectInfiniteResource;
use App\Http\Resources\RipsTipoDiagnosticoPrincipalVersion2\RipsTipoDiagnosticoPrincipalVersion2SelectInfiniteResource;
use App\Http\Resources\RipsTipoUsuarioVersion2\RipsTipoUsuarioVersion2SelectResource;
use App\Http\Resources\Servicio\ServicioSelectInfiniteResource;
use App\Http\Resources\Sexo\SexoSelectResource;
use App\Http\Resources\TipoIdPisis\TipoIdPisisSelectResource;
use App\Http\Resources\TipoNota\TipoNotaSelectResource;
use App\Http\Resources\ZonaVersion2\ZonaVersion2SelectResource;
use App\Jobs\Rips\BuildJsonJob;
use App\Jobs\Rips\GenerateExcelGlobalRipJob;
use App\Jobs\Rips\ImportCsv\ValidateStructureJob;
use App\Jobs\Rips\ProcessZipFilesJob;
use App\Jobs\Rips\RipInvoiceValidationJob;
use App\Jobs\Rips\SaveErrorsJob;
use App\Jobs\Rips\ValidateExcelJob;
use App\Jobs\Rips\ValidateRipInvoiceJob;
use App\Jobs\Rips\ValidateZipJob;
use App\Models\Cie10;
use App\Models\ConceptoRecaudo;
use App\Models\CupsRips;
use App\Models\GrupoServicio;
use App\Models\ModalidadAtencion;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\ProcessBatch;
use App\Models\RipInvoice;
use App\Models\RipsCausaExternaVersion2;
use App\Models\RipsFinalidadConsultaVersion2;
use App\Models\RipsTipoDiagnosticoPrincipalVersion2;
use App\Models\RipsTipoUsuarioVersion2;
use App\Models\Servicio;
use App\Models\Sexo;
use App\Models\TipoIdPisis;
use App\Models\TipoNota;
use App\Models\ZonaVersion2;
use App\Repositories\RipInvoiceRepository;
use App\Repositories\RipInvoiceUserRepository;
use App\Repositories\RipRepository;
use App\Repositories\ServiceVendorRepository;
use App\Services\ProcessBatchService;
use App\Services\Rips\RipsMinistryApiClient;
use App\Traits\HttpResponseTrait;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RipController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipRepository $ripRepository,
        private RipInvoiceRepository $ripInvoiceRepository,
        private RipsMinistryApiClient $ripsMinistryApiClient,
        private ServiceVendorRepository $serviceVendorRepository,
        private QueryController $queryController,
        private RipInvoiceUserRepository $ripInvoiceUserRepository,
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

            GenerateRipInfo::generateDataJsonAndExcel($rip->id, $type->value);

            return [
                'code' => 200,
                'rip' => $rip,
            ];
        });
    }

    public function getManualInfoRipInvoice($id)
    {
        return $this->execute(function () use ($id) {
            $rip = $this->ripRepository->find($id);

            $arrayData = [];

            if (isset($rip->ripInvoices) && count($rip->ripInvoices) > 0) {
                foreach ($rip->ripInvoices as $key => $value) {

                    $tipoNota = null;

                    if ($value['tipoNota']) {
                        $tipoNota = TipoNota::where('codigo', $value['tipoNota'])->first();
                    }

                    $arrayData[] = [
                        'id' => $value['id'],
                        'numDocumentoIdObligado' => $rip->nit,
                        'numFactura' => $value['invoice_number'],
                        'cantUsers' => $value['count_users'],
                        'TipoNota' => $value['tipoNota'] ? new TipoNotaSelectResource($tipoNota) : null,
                        'numNota' => $value['numNota'],
                        'sumVr' => $value['sumVr'],
                        'status_name' => $value['status']->description(),
                        'status_background' => $value['status']->backgroundColor(),
                        'xml_status_name' => $value['status_xml']->description(),
                        'xml_status_background' => $value['status_xml']->backgroundColor(),
                    ];
                }
            }

            $ripInfo = [
                "id" => $rip->id,
                "numDocumentoIdObligado" => $rip->nit,
                "numInvoices" => $rip->numInvoices,
                "failedInvoices" => $rip->failedInvoices,
                "successfulInvoices" => $rip->successfulInvoices,
                "status_backgroundColor" => $rip->status?->backgroundColor(),
                "status_description" => $rip->status?->description(),
                "sumVr" => formatNumber($rip->sumVr),
                "arrayData" => $arrayData,
            ];

            $tipoNotas = $this->queryController->selectInfinitetipoNota(request());

            return [
                'code' => 200,
                'rip_info' => $ripInfo,
                ...$tipoNotas,
            ];
        });
    }

    public function storeInvoice(RipsManualStoreInvoiceRequest $request)
    {
        return $this->runTransaction(function () use ($request) {
            $rip_id = $request->input("rip_id");
            $company_id = $request->input("company_id");
            $rip = $this->ripRepository->find($rip_id, ["ripInvoices"]);

            $invoicesData = $request->input("invoicesData", []);
            // Collection con facturas existentes indexadas por id y por invoice_number
            $existingById = $rip->ripInvoices->keyBy('id');
            $existingByNumber = $rip->ripInvoices->keyBy('invoice_number');

            $type = RipTypeEnum::RIP_TYPE_002->value;

            foreach ($invoicesData as $key => $value) {
                // normalizar valores recibidos
                $numFactura = isset($value['numFactura']) ? trim((string)$value['numFactura']) : null;
                $numFacturaOld = $value['numFactura_Old'] ?? null;
                $deleteFlag = isset($value['delete']) ? intval($value['delete']) : 0;
                $cantUsers = $value['cantUsers'] ?? 0;
                $sumVr = $value['sumVr'] ?? 0;
                $tipoNota = $value['TipoNota'] ?? null;
                $numNota = $value['numNota'] ?? null;
                $invoice_id = $value['id'] ?? null;

                if ($tipoNota) {
                    $tipoNota = TipoNota::where('id', $tipoNota['value'])->select('codigo')->first();
                    $tipoNota = $tipoNota?->codigo;
                }

                // ahora si hay deleteFlag y existe la factura -> eliminarla
                if ($deleteFlag && $invoice_id) {
                    $invoiceToDelete = $existingById->get($invoice_id);
                    if ($invoiceToDelete) {
                        // borrar archivo json asociado (si existe)
                        if (!empty($invoiceToDelete->path_json) && Storage::disk('public')->exists($invoiceToDelete->path_json)) {
                            $invoiceDir = dirname($invoiceToDelete->path_json);
                            Storage::disk('public')->deleteDirectory($invoiceDir);
                        }

                        foreach ($invoiceToDelete->ripUsers as $key => $user) {
                            $user->queries()->delete();
                            $user->procedures()->delete();
                            $user->urgencies()->delete();
                            $user->hospitalizations()->delete();
                            $user->newlyBorns()->delete();
                            $user->medicines()->delete();
                            $user->otherServices()->delete();
                        }

                        $invoiceToDelete->ripUsers()->delete();
                        $invoiceToDelete->delete();
                        // también quitarlo de las colecciones locales para que futuras iteraciones no lo usen
                        $existingById->forget($invoice_id);
                        $existingByNumber = $existingByNumber->filter(function ($i) use ($invoice_id) {
                            return $i->id !== $invoice_id;
                        })->keyBy('invoice_number');
                    }
                    continue; // siguiente elemento
                }

                // Construir contenido JSON de la factura
                $info = convertNullToEmptyString([
                    "numDocumentoIdObligado" => $value["numDocumentoIdObligado"] ?? $rip->nit,
                    "numFactura" => $numFactura,
                    "TipoNota" => $tipoNota,
                    "numNota" => $numNota,
                ]);

                // Si existe invoice_id -> actualizar (o sobrescribir JSON con usuarios del JSON anterior)
                if ($invoice_id) {
                    $invoice = $existingById->get($invoice_id) ?? $this->ripInvoiceRepository->find($invoice_id);
                    if ($invoice) {
                        // conservar usuarios si ya existe archivo json anterior
                        if (!empty($invoice->path_json) && Storage::disk('public')->exists($invoice->path_json)) {
                            $json_invoice = openFileJson($invoice->path_json);
                            $info['usuarios'] = $json_invoice['usuarios'] ?? [];
                        } else {
                            $info['usuarios'] = $value['usuarios'] ?? [];
                        }

                        // generar ruta nueva (si cambiaron número) o mantener ruta anterior si prefieres
                        $nameFile = $numFactura . '.json';
                        $ruta = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $numFactura . '/' . $nameFile;

                        // asegúrate que carpeta existe
                        Storage::disk('public')->makeDirectory(dirname($ruta));
                        Storage::disk('public')->put($ruta, json_encode($info));

                        // upsert usando tu repo (id presente provoca update)
                        $invoice = $this->ripInvoiceRepository->store([
                            'id' => $invoice->id,
                            'rip_id' => $rip_id,
                            'invoice_number' => $numFactura,
                            'company_id' => $company_id,
                            'path_json' => $ruta,
                            'status' => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002,
                            'status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002,
                            'count_users' => $cantUsers,
                            'sumVr' => $sumVr,
                            'tipoNota' => $tipoNota,
                            'numNota' => $numNota,
                        ]);

                        // actualizar colecciones locales
                        $existingById->put($invoice->id, $invoice);
                        $existingByNumber->put($invoice->invoice_number, $invoice);

                        continue;
                    }
                }

                // Si llegamos aquí y no hay invoice_id: crear nueva factura solo si no existe por número
                if (!$invoice_id) {
                    if ($numFactura && $existingByNumber->has($numFactura)) {
                        // ya existe por número: saltamos o lo tratamos como actualización (evitar duplicados)
                        // aquí optamos por actualizar el existente:
                        $existing = $existingByNumber->get($numFactura);
                        $invoice_id = $existing->id;

                        // actualizar igual que arriba (duplicando el bloque por claridad)
                        $json_invoice = [];
                        if (!empty($existing->path_json) && Storage::disk('public')->exists($existing->path_json)) {
                            $json_invoice = openFileJson($existing->path_json);
                        }
                        $info['usuarios'] = $json_invoice['usuarios'] ?? ($value['usuarios'] ?? []);

                        $nameFile = $numFactura . '.json';
                        $ruta = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $numFactura . '/' . $nameFile;
                        Storage::disk('public')->makeDirectory(dirname($ruta));
                        Storage::disk('public')->put($ruta, json_encode($info));

                        $invoice = $this->ripInvoiceRepository->store([
                            'id' => $invoice_id,
                            'rip_id' => $rip_id,
                            'invoice_number' => $numFactura,
                            'company_id' => $company_id,
                            'path_json' => $ruta,
                            'status' => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002,
                            'status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002,
                            'count_users' => $cantUsers,
                            'sumVr' => $sumVr,
                            'tipoNota' => $tipoNota,
                            'numNota' => $numNota,
                        ]);

                        $existingById->put($invoice->id, $invoice);
                        $existingByNumber->put($invoice->invoice_number, $invoice);

                        continue;
                    }

                    // crear nueva factura
                    $nameFile = $numFactura . '.json';
                    $ruta = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $numFactura . '/' . $nameFile;
                    Storage::disk('public')->makeDirectory(dirname($ruta));
                    $info['usuarios'] = $value['usuarios'] ?? [];
                    Storage::disk('public')->put($ruta, json_encode($info));

                    $invoice = $this->ripInvoiceRepository->store([
                        'id' => null,
                        'rip_id' => $rip_id,
                        'invoice_number' => $numFactura,
                        'company_id' => $company_id,
                        'path_json' => $ruta,
                        'status' => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002,
                        'status_xml' => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002,
                        'count_users' => $cantUsers,
                        'sumVr' => $sumVr,
                        'tipoNota' => $tipoNota,
                        'numNota' => $numNota,
                    ]);

                    $existingById->put($invoice->id, $invoice);
                    $existingByNumber->put($invoice->invoice_number, $invoice);
                }
            } // foreach invoicesData

            // Regenerar JSON/Excel y recargar la relación
            GenerateRipInfo::generateDataJsonAndExcel($rip_id, $type);

            $rip->load("ripInvoices");

            // Actualizar número de facturas
            $rip = $this->ripRepository->find($rip_id);
            $rip->numInvoices = count($rip->ripInvoices);
            $rip->failedInvoices = count($rip->ripInvoices);
            $rip->save();

            // Obtener la info final a devolver (ahora basada en la relación DB)
            $response = $this->getManualInfoRipInvoice($rip->id);
            $rip_info = $response->original["rip_info"];

            $req = new Request(['company_id' => $rip->company_id]);
            $tipoNotas = $this->queryController->selectInfinitetipoNota($req);


            return [
                'code' => 200,
                'message' => 'Facturas creadas exitosamente',
                'rip_info' => $rip_info,
                ...$tipoNotas,
            ];
        });
    }


    public function getManualInfoUsers($ripInvoice_id)
    {
        return $this->execute(function () use ($ripInvoice_id) {
            $ripInvoice = $this->ripInvoiceRepository->find($ripInvoice_id);

            $users = [];

            if (isset($ripInvoice->ripUsers) && count($ripInvoice->ripUsers) > 0) {

                $orderedRipUsers = collect($ripInvoice->ripUsers)
                    ->sortBy(function ($v) {
                        return (int) ($v['consecutivo'] ?? 0);
                    })
                    ->values();

                foreach ($orderedRipUsers as $key => $value) {

                    $tipoDoc = null;
                    $tipoUsuario = null;
                    $codSexo = null;
                    $codPaisResidencia = null;
                    $codMunicipioResidencia = null;
                    $codZonaTerritorialResidencia = null;

                    if ($value['tipoDocumentoIdentificacion']) {
                        $tipoDoc = TipoIdPisis::where('codigo', $value['tipoDocumentoIdentificacion'])->first();
                    }

                    if ($value['tipoUsuario']) {
                        $tipoUsuario = RipsTipoUsuarioVersion2::where('codigo', $value['tipoUsuario'])->first();
                    }

                    if ($value['codSexo']) {
                        $codSexo = Sexo::where('codigo', $value['codSexo'])->first();
                    }

                    if ($value['codPaisResidencia']) {
                        $codPaisResidencia = Pais::where('codigo', $value['codPaisResidencia'])->first();
                    }

                    if ($value['codMunicipioResidencia']) {
                        $codMunicipioResidencia = Municipio::where('codigo', $value['codMunicipioResidencia'])->first();
                    }

                    if ($value['codZonaTerritorialResidencia']) {
                        $codZonaTerritorialResidencia = ZonaVersion2::where('codigo', $value['codZonaTerritorialResidencia'])->first();
                    }

                    if ($value['codPaisOrigen']) {
                        $codPaisOrigen = Pais::where('codigo', $value['codPaisOrigen'])->first();
                    }

                    $users[] = [
                        'id' => $value['id'],
                        'tipoDocumentoIdentificacion' => $value['tipoDocumentoIdentificacion'] ? new TipoIdPisisSelectResource($tipoDoc) : null,
                        'numDocumentoIdentificacion' => $value['numDocumentoIdentificacion'],
                        'tipoUsuario' => $value['tipoUsuario'] ? new RipsTipoUsuarioVersion2SelectResource($tipoUsuario) : null,
                        'fechaNacimiento' => $value['fechaNacimiento'],
                        'codSexo' => $value['codSexo'] ? new SexoSelectResource($codSexo) : null,
                        'codPaisResidencia' => $value['codPaisResidencia'] ? new PaisSelectResource($codPaisResidencia) : null,
                        'codMunicipioResidencia' => $value['codMunicipioResidencia'] ? new MunicipioSelectResource($codMunicipioResidencia) : null,
                        'codZonaTerritorialResidencia' => $value['codZonaTerritorialResidencia'] ? new ZonaVersion2SelectResource($codZonaTerritorialResidencia) : null,
                        'incapacidad' => $value['incapacidad'],
                        'consecutivo' => $value['consecutivo'],
                        'codPaisOrigen' => $value['codPaisOrigen']  ? new PaisSelectResource($codPaisOrigen) : null,
                    ];
                }
            }

            $tipoNota = null;
            if ($ripInvoice->tipoNota) {
                $tipoNota = TipoNota::where('codigo', $ripInvoice->tipoNota)->select('codigo', 'nombre')->first();
                $tipoNota = $tipoNota->codigo . ' - ' . $tipoNota->nombre;
            }

            $ripInvoiceInfo = [
                "id" => $ripInvoice->id,
                "numDocumentoIdObligado" => $ripInvoice->rip?->nit,
                "numFactura" => $ripInvoice->invoice_number,
                "cantUsers" => $ripInvoice->count_users,
                "tipoNota" => $tipoNota,
                "numNota" => $ripInvoice->numNota,

                "users" => $users,
            ];

            $tipoIdPisis = $this->queryController->selectInfiniteTipoIdPisis(request());
            $tipoUsuarios = $this->queryController->selectInfiniteTipoUsuario(request());
            $sexos = $this->queryController->selectInfiniteSexo(request());
            $paises = $this->queryController->selectInfinitePais(request());
            $municipios = $this->queryController->selectInfiniteMunicipio(request());
            $zonaVersion2 = $this->queryController->selectInfiniteZonaVersion2(request());

            return [
                'code' => 200,
                'ripInvoice_info' => $ripInvoiceInfo,
                ...$tipoIdPisis,
                ...$tipoUsuarios,
                ...$sexos,
                ...$paises,
                ...$municipios,
                ...$zonaVersion2,
            ];
        });
    }

    public function storeUsers(RipsManualStoreUsersRequest $request)
    {
        return $this->runTransaction(function () use ($request) {

            $ripInvoice = $this->ripInvoiceRepository->find($request->input('ripInvoice_id'));

            $type = RipTypeEnum::RIP_TYPE_002->value;

            RipsManual::saveInvoiceUsersAndJson($ripInvoice, $request['usersData'] ?? [], $request['company_id'], $ripInvoice->rip, $type);

            $tipoIdPisis = $this->queryController->selectInfiniteTipoIdPisis(request());
            $tipoUsuarios = $this->queryController->selectInfiniteTipoUsuario(request());
            $sexos = $this->queryController->selectInfiniteSexo(request());
            $paises = $this->queryController->selectInfinitePais(request());
            $municipios = $this->queryController->selectInfiniteMunicipio(request());
            $zonaVersion2 = $this->queryController->selectInfiniteZonaVersion2(request());

            $response = $this->getManualInfoUsers($ripInvoice->id);
            $ripInvoice_info = $response->original["ripInvoice_info"];

            return [
                'code' => 200,
                'message' => 'Usuarios creados exitosamente',
                'ripInvoice_info' => $ripInvoice_info,
                ...$tipoIdPisis,
                ...$tipoUsuarios,
                ...$sexos,
                ...$paises,
                ...$municipios,
                ...$zonaVersion2,
            ];
        });
    }

    public function getManualInfoServices($ripInvoiceUser_id, ?string $typeService = null)
    {
        return $this->execute(function () use ($ripInvoiceUser_id, $typeService) {
            $ripInvoiceUser = $this->ripInvoiceUserRepository->find($ripInvoiceUser_id);

            // pedir servicios mapeados (por default trae todos los tipos definidos en ServiceMapper::$config)
            $services = ServiceMapper::getServicesForUser($ripInvoiceUser, $typeService);

            // servicesCount: por ejemplo conteo por tipo
            $servicesCount = [];
            foreach ($services as $k => $arr) {
                $servicesCount[$k] = count($arr);
            }

            // preparar tipoDocumento/tipoUsuario/codSexo como en tu versión original
            $tipoDocumentoIdentificacion = null;
            if ($ripInvoiceUser->tipoDocumentoIdentificacion) {
                $td = TipoIdPisis::where('codigo', $ripInvoiceUser->tipoDocumentoIdentificacion)->select('codigo', 'nombre')->first();
                $tipoDocumentoIdentificacion = $td?->codigo . ' - ' . $td?->nombre;
            }
            $tipoUsuario = null;
            if ($ripInvoiceUser->tipoUsuario) {
                $tu = RipsTipoUsuarioVersion2::where('codigo', $ripInvoiceUser->tipoUsuario)->select('codigo', 'nombre')->first();
                $tipoUsuario = $tu?->codigo . ' - ' . $tu?->nombre;
            }
            $codSexo = null;
            if ($ripInvoiceUser->codSexo) {
                $s = Sexo::where('codigo', $ripInvoiceUser->codSexo)->select('codigo', 'nombre')->first();
                $codSexo = $s?->codigo . ' - ' . $s?->nombre;
            }

            $ripInvoiceUserInfo = [
                "id" => $ripInvoiceUser->id,
                "consecutivo" => $ripInvoiceUser->consecutivo,
                "tipoDocumentoIdentificacion" => $tipoDocumentoIdentificacion,
                "numDocumentoIdentificacion" => $ripInvoiceUser->numDocumentoIdentificacion,
                "tipoUsuario" => $tipoUsuario,
                "fechaNacimiento" => $ripInvoiceUser->fechaNacimiento,
                "codSexo" => $codSexo,

                "servicios" => $services,
                "servicesCount" => $servicesCount,
            ];

            return [
                'code' => 200,
                'ripInvoiceUser_info' => $ripInvoiceUserInfo,
            ];
        });
    }

    public function ripInvoiceServicesSelectsInfinite(Request $request)
    {
        return $this->execute(function () use ($request) {

            $cupsrip = $this->queryController->selectInfiniteCupsRips(request());
            $viaIngresoUsuario = $this->queryController->selectInfiniteViaIngresoUsuario(request());
            $modalidadAtencion = $this->queryController->selectInfiniteModalidadAtencion(request());
            $grupoServicio = $this->queryController->selectInfiniteGrupoServicio(request());
            $servicios = $this->queryController->selectInfiniteServicio(request());
            $ripsFinalidadConsultaVersion2 = $this->queryController->selectInfiniteRipsFinalidadConsultaVersion2(request());
            $ripsCausaConsultaVersion2 = $this->queryController->selectInfiniteRipsCausaExternaVersion2(request());
            $cie10 = $this->queryController->selectInfiniteCie10(request());
            $ripsTipoDiagnosticoPrincipalVersion2 = $this->queryController->selectInfiniteRipsTipoDiagnosticoPrincipalVersion2(request());
            $conceptoRecaudo = $this->queryController->selectInfiniteConceptoRecaudo(request());
            $destionUsuarioEgreso = $this->queryController->selectInfiniteCondicionyDestinoUsuarioEgreso(request());
            $sexo = $this->queryController->selectInfiniteSexo(request());
            $tipoMedicamento = $this->queryController->selectInfiniteTipoMedicamentoPosVersion2(request());
            $dci = $this->queryController->selectInfiniteDci(request());
            $umm = $this->queryController->selectInfiniteUmm(request());
            $ffm = $this->queryController->selectInfiniteFfm(request());
            $upr = $this->queryController->selectInfiniteUpr(request());
            $ium = $this->queryController->selectInfiniteIum(request());
            $catalogoCum = $this->queryController->selectInfiniteCatalogoCum(request());
            $tipoOtrosServicios = $this->queryController->selectInfiniteTipoOtrosServicios(request());

            $codTecnologiaSaludables = [
                [
                    'value' => "Ium",
                    'label' => 'Ium',
                    'url' => '/selectInfiniteIum',
                    'arrayInfo' => 'ium',
                    'itemsData' => $ium['ium_arrayInfo'],
                ],
                [
                    'value' => "CatalogoCum",
                    'label' => 'CatalogoCum',
                    'url' => '/selectInfiniteCatalogoCum',
                    'arrayInfo' => 'catalogoCum',
                    'itemsData' => $catalogoCum['catalogoCum_arrayInfo'],
                ],
            ];

            return [
                'code' => 200,
                'codTecnologiaSaludables' => $codTecnologiaSaludables,
                ...$cupsrip,
                ...$viaIngresoUsuario,
                ...$modalidadAtencion,
                ...$grupoServicio,
                ...$servicios,
                ...$ripsFinalidadConsultaVersion2,
                ...$ripsCausaConsultaVersion2,
                ...$cie10,
                ...$ripsTipoDiagnosticoPrincipalVersion2,
                ...$conceptoRecaudo,
                ...$destionUsuarioEgreso,
                ...$sexo,
                ...$tipoMedicamento,
                ...$dci,
                ...$umm,
                ...$ffm,
                ...$upr,
                ...$tipoOtrosServicios,
            ];
        });
    }

    public function storeServices(Request $request)
    {
        return $this->runTransaction(function () use ($request) {
            $ripInvoice = $this->ripInvoiceRepository->find($request->input('ripInvoice_id'));
            $typeService = $request->input('typeService');

            $updatedInvoice = RipsManual::saveServicesToInvoiceAndDbMapped(
                $ripInvoice,
                $request->input('serviceData'),
                $request->input('company_id'),
                $request->input('ripInvoiceUser_id'),
                $ripInvoice->rip,
                $typeService,
                RipTypeEnum::RIP_TYPE_002->value
            );


            $response = $this->getManualInfoServices($request->input('ripInvoiceUser_id'));
            $ripInvoiceUser_info = $response->original["ripInvoiceUser_info"];

            return [
                'code' => 200,
                'message' => 'Servicios creados exitosamente',
                'ripInvoiceUser_info' => $ripInvoiceUser_info
            ];
        });
    }

    public function uploadFileCsv(RipUploadFileCsvRequest $request)
    {
        // Mantienes tu envoltura de transacción
        return $this->runTransaction(function () use ($request) {
            $company_id = $request->input('company_id');
            $user_id = $request->input('user_id');
            $uploadedFile = $request->file('file');

            // Generar batchId único
            $batchId = (string) Str::uuid();

            // Generar nombre único y ruta temporal (ya lo tenías así)
            $fileNameWithExtension = strtolower($uploadedFile->getClientOriginalName());
            $fileName = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
            $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $uniqueFileName = $fileName . '_' . time() . '.' . $fileExtension;
            $tempSubfolder = 'temp/rips/' . $batchId;
            $filePath = $uploadedFile->storeAs($tempSubfolder, $uniqueFileName, Constants::DISK_FILES);

            // Ruta completa en servidor (si la necesitas)
            $fullPath = storage_path('app/' . $filePath); // ojo: usé storage_path('app/...') para respetar disco configurado

            // Metadata que vas a guardar en Redis (igual a lo que tenías)
            $metadata = [
                'file_name' => $uniqueFileName,
                'file_size' => $uploadedFile->getSize(),
                'started_at' => now()->toDateTimeString(),
                'total_rows' => 0,
                'total_sheets' => 1,
                'current_sheet' => 1,
                'user_id' => $user_id,
                'company_id' => $company_id,
                'file_path' => $filePath, // guardar path para que jobs lo encuentren
                'type' => "ripsCsv",
            ];

            // Conexión a Redis (mantener la tuya)
            $redis = Redis::connection('redis_6380');
            $redisKey = "batch:{$batchId}:metadata";
            $redis->hmset($redisKey, $metadata);

            // Crear registro en BD (tu modelo ProcessBatch)
            ProcessBatch::create([
                'id' => $batchId,
                'batch_id' => $batchId,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'total_records' => 0,
                'error_count' => 0,
                'status' => 'active', // estado inicial
                'metadata' => json_encode($metadata),
            ]);

            // Despachar job de validación de estructura (asíncrono)
            $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_IMPORTS_RIPS_CSV);

            Bus::dispatch((new ValidateStructureJob($batchId))->onQueue($selectedQueue));

            return [
                'code' => 200,
                'message' => 'Archivo subido y encolado para validación de estructura.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }
}
