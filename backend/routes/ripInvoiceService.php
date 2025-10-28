<?php

use App\Http\Controllers\RipInvoiceServiceController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:rips.index'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RipInvoiceService
    |--------------------------------------------------------------------------
    */

    Route::get('/ripInvoiceService/getInfoUser/{ripInvoiceUser_id}', [RipInvoiceServiceController::class, 'getInfoUser']);

    Route::get('/ripInvoiceService/paginateQueries', [RipInvoiceServiceController::class, 'paginateQueries']);

    Route::get('/ripInvoiceService/paginateProcedures', [RipInvoiceServiceController::class, 'paginateProcedures']);

    Route::get('/ripInvoiceService/paginateUrgencies', [RipInvoiceServiceController::class, 'paginateUrgencies']);

    Route::get('/ripInvoiceService/paginateHospitalizations', [RipInvoiceServiceController::class, 'paginateHospitalizations']);

    Route::get('/ripInvoiceService/paginateNewlyBorns', [RipInvoiceServiceController::class, 'paginateNewlyBorns']);

    Route::get('/ripInvoiceService/paginateMedicines', [RipInvoiceServiceController::class, 'paginateMedicines']);

    Route::get('/ripInvoiceService/paginateOtherServices', [RipInvoiceServiceController::class, 'paginateOtherServices']);
});
