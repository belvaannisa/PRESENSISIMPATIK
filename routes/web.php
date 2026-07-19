<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Api\PresensiApiController;

/*
|--------------------------------------------------------------------------
| HALAMAN AWAL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';

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
*/

Route::middleware([
    'auth',
    'verified',
    'role:admin,kepala_personalia,haf,pimpinan'
])->group(function () {

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ])->name('dashboard');

});

/*
|--------------------------------------------------------------------------
| KARYAWAN (LIHAT)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,haf,pimpinan'
])->group(function () {

    Route::get('/karyawan', [
        KaryawanController::class,
        'index'
    ])->name('karyawan.index');

    Route::get('/karyawan/{id}/detail', [
        KaryawanController::class,
        'detail'
    ])->name('karyawan.detail');

    Route::get('/karyawan/{id}/pdf', [
        KaryawanController::class,
        'exportDetailPdf'
    ])->name('karyawan.pdf');

});

/*
|--------------------------------------------------------------------------
| KARYAWAN (CRUD)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,haf'
])->group(function () {

    Route::get('/karyawan/tambah', [
        KaryawanController::class,
        'create'
    ])->name('karyawan.tambah');

    Route::post('/karyawan', [
        KaryawanController::class,
        'store'
    ])->name('karyawan.store');

    Route::get('/karyawan/{karyawan}/edit', [
        KaryawanController::class,
        'edit'
    ])->name('karyawan.edit');

    Route::put('/karyawan/{karyawan}', [
        KaryawanController::class,
        'update'
    ])->name('karyawan.update');

    Route::delete('/karyawan/{karyawan}', [
        KaryawanController::class,
        'destroy'
    ])->name('karyawan.destroy');
Route::post('/karyawan/pengaturan-jam', [App\Http\Controllers\KaryawanController::class, 'updatePengaturanJam'])->name('karyawan.update_pengaturan_jam');
});

/*
|--------------------------------------------------------------------------
| PRESENSI (LIHAT)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,haf,pimpinan'
])->group(function () {

    Route::get('/presensi', [
        PresensiController::class,
        'index'
    ])->name('presensi.index');

    Route::get('/presensi/{presensi}/edit', [
        PresensiController::class,
        'edit'
    ])->name('presensi.edit');

    Route::put('/presensi/{presensi}', [
        PresensiController::class,
        'update'
    ])->name('presensi.update');

    Route::delete('/presensi/{presensi}', [
        PresensiController::class,
        'destroy'
    ])->name('presensi.destroy');

});

/*
|--------------------------------------------------------------------------
| IMPORT PRESENSI
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,haf'
])->group(function () {

    Route::post('/presensi/import', [
        PresensiController::class,
        'importLocal'
    ])->name('presensi.import');

    Route::post('/presensi/upload-file', [
        PresensiController::class,
        'upload'
    ])->name('presensi.upload');

});

/*
|--------------------------------------------------------------------------
| LAPORAN
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia,haf,pimpinan'
])->group(function () {

    Route::get('/laporan/presensi', [
        LaporanController::class,
        'presensi'
    ])->name('laporan.presensi');

    Route::get('/laporan/presensi/export-pdf', [
        LaporanController::class,
        'exportPdf'
    ])->name('laporan.presensi.exportPdf');

});

/*
|--------------------------------------------------------------------------
| API Fingerprint
|--------------------------------------------------------------------------
*/

Route::middleware('apikey')->group(function () {

    Route::post('/presensi/upload', [
        PresensiApiController::class,
        'upload'
    ]);

});