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




});
