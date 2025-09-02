<?php

use App\Http\Controllers\RipController;
use App\Http\Controllers\RipManualController;
use Illuminate\Support\Facades\Route;

//Rutas protegidas
Route::middleware(['check.company:App\Repositories\RipRepository'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Method ZIP
    |--------------------------------------------------------------------------
    */

    Route::get('/rip/paginate', [RipController::class, 'paginate']);

});
