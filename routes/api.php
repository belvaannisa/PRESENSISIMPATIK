<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PresensiApiController;



/*
|--------------------------------------------------------------------------
| API ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('apikey')->group(function () {

    Route::post('/presensi/upload', [
        PresensiApiController::class,
        'upload'
    ]);

});