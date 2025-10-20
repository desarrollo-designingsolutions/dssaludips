<?php

use App\Http\Controllers\ProcessBatchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ProcessBatch
|--------------------------------------------------------------------------
*/

Route::get('/processBatch/errorsPaginate', [ProcessBatchController::class, 'paginate']);

Route::get('/processBatch/getUserProcesses/{id}', [ProcessBatchController::class, 'getUserProcesses']);

Route::post('/processBatch/generateCsvReportErrors', [ProcessBatchController::class, 'generateCsvReportErrors']);

Route::post('/processBatch/generateExcelReportData', [ProcessBatchController::class, 'generateExcelReportData']);


