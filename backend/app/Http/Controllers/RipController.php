<?php

namespace App\Http\Controllers;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Http\Requests\Rip\RipUploadFileZipRequest;
use App\Http\Resources\Rip\RipPaginateResource;
use App\Jobs\Rips\SaveErrorsJob;
use App\Jobs\Rips\ValidateZipJob;
use App\Models\ProcessBatch;
use App\Models\Rip;
use App\Repositories\RipRepository;
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
            ];
            $redis = Redis::connection('redis_6380');
            $redis->hmset("batch:{$batchId}:metadata", $metadata);
            $redis->hmset("rip_batch:{$batchId}", [
                'status' => 'uploaded',
                'file_path' => $filePath,
                'user_id' => $user_id,
                'company_id' => $company_id,
            ]);
            $redis->expire("rip_batch:{$batchId}", 86400);

            Log::info("ZIP uploaded for batch {$batchId}: Path {$filePath}");

            ProcessBatch::create([
                'id' => Str::uuid(),
                'batch_id' => $batchId,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'total_records' => 0,
                'error_count' => 0,
                'status' => 'active',
                'metadata' => json_encode($metadata),
            ]);

            Bus::chain([
                new ValidateZipJob($fullPath, $batchId, $user_id, $company_id),
                // new ProcessZipFilesJob($fullPath, $batchId),
                new SaveErrorsJob($batchId),
            ])
                ->catch(function (\Throwable $e) use ($batchId) {
                    Log::error("Validation failed for batch {$batchId}: {$e->getMessage()}");
                    ErrorCollector::saveErrorsToDatabase($batchId, 'failed');
                    event(new ImportProgressEvent($batchId, 0, 'Error en validación', count(ErrorCollector::getErrors($batchId)), 'failed', 'error'));
                })
                ->onQueue('import_rips')
                ->dispatch();

            return [
                'code' => 200,
                'message' => 'Archivo ZIP subido y encolado para validación.',
                'batch_id' => $batchId,
                'status' => 'success',
            ];
        });
    }
}
