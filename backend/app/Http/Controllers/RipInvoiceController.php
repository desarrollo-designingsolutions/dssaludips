<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Http\Requests\Rip\RipUploadFileXmlRequest;
use App\Http\Resources\RipInvoice\RipInvoicePaginateResource;
use App\Repositories\RipInvoiceRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use App\Models\ProcessBatch;
use App\Services\ProcessBatchService;
use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Jobs\Rips\ProcessXmlFileJob;
use App\Jobs\Rips\ValidationXmlFilesJob;
use App\Jobs\Rips\SaveErrorsJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RipInvoiceController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipInvoiceRepository $ripInvoiceRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceRepository->paginate($request->all());
            $tableData = RipInvoicePaginateResource::collection($data);

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


    public function downloadJson($id)
    {
        // Buscar el registro en el repositorio
        $ripInvoice = $this->ripInvoiceRepository->find($id);

        // Verificar si existe el registro
        if (!$ripInvoice) {
            return response()->json(['message' => 'Factura no encontrado.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $ripInvoice->path_json);

        // Verificar si existe el archivo JSON
        if (!$ripInvoice->path_json || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo JSON no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($ripInvoice->path_json);

        // Retornar la respuesta con el archivo JSON para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function downloadExcel($id)
    {
        // Buscar el registro en el repositorio
        $ripInvoice = $this->ripInvoiceRepository->find($id);

        // Verificar si existe el registro
        if (!$ripInvoice) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $ripInvoice->path_excel);

        // Verificar si existe el archivo Excel
        if (!$ripInvoice->path_excel || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo Excel no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($ripInvoice->path_excel);

        // Retornar la respuesta con el archivo Excel para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadXml($id)
    {
        // Buscar el registro en el repositorio
        $ripInvoice = $this->ripInvoiceRepository->find($id);

        // Verificar si existe el registro
        if (!$ripInvoice) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $ripInvoice->path_xml);

        // Verificar si existe el archivo Excel
        if (!$ripInvoice->path_xml || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo Excel no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($ripInvoice->path_xml);

        // Retornar la respuesta con el archivo Excel para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function uploadFileXml(RipUploadFileXmlRequest $request)
    {
        $company_id = $request->input('company_id');
        $user_id = $request->input('user_id');
        $uploadedFile = $request->file('file');
        $invoice_id = $request->input('invoice_id');
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
            'total_rows' => 1,
            'total_sheets' => 1,
            'current_sheet' => 1,
            'user_id' => $user_id,
            'invoice_id' => $invoice_id,
            'company_id' => $company_id,
        ];

        $redis = Redis::connection('redis_6380');
        $redis->hmset("batch:{$batchId}:metadata", $metadata);

        // Log::info("XML uploaded for batch {$batchId}: Path {$filePath}");

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
            $selectedQueue = ProcessBatchService::selectAvailableQueueRoundRobin(Constants::AVAILABLE_QUEUES_TO_IMPORTS_RIPS_XML);
            Bus::chain([
                new ValidationXmlFilesJob($fullPath, $batchId, $selectedQueue),
                new ProcessXmlFileJob($filePath, $batchId, $selectedQueue),
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
        }

        return [
            'code' => 200,
            'message' => 'Archivo XML subido y encolado para validación.',
            'batch_id' => $batchId,
            'status' => 'success',
        ];
    }



    public function getCountRipInvoicestoValidate(Request $request)
    {
        return $this->execute(function () use ($request) {

            $request->validate([
                'invoices_ids' => 'required|array',
                'invoices_ids.*' => 'string',
            ]);

            $invoices_ids = $request->input('invoices_ids');
            $countRipInvoicesWithoutXml = $this->ripInvoiceRepository->countRipInvoicesWithoutXml($invoices_ids);

            return [
                'code' => 200,
                'countRipInvoicesWithoutXml' => $countRipInvoicesWithoutXml,
                'totalInvoices' => count($invoices_ids), // Añadimos el total de facturas enviadas
            ];
        });
    }
}
