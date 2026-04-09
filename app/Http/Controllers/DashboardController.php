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

        $presensiHariIni = Presensi::whereDate('tanggal', now())->count();

        $presensi = Presensi::with('karyawan')
                        ->latest()
                        ->take(5)
                        ->get();

        return view('dashboard', compact(
            'totalKaryawan',
            'totalPresensi',
            'presensiHariIni',
            'presensi'
        ));
    }
}