<?php

use App\Http\Controllers\RipController;
use App\Http\Controllers\RipManualController;
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

});
