<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    private $staffJabatan = [
        'KEPALA CABANG',
        'HAF',
        'KEPALA GUDANG & KEPALA PERSONALIA',
        'KASIR',
        'KOORD AR',
        'ADM AR',
        'KORWIL BJB',
        'COLLECTOR',
        'KANVAS DRIVER',
        'DRIVER GUDANG',
        'HELPER',
        'OFFICE BOY CABANG',
    ];

    public function presensi(Request $request)
    {
        // Default langsung ke mode bulanan
        $mode = $request->mode ?? 'bulanan';
        $bulan = $request->bulan ?? now()->format('Y-m');

        if ($mode == 'bulanan') {
            $selectedDate = Carbon::parse($bulan);
            $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
            $endDate   = $selectedDate->copy()->day(25)->format('Y-m-d');
            $hariKerja = 28; // Tetap 28 hari untuk bulanan default
        } else { // mode == 'custom'
            $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
            $endDate   = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
            // Hitung total hari di rentang waktu tersebut
            $hariKerja = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        }

        $karyawans = Karyawan::orderBy('nama')->get();
        $rekapStaff = [];
        $rekapNonStaff = [];

        foreach ($karyawans as $k) {
            $presensi = Presensi::where('karyawan_id', $k->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $hadir = $presensi->count();
            $telat = $presensi->whereIn('status', ['Terlambat', 'Tidak Absen Pagi'])->count();
            $nilaiDisiplin = max(0, $hadir - $telat);

            $ketidakhadiran = max(0, $hariKerja - $hadir);
            $persen = $hariKerja > 0 ? round(($nilaiDisiplin / $hariKerja) * 100, 1) : 0;

            // Keterangan dinamis berdasarkan hari kerja
            if ($hadir >= ($hariKerja * 0.75) && $telat <= 3) {
                $keterangan = 'Disiplin';
            } else {
                $keterangan = 'Kurang Disiplin';
            }

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

            if (in_array($k->jabatan, $this->staffJabatan)) {
                $rekapStaff[] = $item;
            } else {
                $rekapNonStaff[] = $item;
            }
        }
            
        return view('laporan.presensi.index', compact(
            'rekapStaff',
            'rekapNonStaff',
            'mode',
            'bulan',
            'startDate',
            'endDate'
        ));
    }

    public function exportPdf(Request $request)
    {
        $mode = $request->mode ?? 'bulanan';
        $bulan = $request->bulan ?? now()->format('Y-m');

        $rekapStaff = [];
        $rekapNonStaff = [];
        $data = collect(); // Biarkan kosong agar tidak error jika pdf.blade.php memanggilnya

        if ($mode == 'bulanan') {
            $selectedDate = Carbon::parse($bulan);
            $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
            $endDate   = $selectedDate->copy()->day(25)->format('Y-m-d');
            $hariKerja = 28;
        } elseif ($mode == 'custom') {
            $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
            $endDate   = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
            $hariKerja = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        } else {
            return back()->with('error', 'Mode laporan tidak valid.');
        }

        $karyawans = Karyawan::orderBy('nama')->get();

        foreach ($karyawans as $k) {
            $presensi = Presensi::where('karyawan_id', $k->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $hadir = $presensi->count();
            $telat = $presensi->whereIn('status', ['Terlambat', 'Tidak Absen Pagi'])->count();
            $nilaiDisiplin = max(0, $hadir - $telat);

            $ketidakhadiran = max(0, $hariKerja - $hadir);
            $persen = $hariKerja > 0 ? round(($nilaiDisiplin / $hariKerja) * 100, 1) : 0;
            $keterangan = ($hadir >= ($hariKerja * 0.75) && $telat <= 3) ? 'Disiplin' : 'Kurang Disiplin';
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

            if (in_array($k->jabatan, $this->staffJabatan)) {
                $rekapStaff[] = $item;
            } else {
                $rekapNonStaff[] = $item;
            }
        }

        $pdf = Pdf::loadView('laporan.presensi.pdf', [
            'data' => $data,
            'rekapStaff' => $rekapStaff,
            'rekapNonStaff' => $rekapNonStaff,
            'mode' => $mode,
            'bulan' => $bulan,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $filename = match ($mode) {
            'custom' => 'laporan-presensi-rentang-waktu.pdf',
            default => 'laporan-presensi-bulanan.pdf'
        };

        return $pdf->download($filename);
    }
}