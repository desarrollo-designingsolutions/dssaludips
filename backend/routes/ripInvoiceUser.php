<?php

use App\Http\Controllers\RipInvoiceUserController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.permission:rips.index'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RipInvoiceUser
    |--------------------------------------------------------------------------
    */

    Route::get('/ripInvoiceUser/paginate', [RipInvoiceUserController::class, 'paginate']);

    Route::get('/ripInvoiceUser/getInfoInvoice/{ripInvoice_id}', [RipInvoiceUserController::class, 'getInfoInvoice']);

});
