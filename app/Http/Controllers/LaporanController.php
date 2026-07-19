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
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

        // HARIAN & MINGGUAN
        if ($mode == 'harian' || $mode == 'mingguan') {
            if ($mode == 'harian') {
                $queryDate = ['tanggal', 'LIKE', $tanggal . '%'];
                $data = Presensi::with('karyawan')->where([$queryDate])->orderBy('tanggal', 'DESC')->get();
            } else {
                $start = Carbon::parse($tanggal)->startOfWeek()->toDateString();
                $end = Carbon::parse($tanggal)->endOfWeek()->toDateString();
                $data = Presensi::with('karyawan')->whereBetween('tanggal', [$start, $end])->orderBy('tanggal', 'DESC')->get();
            }

            return view('laporan.presensi.index', compact('data', 'mode', 'tanggal'));
        }

        // BULANAN & RENTANG WAKTU (CUSTOM)
        if ($mode == 'bulanan' || $mode == 'custom') {
            if ($mode == 'bulanan') {
                $selectedDate = Carbon::parse($bulan);
                $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
                $endDate   = $selectedDate->copy()->day(25)->format('Y-m-d');
                $hariKerja = 28; // Tetap 28 hari untuk bulanan default
            } else {
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
    }

    public function exportPdf(Request $request)
    {
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

        $data = collect();
        $rekapStaff = [];
        $rekapNonStaff = [];

        if ($mode == 'harian' || $mode == 'mingguan') {
            if ($mode == 'harian') {
                $data = Presensi::with('karyawan')->where('tanggal', 'LIKE', $tanggal . '%')->orderBy('tanggal', 'DESC')->get();
            } else {
                $start = Carbon::parse($tanggal)->startOfWeek()->toDateString();
                $end = Carbon::parse($tanggal)->endOfWeek()->toDateString();
                $data = Presensi::with('karyawan')->whereBetween('tanggal', [$start, $end])->orderBy('tanggal', 'DESC')->get();
            }
        } elseif ($mode == 'bulanan' || $mode == 'custom') {
            
            if ($mode == 'bulanan') {
                $selectedDate = Carbon::parse($bulan);
                $startDate = $selectedDate->copy()->subMonth()->day(26)->format('Y-m-d');
                $endDate   = $selectedDate->copy()->day(25)->format('Y-m-d');
                $hariKerja = 28;
            } else {
                $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
                $endDate   = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
                $hariKerja = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
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
            'startDate' => $startDate ?? null,
            'endDate' => $endDate ?? null
        ]);

        $filename = match ($mode) {
            'harian' => 'laporan-presensi-harian.pdf',
            'mingguan' => 'laporan-presensi-mingguan.pdf',
            'bulanan' => 'laporan-presensi-bulanan.pdf',
            'custom' => 'laporan-presensi-rentang-waktu.pdf',
            default => 'laporan-presensi.pdf'
        };

        return $pdf->download($filename);
    }
}