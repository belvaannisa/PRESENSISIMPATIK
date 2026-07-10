<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Api\PresensiApiController;



use App\Http\Controllers\Auth\AuthenticatedSessionController;



/*
|--------------------------------------------------------------------------
| HALAMAN AWAL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return redirect()->route('login');

});
Route::get('/tes', function () {
    return 'TES BERHASIL';
});
Route::get('/tes-create', [KaryawanController::class, 'create']);



/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';



/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy'
])->name('logout');



/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
| Semua role bisa akses dashboard
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'role:admin,kepala_personalia,pimpinan'
])->group(function () {

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ])->name('dashboard');

});



/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DATA USER
    |--------------------------------------------------------------------------
    */

    // Route::resource('users', UserController::class);

});



/*
|--------------------------------------------------------------------------
| VIEW DATA KARYAWAN
|--------------------------------------------------------------------------
| Semua role bisa melihat data karyawan
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,pimpinan'
])->group(function () {

    Route::get('/karyawan', [
        KaryawanController::class,
        'index'
    ])->name('karyawan.index');


});

    // Admin & Kepala Personalia
    Route::middleware(['auth','role:admin,kepala_personalia'])->group(function () {

        Route::get('/karyawan/tambah', [KaryawanController::class, 'create'])
            ->name('karyawan.tambah');

        Route::post('/karyawan/simpan', [KaryawanController::class, 'store'])
            ->name('karyawan.simpan');

        Route::get('/karyawan/{karyawan}/edit', [KaryawanController::class, 'edit'])
            ->name('karyawan.edit');

        Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])
            ->name('karyawan.update');

        Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])
            ->name('karyawan.destroy');

        Route::get('/karyawan/{id}/pdf', [KaryawanController::class,'pdf'])
            ->name('karyawan.pdf');

    });

    /*
    |--------------------------------------------------------------------------
    | Semua role bisa melihat data karyawan
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth',
        'role:admin,kepala_personalia,pimpinan'
    ])->group(function () {

        Route::get('/karyawan', [KaryawanController::class, 'index'])
            ->name('karyawan.index');

        Route::get('/karyawan/{id}/detail', [KaryawanController::class, 'detail'])
            ->name('karyawan.detail');



    });

    /*
    |--------------------------------------------------------------------------
    | Admin + Kepala Personalia
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth',
        'role:admin,kepala_personalia'
    ])->group(function () {

        Route::resource('karyawan', KaryawanController::class)
            ->except(['index', 'show']);

    });

    /*
    |--------------------------------------------------------------------------
    | PRESENSI FULL CRUD
    |--------------------------------------------------------------------------
    */

    Route::resource('presensi', PresensiController::class);



    /*
    |--------------------------------------------------------------------------
    | ABSEN KELUAR
    |--------------------------------------------------------------------------
    */

    Route::get('/presensi/keluar/{id}', [
        PresensiController::class,
        'absenKeluar'
    ])->name('presensi.keluar');



    /*
    |--------------------------------------------------------------------------
    | IMPORT PRESENSI OTOMATIS
    |--------------------------------------------------------------------------
    */

        Route::post(
            '/presensi/auto-import',
            [PresensiController::class,'importLocal']
        )->name('presensi.autoimport');

        Route::post(
            '/presensi/import',
            [PresensiController::class,
            'importLocal']
        )->name('presensi.import');

    /*
    |--------------------------------------------------------------------------
    | UPLOAD PRESENSI MANUAL
    |--------------------------------------------------------------------------
    */

    Route::post('/upload-presensi', [
        PresensiController::class,
        'upload'
    ])->name('presensi.upload');
    
    /*
    |--------------------------------------------------------------------------
    | LAPORAN
    |--------------------------------------------------------------------------
    | Semua role bisa melihat laporan
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth',
        'role:admin,kepala_personalia,pimpinan'
    ])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | LAPORAN PRESENSI
    |--------------------------------------------------------------------------
    */

    Route::get('/laporan/presensi', [
        LaporanController::class,
        'presensi'
    ])->name('laporan.presensi');



    Route::get('/laporan/presensi/export-pdf', [
        LaporanController::class,
        'exportPdf'
    ])->name('laporan.presensi.exportPdf');

});


Route::middleware('apikey')->group(function () {

    Route::post(
        '/presensi/upload',
        [PresensiApiController::class,'upload']
    );

});