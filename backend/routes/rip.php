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
});
