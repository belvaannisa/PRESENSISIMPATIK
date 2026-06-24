<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\PresensiLog;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PresensiController extends Controller
{
    // ================= INDEX =================
    public function index(Request $request)
    {
        $search = $request->search;

        $presensis = Presensi::with('karyawan')
            ->when($search, function ($query) use ($search) {
                $query->where('tanggal', 'like', "%$search%")
                      ->orWhere('status', 'like', "%$search%")
                      ->orWhereHas('karyawan', function ($q) use ($search) {
                          $q->where('nama', 'like', "%$search%");
                      });
            })
            ->orderBy('id', 'asc') 
            ->paginate(10);

        $no = ($presensis->currentPage() - 1) * $presensis->perPage() + 1;

        return view('presensi.index', compact('presensis', 'search', 'no'));
    }

    // ================= AUTO =================
    
    public function importLocal()
    {
        $path = 'C:/Users/LENOVO/datapresensi/incoming/';
        $processedPath = 'C:/Users/LENOVO/datapresensi/processed/';
        $failedPath = 'C:/Users/LENOVO/datapresensi/failed/';

        $files = glob(
            $path . '*.{csv,xls,xlsx}',
            GLOB_BRACE
        );

        if(empty($files))
        {
            return back()->with(
                'error',
                'Tidak ada file ditemukan'
            );
        }

        foreach($files as $file)
        {
            try {

                $rows = $this->bacaFile($file);

                $this->prosesRows($rows);

                if(!is_dir($processedPath))
                {
                    mkdir(
                        $processedPath,
                        0777,
                        true
                    );
                }

                rename(
                    $file,
                    $processedPath .
                    basename($file)
                );

            }
            catch(\Exception $e)
            {
                Log::error(
                    'Import gagal : ' .
                    $e->getMessage()
                );

                if(!is_dir($failedPath))
                {
                    mkdir(
                        $failedPath,
                        0777,
                        true
                    );
                }

                rename(
                    $file,
                    $failedPath .
                    basename($file)
                );
            }
        }

        try {

    $this->sinkronisasiPresensi();

} catch (\Exception $e) {

    Log::error(
        'Sinkronisasi gagal : ' .
        $e->getMessage()
    );
}

        return back()->with(
            'success',
            'Auto Import Berhasil'
        );
    }


        // ================= MANUAL =================
        public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt,xls,xlsx'
    ]);

    try {

        $file = $request->file('file');

        $rows = $this->bacaFile(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        $this->prosesRows($rows);

        $this->sinkronisasiPresensi();

        return back()->with(
            'success',
            'Import Berhasil'
        );

    } catch (\Exception $e) {

        Log::error(
            'Upload gagal : ' .
            $e->getMessage()
        );

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}


    private function prosesLogKePresensi(
    $karyawan,
    $log
)
{
    $presensi = Presensi::firstOrCreate([
        'karyawan_id' => $karyawan->id,
        'tanggal' => $log->tanggal
    ]);

    if (
        !$presensi->jam_masuk ||
        $log->jam < $presensi->jam_masuk
    ) {
        $presensi->jam_masuk = $log->jam;
    }

    if (
        !$presensi->jam_keluar ||
        $log->jam > $presensi->jam_keluar
    ) {
        $presensi->jam_keluar = $log->jam;
    }

    $presensi->status =
        strtotime($presensi->jam_masuk)
        > strtotime('08:15:00')
        ? 'Terlambat'
        : 'Hadir';

    $presensi->save();
}


private function bacaFile($filePath, $extension = null)
{
    if (!$extension) {

        $extension = strtolower(
            pathinfo(
                $filePath,
                PATHINFO_EXTENSION
            )
        );
    }

    if (in_array($extension, ['xls', 'xlsx'])) {

        $spreadsheet = IOFactory::load($filePath);

        return $spreadsheet
            ->getActiveSheet()
            ->toArray();
    }

    return array_map(function ($line) {

        return str_getcsv($line, ';');

    }, file($filePath));
}
        // ✏️ FORM EDIT
    public function edit(Presensi $presensi)
    {
        return view('presensi.edit', compact('presensi'));
    }

    // 🔄 UPDATE
    public function update(Request $request, Presensi $presensi)
    {
        $request->validate([
            'jam_masuk' => 'required',
            'keterangan' => 'nullable|string'
        ]);

        $jamMasuk = $request->jam_masuk;

        // Jam kerja normal = 08:00
        if ($jamMasuk > '08:15:00') {
            $status = 'Terlambat';
        } else {
            $status = 'Hadir';
        }

        $presensi->update([
            'jam_masuk' => $jamMasuk,
            'status' => $status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->route('presensi.index')
            ->with('success', 'Data Presensi Berhasil Diedit!');
    }

        private function prosesRows($rows)
    {
        foreach($rows as $index => $row)
        {
            if($index == 0)
            {
                continue;
            }

            if(count($row) < 8)
            {
                continue;
            }

            $nama = trim($row[1]);
            $pin = trim($row[2]);
            $datetime = trim($row[3]);
            $verifyCode = trim($row[6]);

            if(
                empty($pin) ||
                empty($datetime)
            ){
                continue;
            }

           try {

             $dt = Carbon::parse($datetime);

            } catch (\Exception $e) {

                continue;
            }

            $tanggal = $dt->format('Y-m-d');
            $jam = $dt->format('H:i:s');

            $sudahAda = PresensiLog::where(
                'pin',
                $pin
            )
            ->where(
                'tanggal',
                $tanggal
            )
            ->where(
                'jam',
                $jam
            )
            ->exists();

            if($sudahAda)
            {
                continue;
            }

            $log = PresensiLog::create([
                'pin' => $pin,
                'nama' => $nama,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'verify_code' => $verifyCode,
                'status_sinkron' => 'pending'
            ]);

            $this->kirimKeVps($log);
        }
    }

   private function sinkronisasiPresensi()
{
    $logs = PresensiLog::where(
        'status_sinkron',
        'pending'
    )->get();

    foreach ($logs as $log) {

        try {

            $karyawan = Karyawan::where(
                'pin',
                $log->pin
            )->first();

            if (!$karyawan) {

                Log::warning(
                    'PIN tidak ditemukan : ' .
                    $log->pin
                );

                continue;
            }

            $this->prosesLogKePresensi(
                $karyawan,
                $log
            );

            $log->update([
                'status_sinkron' => 'success'
            ]);

        } catch (\Exception $e) {

            Log::error(
                'Gagal sinkron PIN ' .
                $log->pin .
                ' : ' .
                $e->getMessage()
            );
        }
    }
}

    // ❌ DELETE
    public function destroy(Presensi $presensi)
    {
        $presensi->delete();

        return redirect()->route('presensi.index')
                         ->with('success', 'Data Presensi Berhasil Dihapus!');
    }

    private function kirimKeVps($log)
{
    try {

        Http::withHeaders([
            'X-API-KEY' => env('FINGERPRINT_API_KEY')
        ])->post(
            'https://domain-kamu.com/api/presensi/upload',
            [
                'pin'      => $log->pin,
                'nama'     => $log->nama,
                'tanggal'  => $log->tanggal,
                'jam'      => $log->jam
            ]
        );

    } catch (\Exception $e) {

        \Log::error(
            'Gagal kirim VPS : ' .
            $e->getMessage()
        );
    }
}
}