<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LAPORAN PRESENSI
    |--------------------------------------------------------------------------
    */
    private $staffJabatan = [
        'KEPALA CABANG', 'HAF', 'KEPALA GUDANG & KEPALA PERSONALIA', 'KASIR', 
        'KOORD AR', 'ADM AR', 'KORWIL BJB', 'COLLECTOR', 'KANVAS DRIVER', 
        'DRIVER GUDANG', 'HELPER', 'OFFICE BOY CABANG',
    ];

    public function presensi(Request $request)
    {
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

        $data = collect();
        $rekapStaff = [];
        $rekapNonStaff = [];
        $startDate = null;
        $endDate = null;

        /*
        |--------------------------------------------------------------------------
        | HARIAN
        |--------------------------------------------------------------------------
        */
        if ($mode == 'harian') {
            $data = Presensi::with('karyawan')
                ->where('tanggal', 'LIKE', $tanggal . '%')
                ->orderBy('tanggal', 'DESC')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | MINGGUAN
        |--------------------------------------------------------------------------
        */
        elseif ($mode == 'mingguan') {
            $start = Carbon::parse($tanggal)->startOfWeek()->toDateString();
            $end = Carbon::parse($tanggal)->endOfWeek()->toDateString();

            $data = Presensi::with('karyawan')
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal', 'DESC')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | BULANAN
        |--------------------------------------------------------------------------
        */
        elseif ($mode == 'bulanan') {
            $selectedDate = Carbon::parse($bulan);
            $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
            $endDate = $selectedDate->copy()->day(25)->format('Y-m-d');

            // Optimasi N+1: Memuat relasi presensi sekaligus berdasarkan range tanggal
            $karyawans = Karyawan::with(['presensi' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }])->orderBy('nama')->get();

            foreach ($karyawans as $k) {
                $presensi = $k->presensi; 
                $hadir = $presensi->count();
                $telat = $presensi->where('status', 'Terlambat')->count();
                $hariKerja = 28;
                $nilaiDisiplin = max(0, $hadir - $telat);

                // Perhitungan Ketidakhadiran & Persentase
                $ketidakhadiran = ($hadir == 0) ? 0 : max(0, $hariKerja - $hadir);
                $persen = ($hadir == 0) ? '-' : round(($nilaiDisiplin / $hariKerja) * 100, 1);

                // Keterangan & Insentif
                $keterangan = ($hadir >= 20 && $telat <= 3) ? 'Disiplin' : 'Kurang Disiplin';
                $insentif = $nilaiDisiplin * 15000;

                $item = [
                    'nama' => $k->nama,
                    'hadir' => $hadir,
                    'telat' => $telat,
                    'ketidakhadiran' => $ketidakhadiran,
                    'keterangan' => $keterangan,
                    'persen' => $persen,
                    'insentif' => $insentif
                ];

                if (in_array(strtoupper($k->jabatan), $this->staffJabatan)) {
                    $rekapStaff[] = $item;
                } else {
                    $rekapNonStaff[] = $item;
                }
            }
        }

        return view('laporan.presensi.index', compact(
            'data', 'rekapStaff', 'rekapNonStaff', 'mode', 'tanggal', 'bulan', 'startDate', 'endDate'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT PDF PRESENSI
    |--------------------------------------------------------------------------
    */
    public function exportPdf(Request $request)
    {
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

        $data = collect();
        $rekapStaff = [];
        $rekapNonStaff = [];
        $startDate = null;
        $endDate = null;

        if ($mode == 'harian') {
            $data = Presensi::with('karyawan')
                ->where('tanggal', 'LIKE', $tanggal . '%')
                ->orderBy('tanggal', 'DESC')
                ->get();
        }

        elseif ($mode == 'mingguan') {
            $start = Carbon::parse($tanggal)->startOfWeek()->toDateString();
            $end = Carbon::parse($tanggal)->endOfWeek()->toDateString();

            $data = Presensi::with('karyawan')
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal', 'DESC')
                ->get();
        }

        elseif ($mode == 'bulanan') {
            $selectedDate = Carbon::parse($bulan);
            $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
            $endDate = $selectedDate->copy()->day(25)->format('Y-m-d');

            // Optimasi N+1 untuk PDF
            $karyawans = Karyawan::with(['presensi' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }])->orderBy('nama')->get();

            foreach ($karyawans as $k) {
                $presensi = $k->presensi;
                $hadir = $presensi->count();
                $telat = $presensi->where('status', 'Terlambat')->count();
                $hariKerja = 28;
                $nilaiDisiplin = max(0, $hadir - $telat);

                $ketidakhadiran = ($hadir == 0) ? 0 : max(0, $hariKerja - $hadir);
                $persen = ($hadir == 0) ? '-' : round(($nilaiDisiplin / $hariKerja) * 100, 1);
                $keterangan = ($hadir >= 20 && $telat <= 3) ? 'Disiplin' : 'Kurang Disiplin';
                $insentif = $nilaiDisiplin * 15000;

                $item = [
                    'nama' => $k->nama,
                    'hadir' => $hadir,
                    'telat' => $telat,
                    'ketidakhadiran' => $ketidakhadiran,
                    'keterangan' => $keterangan,
                    'persen' => $persen,
                    'insentif' => $insentif
                ];

                if (in_array(strtoupper($k->jabatan), $this->staffJabatan)) {
                    $rekapStaff[] = $item;
                } else {
                    $rekapNonStaff[] = $item;
                }
            }
        } else {
            return back()->with('error', 'Mode laporan tidak valid.');
        }

        $pdf = Pdf::loadView('laporan.presensi.pdf', [
            'data' => $data,
            'rekapStaff' => $rekapStaff,
            'rekapNonStaff' => $rekapNonStaff,
            'mode' => $mode,
            'tanggal' => $tanggal,
            'bulan' => $bulan,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $filename = match ($mode) {
            'harian' => 'laporan-presensi-harian.pdf',
            'mingguan' => 'laporan-presensi-mingguan.pdf',
            'bulanan' => 'laporan-presensi-bulanan.pdf',
            default => 'laporan-presensi.pdf'
        };

        return $pdf->download($filename);
    }
}
