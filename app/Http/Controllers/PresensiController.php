<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\PresensiLog;
use Illuminate\Support\Facades\Http;
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
            
            if(!file_exists($file)){
                continue;
            }
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

               if(file_exists($file))
                {
                    rename(
                        $file,
                        $processedPath.basename($file)
                    );
                }

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
    Log::info('===== MANUAL UPLOAD =====');
    $request->validate([
        'file' => 'required|mimes:csv,txt,xls,xlsx'
    ]);

    try {

        $file = $request->file('file');

        $rows = $this->bacaFile(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );
        Log::info(
                'Jumlah baris upload : '.count($rows)
            );

       $this->prosesRows($rows);

$this->sinkronisasiPresensi();

$this->kirimDataPendingKeVps();
        Log::info(
            'Manual Upload : '.$file->getClientOriginalName()
        );
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
    Log::info(
    'Manual Upload : '.$file->getClientOriginalName()
);
}


  private function prosesLogKePresensi($karyawan, $log)
{
    $presensi = Presensi::firstOrCreate(
        [
            'karyawan_id' => $karyawan->id,
            'tanggal'     => $log->tanggal,
        ],
        [
            'keterangan'  => 'Hadir',
            'status'      => 'Hadir'
        ]
    );

    if (!$presensi->jam_masuk || $log->jam < $presensi->jam_masuk) {
        $presensi->jam_masuk = $log->jam;
    }

    if (!$presensi->jam_keluar || $log->jam > $presensi->jam_keluar) {
        $presensi->jam_keluar = $log->jam;
    }

    $presensi->status =
        strtotime($presensi->jam_masuk) > strtotime('08:15:00')
        ? 'Terlambat'
        : 'Tepat Waktu';

    $presensi->keterangan = 'Hadir';

    $presensi->save();
}


private function bacaFile($filePath, $extension = null)
{
    if (!$extension) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    if (in_array($extension, ['xls', 'xlsx'])) {

        $spreadsheet = IOFactory::load($filePath);

        return $spreadsheet
            ->getActiveSheet()
            ->toArray(
                null,
                true,
                true,
                false
            );
    }

        return array_map(function ($line) {

            return str_getcsv(
                trim($line),
                ';',
                '"'
            );

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
    foreach ($rows as $index => $row) {

        // Lewati header
        if ($index == 0) {
            continue;
        }

        // Pastikan jumlah kolom sesuai
        if (!is_array($row) || count($row) < 8) {
            Log::warning("Baris {$index} dilewati karena jumlah kolom kurang.");
            continue;
        }

        // Ambil data
        $nama       = trim((string)($row[1] ?? ''));
        $pin        = trim((string)($row[2] ?? ''));
        $datetime   = trim((string)($row[3] ?? ''));
        $verifyCode = trim((string)($row[6] ?? ''));

        // Validasi data wajib
        if ($nama == '' || $pin == '' || $datetime == '') {

            Log::warning(
                "Baris {$index} kosong. PIN={$pin} DATETIME={$datetime}"
            );

            continue;
        }

        // Parsing tanggal
        try {

            $dt = new \DateTime($datetime);

        } catch (\Exception $e) {

            Log::warning(
                "Format tanggal salah pada baris {$index} : {$datetime}"
            );

            continue;
        }

        $tanggal = $dt->format('Y-m-d');
        $jam     = $dt->format('H:i:s');

        // Cek data duplikat
        $sudahAda = PresensiLog::where('pin', $pin)
            ->where('tanggal', $tanggal)
            ->where('jam', $jam)
            ->exists();

        if ($sudahAda) {

            Log::info(
                "Duplikat dilewati : {$pin} {$tanggal} {$jam}"
            );

            continue;
        }

        // Simpan log
       $log = PresensiLog::create([
    'pin' => $pin,
    'nama' => $nama,
    'tanggal' => $tanggal,
    'jam' => $jam,
    'verify_code' => $verifyCode,
    'status_sinkron' => 'pending'
]);

// $this->kirimKeVps($log);

        Log::info(
            "PresensiLog berhasil dibuat. ID={$log->id} PIN={$pin}"
        );

      
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
           if(!$karyawan){

                           $log->update([
                                'status_sinkron'=>'unmatched',
                                'catatan'=>'PIN tidak ditemukan'
                            ]);

                            Log::warning(

                                'PIN '.$log->pin.' tidak ditemukan'

                            );

                            continue;
                        }

            $this->prosesLogKePresensi(
                $karyawan,
                $log
            );

           $log->update([
            'status_sinkron'=>'success',
            'karyawan_id'=>$karyawan->id
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

        $response = Http::retry(3, 500)
            ->timeout(10)
            ->withHeaders([
                'X-API-KEY' => env('FINGERPRINT_API_KEY')
            ])
            ->post(
                env('SERVER_API') . '/api/presensi/upload',
                [
                    'pin'         => $log->pin,
                    'nama'        => $log->nama,
                    'tanggal'     => $log->tanggal,
                    'jam'         => $log->jam,
                    'verify_code' => $log->verify_code
                ]
            );

        if ($response->successful()) {

            $log->update([
                'status_server' => 'success'
            ]);

            Log::info(
                'Upload VPS berhasil | PIN : ' .
                $log->pin
            );

        } else {

            $log->update([
                'status_server' => 'failed'
            ]);

            Log::error(
                'Upload VPS gagal | HTTP ' .
                $response->status() .
                ' | ' .
                $response->body()
            );
        }

    } catch (\Exception $e) {

        $log->update([
            'status_server' => 'failed'
        ]);

        Log::error(
            'Exception Upload VPS : ' .
            $e->getMessage()
        );
    }
}
private function kirimDataPendingKeVps()
{
    $logs = PresensiLog::where('status_server', '!=', 'success')
        ->orWhereNull('status_server')
        ->get();

    foreach ($logs as $log) {

        $this->kirimKeVps($log);

    }
}
}