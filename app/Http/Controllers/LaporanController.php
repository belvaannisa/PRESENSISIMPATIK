<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function presensi(Request $request)
    {
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

        // ===============================
        // HARIAN
        // ===============================
        if ($mode == 'harian') {

            $data = Presensi::with('karyawan')
                ->where('tanggal', 'LIKE', $tanggal.'%')
                ->get();

            return view('laporan.presensi.index', compact('data', 'mode', 'tanggal'));
        }

        // ===============================
        // MINGGUAN
        // ===============================
        if ($mode == 'mingguan') {

            $start = Carbon::parse($tanggal)->startOfWeek()->toDateString();
            $end   = Carbon::parse($tanggal)->endOfWeek()->toDateString();

            $data = Presensi::with('karyawan')
                ->whereBetween('tanggal', [$start, $end])
                ->get();

            return view('laporan.presensi.index', compact('data', 'mode', 'tanggal'));
        }

        // ===============================
        // BULANAN
        // ===============================
        if ($mode == 'bulanan') {

            $karyawans = Karyawan::all();
            $rekap = [];

            foreach ($karyawans as $k) {

                $presensi = Presensi::where('karyawan_id', $k->id)
                    ->where('tanggal', 'LIKE', Carbon::parse($bulan)->format('Y-m').'%')
                    ->get();

                $hadir = $presensi->count();
                $telat = $presensi->where('status', 'Terlambat')->count();

                $hariKerja = 26;
                $absen = max(0, $hariKerja - $hadir);

                // 🔥 INSENTIF FIX
                $insentif = $hadir * 15000;

                $persen = $hariKerja > 0
                    ? round(($hadir / $hariKerja) * 100, 1)
                    : 0;

                $rekap[] = [
                    'nama' => $k->nama,
                    'hadir' => $hadir,
                    'telat' => $telat,
                    'absen' => $absen,
                    'persen' => $persen,
                    'insentif' => $insentif
                ];
            }

            return view('laporan.presensi.index', compact('rekap', 'mode', 'bulan'));
        }
    }

    public function keterlambatan(Request $request)
{
    $bulan = $request->bulan ?? now()->format('Y-m');

    $karyawans = Karyawan::all();
    $data = [];

    foreach ($karyawans as $k) {

        $presensi = Presensi::where('karyawan_id', $k->id)
            ->where('tanggal', 'LIKE', $bulan.'%')
            ->get();

        $hadir = $presensi->count();
        $telat = $presensi->where('status', 'Terlambat')->count();

        $persenTelat = $hadir > 0 
            ? round(($telat / $hadir) * 100, 1) 
            : 0;

        $data[] = [
            'nama' => $k->nama,
            'telat' => $telat,
            'hadir' => $hadir,
            'persen_telat' => $persenTelat
        ];
    }

    return view('laporan.keterlambatan.index', compact('data', 'bulan'));
    }

    public function kedisiplinan(Request $request)
{
    $bulan = $request->bulan ?? now()->format('Y-m');

    $karyawans = Karyawan::all();
    $data = [];

    foreach ($karyawans as $k) {

        $presensi = Presensi::where('karyawan_id', $k->id)
            ->where('tanggal', 'LIKE', $bulan.'%')
            ->get();

        $hadir = $presensi->count();
        $tepat = $presensi->where('status', 'Tepat Waktu')->count();
        $telat = $presensi->where('status', 'Terlambat')->count();

        $persenDisiplin = $hadir > 0 
            ? round(($tepat / $hadir) * 100, 1) 
            : 0;

        $data[] = [
            'nama' => $k->nama,
            'hadir' => $hadir,
            'tepat' => $tepat,
            'telat' => $telat,
            'persen_disiplin' => $persenDisiplin
        ];
    }

    return view('laporan.kedisiplinan.index', compact('data', 'bulan'));
    }
}