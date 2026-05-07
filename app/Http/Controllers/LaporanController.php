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

    public function presensi(Request $request)
    {
        $mode = $request->mode ?? 'harian';
        $tanggal = $request->tanggal ?? now()->toDateString();
        $bulan = $request->bulan ?? now()->format('Y-m');

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

            return view('laporan.presensi.index', compact(
                'data',
                'mode',
                'tanggal'
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | MINGGUAN
        |--------------------------------------------------------------------------
        */
        if ($mode == 'mingguan') {

            $start = Carbon::parse($tanggal)
                ->startOfWeek()
                ->toDateString();

            $end = Carbon::parse($tanggal)
                ->endOfWeek()
                ->toDateString();

            $data = Presensi::with('karyawan')
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal', 'DESC')
                ->get();

            return view('laporan.presensi.index', compact(
                'data',
                'mode',
                'tanggal'
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | BULANAN
        |--------------------------------------------------------------------------
        */
        if ($mode == 'bulanan') {

            $karyawans = Karyawan::all();

            $rekap = [];

            foreach ($karyawans as $k) {

                $presensi = Presensi::where('karyawan_id', $k->id)
                    ->where('tanggal', 'LIKE', Carbon::parse($bulan)->format('Y-m') . '%')
                    ->get();

                $hadir = $presensi->count();

                $telat = $presensi
                    ->where('status', 'Terlambat')
                    ->count();

                $tepat = $presensi
                    ->where('status', 'Tepat Waktu')
                    ->count();

                $hariKerja = 26;

                $absen = max(0, $hariKerja - $hadir);

                /*
                |--------------------------------------------------------------------------
                | INSENTIF
                |--------------------------------------------------------------------------
                */
                $insentif = $hadir * 15000;

                /*
                |--------------------------------------------------------------------------
                | PERSENTASE KEHADIRAN
                |--------------------------------------------------------------------------
                */
                $persen = $hariKerja > 0
                    ? round(($hadir / $hariKerja) * 100, 1)
                    : 0;

                $rekap[] = [
                    'nama' => $k->nama,
                    'hadir' => $hadir,
                    'tepat' => $tepat,
                    'telat' => $telat,
                    'absen' => $absen,
                    'persen' => $persen,
                    'insentif' => $insentif
                ];
            }

            return view('laporan.presensi.index', compact(
                'rekap',
                'mode',
                'bulan'
            ));
        }
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

    /*
    |--------------------------------------------------------------------------
    | DEFAULT VARIABLE
    |--------------------------------------------------------------------------
    */
    $data = collect();
    $rekap = [];

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

        $start = Carbon::parse($tanggal)
            ->startOfWeek()
            ->toDateString();

        $end = Carbon::parse($tanggal)
            ->endOfWeek()
            ->toDateString();

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

        $karyawans = Karyawan::all();

        foreach ($karyawans as $k) {

            $presensi = Presensi::where('karyawan_id', $k->id)
                ->where('tanggal', 'LIKE', Carbon::parse($bulan)->format('Y-m') . '%')
                ->get();

            $hadir = $presensi->count();

            $telat = $presensi
                ->where('status', 'Terlambat')
                ->count();

            $tepat = $presensi
                ->where('status', 'Tepat Waktu')
                ->count();

            $hariKerja = 26;

            $absen = max(0, $hariKerja - $hadir);

            /*
            |--------------------------------------------------------------------------
            | INSENTIF
            |--------------------------------------------------------------------------
            */
            $insentif = $hadir * 15000;

            /*
            |--------------------------------------------------------------------------
            | PERSENTASE
            |--------------------------------------------------------------------------
            */
            $persen = $hariKerja > 0
                ? round(($hadir / $hariKerja) * 100, 1)
                : 0;

            $rekap[] = [
                'nama' => $k->nama,
                'hadir' => $hadir,
                'tepat' => $tepat,
                'telat' => $telat,
                'absen' => $absen,
                'persen' => $persen,
                'insentif' => $insentif
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MODE TIDAK VALID
    |--------------------------------------------------------------------------
    */
    else {

        return back()->with('error', 'Mode laporan tidak valid.');
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT PDF
    |--------------------------------------------------------------------------
    */
    $pdf = Pdf::loadView('laporan.presensi.pdf', [

        'data' => $data,
        'rekap' => $rekap,

        'mode' => $mode,
        'tanggal' => $tanggal,
        'bulan' => $bulan

    ])->setPaper('a4', 'landscape');

    /*
    |--------------------------------------------------------------------------
    | NAMA FILE PDF
    |--------------------------------------------------------------------------
    */
    $filename = match ($mode) {

        'harian' => 'laporan-presensi-harian.pdf',

        'mingguan' => 'laporan-presensi-mingguan.pdf',

        'bulanan' => 'laporan-presensi-bulanan.pdf',

        default => 'laporan-presensi.pdf'
    };

    return $pdf->download($filename);
}
    /*
    |--------------------------------------------------------------------------
    | LAPORAN KETERLAMBATAN
    |--------------------------------------------------------------------------
    */

    public function keterlambatan(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');

        $karyawans = Karyawan::all();

        $data = [];

        foreach ($karyawans as $k) {

            $presensi = Presensi::where('karyawan_id', $k->id)
                ->where('tanggal', 'LIKE', $bulan . '%')
                ->get();

            $hadir = $presensi->count();

            $telat = $presensi
                ->where('status', 'Terlambat')
                ->count();

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

        return view('laporan.keterlambatan.index', compact(
            'data',
            'bulan'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT PDF KETERLAMBATAN
    |--------------------------------------------------------------------------
    */

    public function exportKeterlambatanPdf(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');

        $karyawans = Karyawan::all();

        $data = [];

        foreach ($karyawans as $k) {

            $presensi = Presensi::where('karyawan_id', $k->id)
                ->where('tanggal', 'LIKE', $bulan . '%')
                ->get();

            $hadir = $presensi->count();

            $telat = $presensi
                ->where('status', 'Terlambat')
                ->count();

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

        $pdf = Pdf::loadView(
            'laporan.keterlambatan.pdf',
            compact('data', 'bulan')
        )->setPaper('a4', 'portrait');

        return $pdf->download('laporan-keterlambatan.pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | LAPORAN KEDISIPLINAN
    |--------------------------------------------------------------------------
    */

    public function kedisiplinan(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');

        $karyawans = Karyawan::all();

        $data = [];

        foreach ($karyawans as $k) {

            $presensi = Presensi::where('karyawan_id', $k->id)
                ->where('tanggal', 'LIKE', $bulan . '%')
                ->get();

            $hadir = $presensi->count();

            $tepat = $presensi
                ->where('status', 'Tepat Waktu')
                ->count();

            $telat = $presensi
                ->where('status', 'Terlambat')
                ->count();

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

        return view('laporan.kedisiplinan.index', compact(
            'data',
            'bulan'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT PDF KEDISIPLINAN
    |--------------------------------------------------------------------------
    */

    public function exportKedisiplinanPdf(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');

        $karyawans = Karyawan::all();

        $data = [];

        foreach ($karyawans as $k) {

            $presensi = Presensi::where('karyawan_id', $k->id)
                ->where('tanggal', 'LIKE', $bulan . '%')
                ->get();

            $hadir = $presensi->count();

            $tepat = $presensi
                ->where('status', 'Tepat Waktu')
                ->count();

            $telat = $presensi
                ->where('status', 'Terlambat')
                ->count();

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

        $pdf = Pdf::loadView(
            'laporan.kedisiplinan.pdf',
            compact('data', 'bulan')
        )->setPaper('a4', 'portrait');

        return $pdf->download('laporan-kedisiplinan.pdf');
    }
}