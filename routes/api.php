<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\PresensiApiController;



/*
|--------------------------------------------------------------------------
| API ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/presensi/import', [
    PresensiApiController::class,
    'import'
]);
Route::post(
    '/presensi/upload',
    [PresensiApiController::class,'store']
);