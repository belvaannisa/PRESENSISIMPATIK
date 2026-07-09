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

                $telat = $presensi->where('status','Terlambat')->count();

                $hariKerja = 28;

                $ketidakhadiran = max(0,$hariKerja-$hadir);

                $nilaiDisiplin = ($hadir - $telat);

                $persen = round(($nilaiDisiplin/$hariKerja)*100,1);

                if ($hadir == 0) {
                    $keterangan = null;   // atau '-'
                } elseif ($persen >= 75) {
                    $keterangan = 'Disiplin';
                } else {
                    $keterangan = 'Kurang Disiplin';
                }

                $insentif = ($hadir - $telat) * 15000;

                if($insentif < 0){
                    $insentif = 0;
                }

                $rekap[] = [

                    'nama'=>$k->nama,

                    'hadir'=>$hadir,

                    'telat'=>$telat,

                    'ketidakhadiran'=>$ketidakhadiran,

                    'keterangan'=>$keterangan,

                    'persen'=>$persen,

                    'insentif'=>$insentif

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

        $hariKerja = 28;

        $ketidakhadiran = max(0, $hariKerja - $hadir);

        $nilaiDisiplin = $hadir - $telat;

        $persen = round(($nilaiDisiplin / $hariKerja) * 100, 1);

        $keterangan = $persen >= 75
            ? 'Disiplin'
            : 'Kurang Disiplin';

        $insentif = $nilaiDisiplin * 15000;

        if ($insentif < 0) {
            $insentif = 0;
        }

        $rekap[] = [

            'nama' => $k->nama,

            'hadir' => $hadir,

            'telat' => $telat,

            'ketidakhadiran' => $ketidakhadiran,

            'keterangan' => $keterangan,

            'persen' => $persen,

            'insentif' => $insentif,

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
}