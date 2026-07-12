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

            $karyawans = Karyawan::orderBy('nama')->get();

            $rekapStaff = [];
            $rekapNonStaff = [];

            foreach ($karyawans as $k) {

                $presensi = Presensi::where('karyawan_id', $k->id)
                    ->whereYear('tanggal', Carbon::parse($bulan)->year)
                    ->whereMonth('tanggal', Carbon::parse($bulan)->month)
                    ->get();

                $hadir = $presensi->count();

                $telat = $presensi->where('status', 'Terlambat')->count();

                $hariKerja = 28;

                $nilaiDisiplin = max(0, $hadir - $telat);

                // ===============================
                // Ketidakhadiran
                // ===============================
                if ($hadir == 0) {

                    // hanya tampilan
                    $ketidakhadiran = 0;

                } else {

                    $ketidakhadiran = $hariKerja - $hadir;

                }

                // ===============================
                // Persentase
                // ===============================
                if ($hadir == 0) {

                    $persen = '-';

                } else {

                    $persen = round(($nilaiDisiplin / $hariKerja) * 100, 1);

                }

                // ===============================
                // Keterangan
                // ===============================
                if ($hadir >= 20 && $telat <= 3) {

                    $keterangan = 'Disiplin';

                } else {

                    $keterangan = 'Kurang Disiplin';

                }

                // ===============================
                // Insentif
                // ===============================
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
    $rekapStaff = [];
    $rekapNonStaff = [];

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

    $karyawans = Karyawan::orderBy('nama')->get();

    foreach ($karyawans as $k) {

        $presensi = Presensi::where('karyawan_id', $k->id)
            ->whereYear('tanggal', Carbon::parse($bulan)->year)
            ->whereMonth('tanggal', Carbon::parse($bulan)->month)
            ->get();

        $hadir = $presensi->count();

        $telat = $presensi->where('status', 'Terlambat')->count();

        $hariKerja = 28;

        $nilaiDisiplin = max(0, $hadir - $telat);

        // ===============================
        // Ketidakhadiran
        // ===============================
        if ($hadir == 0) {

            // hanya tampilan
            $ketidakhadiran = 0;

        } else {

            $ketidakhadiran = $hariKerja - $hadir;

        }

        // ===============================
        // Persentase
        // ===============================
        if ($hadir == 0) {

            $persen = '-';

        } else {

            $persen = round(($nilaiDisiplin / $hariKerja) * 100, 1);

        }

        // ===============================
        // Keterangan
        // ===============================
        if ($hadir >= 20 && $telat <= 3) {

            $keterangan = 'Disiplin';

        } else {

            $keterangan = 'Kurang Disiplin';

        }

        // ===============================
        // Insentif
        // ===============================
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
    'rekapStaff' => $rekapStaff,
    'rekapNonStaff' => $rekapNonStaff,

    'mode' => $mode,
    'tanggal' => $tanggal,
    'bulan' => $bulan

]);

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