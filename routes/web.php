<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;


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

Route::resource('karyawan', KaryawanController::class)->middleware('auth');
Route::resource('presensi', PresensiController::class)->middleware('auth');

// route tambahan absen keluar
Route::get('/presensi/keluar/{id}', [PresensiController::class, 'absenKeluar'])
    ->name('presensi.keluar');

Route::middleware(['auth', 'role:pimpinan'])->group(function () {
    Route::get('/laporan', [PresensiController::class, 'index']);
});
