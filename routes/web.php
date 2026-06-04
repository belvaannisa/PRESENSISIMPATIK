<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\LaporanController;

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
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [
        ProfileController::class,
        'edit'
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])->name('profile.destroy');

});



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

Route::get('/karyawan/tambah', [KaryawanController::class, 'create'])
    ->name('karyawan.tambah');

    Route::get('/karyawan/{karyawan}', [
        KaryawanController::class,
        'show'
    ])->name('karyawan.show');

});



/*
|--------------------------------------------------------------------------
| ADMIN + KEPALA PERSONALIA
|--------------------------------------------------------------------------
| FULL CRUD OPERASIONAL
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:admin,kepala_personalia'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | KARYAWAN CRUD
    |--------------------------------------------------------------------------
    */

    Route::resource('karyawan', KaryawanController::class)
        ->except(['index', 'show']);



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

    Route::post('/import-presensi', [
        PresensiController::class,
        'importLocal'
    ])->name('presensi.import');



    /*
    |--------------------------------------------------------------------------
    | UPLOAD PRESENSI MANUAL
    |--------------------------------------------------------------------------
    */

    Route::post('/upload-presensi', [
        PresensiController::class,
        'upload'
    ])->name('presensi.upload');

});



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



    /*
    |--------------------------------------------------------------------------
    | LAPORAN KETERLAMBATAN
    |--------------------------------------------------------------------------
    */

    Route::get('/laporan/keterlambatan', [
        LaporanController::class,
        'keterlambatan'
    ])->name('laporan.keterlambatan');



    Route::get('/laporan/keterlambatan/export-pdf', [
        LaporanController::class,
        'exportKeterlambatanPdf'
    ])->name('laporan.keterlambatan.exportPdf');



    /*
    |--------------------------------------------------------------------------
    | LAPORAN KEDISIPLINAN
    |--------------------------------------------------------------------------
    */

    Route::get('/laporan/kedisiplinan', [
        LaporanController::class,
        'kedisiplinan'
    ])->name('laporan.kedisiplinan');



    Route::get('/laporan/kedisiplinan/export-pdf', [
        LaporanController::class,
        'exportKedisiplinanPdf'
    ])->name('laporan.kedisiplinan.exportPdf');

});