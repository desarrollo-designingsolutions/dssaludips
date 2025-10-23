<?php

use App\Http\Controllers\RipController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:rips.index'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Method ZIP
    |--------------------------------------------------------------------------
    */

    Route::get('/rip/paginate', [RipController::class, 'paginate']);

    Route::post('/rip/uploadFileZip', [RipController::class, 'uploadFileZip']);

    Route::get('/rip/downloadJson/{id}', [RipController::class, 'downloadJson']);

    Route::post('/rip/uploadExcel', [RipController::class, 'uploadExcel']);

    Route::get('/rip/downloadExcel/{id}', [RipController::class, 'downloadExcel']);

    Route::post('/rip/getValidationMetadata', [RipController::class, 'getValidationMetadata']);

    Route::post('/rip/validateRips', [RipController::class, 'validateRips']);

    Route::post('/rip/validateRipGlobal', [RipController::class, 'validateRipGlobal']);

    /*
    |--------------------------------------------------------------------------
    | Method MANUAL
    |--------------------------------------------------------------------------
    */

    Route::post('/rip/createRipManual', [RipController::class, 'createRipManual']);

    Route::get('/rip/getManualInfoRipInvoice/{rip_id}', [RipController::class, 'getManualInfoRipInvoice']);

    Route::post('/rip/storeInvoice', [RipController::class, 'storeInvoice']);

    Route::get('/rip/getManualInfoUsers/{ripInvoice_id}', [RipController::class, 'getManualInfoUsers']);

    Route::post('/rip/storeUsers', [RipController::class, 'storeUsers']);

    Route::get('/rip/ripInvoiceServicesSelectsInfinite', [RipController::class, 'ripInvoiceServicesSelectsInfinite']);

    Route::get('/rip/getManualInfoServices/{ripInvoiceUser_id}', [RipController::class, 'getManualInfoServices']);

    Route::post('/rip/storeServices', [RipController::class, 'storeServices']);


    /*
    |--------------------------------------------------------------------------
    | Method CSV
    |--------------------------------------------------------------------------
    */

    Route::post('/rip/uploadFileCsv', [RipController::class, 'uploadFileCsv']);
});
