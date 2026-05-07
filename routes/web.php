<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
Route::get('/', function () {
    return redirect()->route('login');
}); 


Route::resource('karyawan', KaryawanController::class)->middleware('auth');
Route::resource('presensi', PresensiController::class)->middleware('auth');

// route tambahan absen keluar
Route::get('/presensi/keluar/{id}', [PresensiController::class, 'absenKeluar'])
    ->name('presensi.keluar');

Route::middleware(['auth', 'role:pimpinan'])->group(function () {
    Route::get('/laporan', [PresensiController::class, 'index']);
});

Route::middleware('auth')->group(function () {

    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');

    // AUTO
    Route::post('/import-presensi', [PresensiController::class, 'importLocal'])
    ->name('presensi.import');

    // MANUAL
    Route::post('/upload-presensi', [PresensiController::class, 'upload'])
        ->name('presensi.upload');
}); 
Route::middleware('auth')->group(function () {

    Route::get('/laporan/presensi', [LaporanController::class, 'presensi'])
        ->name('laporan.presensi');

    Route::get('/laporan/presensi/export-pdf', [LaporanController::class, 'exportPdf'])
        ->name('laporan.presensi.exportPdf');


    Route::get('/laporan/keterlambatan', [LaporanController::class, 'keterlambatan'])
        ->name('laporan.keterlambatan');

    Route::get('/laporan/keterlambatan/export-pdf', [LaporanController::class, 'exportKeterlambatanPdf'])
        ->name('laporan.keterlambatan.exportPdf');


    Route::get('/laporan/kedisiplinan', [LaporanController::class, 'kedisiplinan'])
        ->name('laporan.kedisiplinan');

    Route::get('/laporan/kedisiplinan/export-pdf', [LaporanController::class, 'exportKedisiplinanPdf'])
        ->name('laporan.kedisiplinan.exportPdf');
});