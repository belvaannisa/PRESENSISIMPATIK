<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Presensi;

class DashboardController extends Controller
{
  public function index()
  {
    $totalKaryawan = Karyawan::count();

    $totalPresensi = Presensi::count();

    $presensiHariIni = Presensi::whereDate('tanggal', today())->count();

    $tepatWaktu = Presensi::where('status', 'Tepat Waktu')->count();

    $terlambat = Presensi::where('status', 'Terlambat')->count();

    $presensi = Presensi::with('karyawan')
        ->latest()
        ->take(10)
        ->get();

    return view('dashboard', compact(
        'totalKaryawan',
        'totalPresensi',
        'presensiHariIni',
        'tepatWaktu',
        'terlambat',
        'presensi'
    ));
   }
}