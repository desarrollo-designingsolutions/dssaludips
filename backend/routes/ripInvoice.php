<?php

use App\Http\Controllers\RipInvoiceController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:rips.index'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RipInvoice
    |--------------------------------------------------------------------------
    */

    Route::get('/ripInvoice/paginate', [RipInvoiceController::class, 'paginate']);

    Route::get('/ripInvoice/downloadJson/{id}', [RipInvoiceController::class, 'downloadJson']);

    Route::get('/ripInvoice/downloadExcel/{id}', [RipInvoiceController::class, 'downloadExcel']);

    Route::get('/ripInvoice/downloadXml/{id}', [RipInvoiceController::class, 'downloadXml']);

    Route::post('/ripInvoice/uploadFileXml', [RipInvoiceController::class, 'uploadFileXml']);

    Route::post('/ripInvoice/getCountRipInvoicestoValidate', [RipInvoiceController::class, 'getCountRipInvoicestoValidate']);

});
