<?php
namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\PresensiLog;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use PhpOffice\PhpSpreadsheet\IOFactory;

use Throwable;

class PresensiController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $presensis = Presensi::with('karyawan')
            ->when($search, function ($query) use ($search) {

                $query->where('tanggal', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('karyawan', function ($q) use ($search) {

                            $q->where(
                                'nama',
                                'like',
                                "%{$search}%"
                            );

                    });

            })
            ->latest()
            ->paginate(10);

        $no =
            ($presensis->currentPage()-1)
            *
            $presensis->perPage()
            +1;

        return view(
            'presensi.index',
            compact(
                'presensis',
                'search',
                'no'
            )
        );
    }

   public function importLocal()
{
    $incomingPath = storage_path('app/fingerprint/incoming');

    $processedPath = storage_path('app/fingerprint/processed');

    $failedPath = storage_path('app/fingerprint/failed');

    if (!is_dir($incomingPath)) {

        return back()->with(
            'error',
            'Folder incoming tidak ditemukan.'
        );

    }

    $files = glob(
        $incomingPath.'/*.{csv,xls,xlsx}',
        GLOB_BRACE
    );

    if (empty($files)) {

        return back()->with(
            'error',
            'Tidak ada file presensi.'
        );

    }

    DB::beginTransaction();

    try {

        foreach ($files as $file) {

            $rows = $this->bacaFile($file);

            $this->prosesRows($rows);

            rename(
                $file,
                $processedPath.'/'.basename($file)
            );

        }

        $this->sinkronisasiPresensi();

        DB::commit();

        $this->kirimDataPendingKeVps();

        return back()->with(
            'success',
            'Auto Import berhasil.'
        );

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error($e->getMessage());

        return back()->with(
            'error',
            $e->getMessage()
        );

    }
}
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

        // nanti dipindah ke queue
        //$this->kirimDataPendingKeVps();

        return back()->with(
            'success',
            'Import berhasil.'
        );

    } catch (\Throwable $e) {

        Log::error($e);

        return back()->with(
            'error',
            $e->getMessage()
        );

    }
}

    private function validasiBaris(array $row, int $index)
    {
        if (count($row) < 8) {

            Log::warning(
                "Baris {$index} dilewati karena kolom tidak lengkap."
            );

            return false;
        }

        if (
            trim($row[1]) == '' ||
            trim($row[2]) == '' ||
            trim($row[3]) == ''
        ) {

            Log::warning(
                "Baris {$index} kosong."
            );

            return false;
        }

        return true;
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
                ->toArray(
                    null,
                    true,
                    true,
                    false
                );
        }

        return array_map(

            function ($line) {

                return str_getcsv(

                    trim($line),

                    ';',

                    '"'

                );

            },

            file($filePath)

        );
    }

  private function prosesRows($rows)
{
    if (count($rows) <= 1) {
        return;
    }

    $tanggalImport = [];

    foreach ($rows as $index => $row) {

        if ($index == 0) {
            continue;
        }

        if (!$this->validasiBaris($row, $index)) {
            continue;
        }

        try {

            $dt = new \DateTime(trim($row[3]));

            $tanggalImport[] = $dt->format('Y-m-d');

        } catch (\Throwable $e) {

            continue;

        }

    }

    $tanggalImport = array_unique($tanggalImport);

    /*
    |--------------------------------------------------------------------------
    | Ambil seluruh PIN karyawan sekali
    |--------------------------------------------------------------------------
    */

    $karyawanMap = Karyawan::select('id', 'pin')
        ->get()
        ->keyBy('pin');

    /*
    |--------------------------------------------------------------------------
    | Ambil duplicate HANYA sesuai tanggal import
    |--------------------------------------------------------------------------
    */

    $existingLogs = PresensiLog::whereIn(
            'tanggal',
            $tanggalImport
        )
        ->select(
            'pin',
            'tanggal',
            'jam'
        )
        ->get()
        ->mapWithKeys(function ($item) {

            return [

                $item->pin .
                '_' .
                $item->tanggal .
                '_' .
                $item->jam

                => true

            ];

        });

    $insert = [];

    foreach ($rows as $index => $row) {

        if ($index == 0) {
            continue;
        }

        if (!$this->validasiBaris($row, $index)) {
            continue;
        }

        try {

            $datetime = new \DateTime(trim($row[3]));

        } catch (\Throwable $e) {

            continue;

        }

        $pin = trim($row[2]);

        $nama = trim($row[1]);

        $tanggal = $datetime->format('Y-m-d');

        $jam = $datetime->format('H:i:s');

        $verify = trim($row[6]);

        $key = "{$pin}_{$tanggal}_{$jam}";

        if (isset($existingLogs[$key])) {
            continue;
        }

        $existingLogs[$key] = true;

        $karyawan = $karyawanMap->get($pin);

        $insert[] = [

            'pin' => $pin,

            'nama' => $nama,

            'tanggal' => $tanggal,

            'jam' => $jam,

            'verify_code' => $verify,

            'karyawan_id' => $karyawan?->id,

            'status_sinkron' => $karyawan
                ? 'matched'
                : 'unmatched',

            'status_server' => 'pending',

            'catatan' => $karyawan
                ? 'PIN ditemukan'
                : 'PIN tidak ditemukan',

            'created_at' => now(),

            'updated_at' => now()

        ];

        /*
        |--------------------------------------------------------------------------
        | Batch Insert
        |--------------------------------------------------------------------------
        */

        if (count($insert) >= 1000) {

            DB::table('presensi_logs')->insert($insert);

            $insert = [];

        }

    }

    if (!empty($insert)) {

        DB::table('presensi_logs')->insert($insert);

    }
}

    private function cariKaryawan($log)
    {
        return Karyawan::where(
            'pin',
            $log->pin
        )->first();
    }

    private function tentukanStatus($jamMasuk)
    {
        return strtotime($jamMasuk)
            >

            strtotime('08:15:00')

            ?

            'Terlambat'

            :

            'Tepat Waktu';
    }

  private function prosesLogKePresensi($karyawan, $log)
{
    $presensi = Presensi::where(
            'karyawan_id',
            $karyawan->id
        )
        ->where(
            'tanggal',
            $log->tanggal
        )
        ->first();

    if (!$presensi) {

        $presensi = new Presensi();

        $presensi->karyawan_id = $karyawan->id;

        $presensi->tanggal = $log->tanggal;

        $presensi->status = 'Hadir';

        $presensi->keterangan = 'Hadir';

    }

    $jamLog = strtotime($log->jam);

    if ($jamLog < strtotime('12:00:00')) {

        if (
            !$presensi->jam_masuk ||
            $jamLog < strtotime($presensi->jam_masuk)
        ) {

            $presensi->jam_masuk = $log->jam;

        }

    } else {

        if (
            !$presensi->jam_keluar ||
            $jamLog > strtotime($presensi->jam_keluar)
        ) {

            $presensi->jam_keluar = $log->jam;

        }

    }

    $presensi->status = $this->tentukanStatus(
        $presensi->jam_masuk
    );

    $presensi->save();
}
   private function sinkronisasiPresensi()
{
    $logs = PresensiLog::where('status_sinkron', 'matched')
        ->where('catatan', '!=', 'Presensi berhasil dibuat')
        ->select(
            'id',
            'pin',
            'tanggal',
            'jam',
            'karyawan_id'
        )
        ->orderBy('id')
        ->get();

    if ($logs->isEmpty()) {
        return;
    }

    $karyawanMap = Karyawan::whereIn(
            'id',
            $logs->pluck('karyawan_id')->unique()
        )
        ->get()
        ->keyBy('id');

    DB::beginTransaction();

    try {

        foreach ($logs as $log) {

            $karyawan = $karyawanMap->get($log->karyawan_id);

            if (!$karyawan) {
                continue;
            }

            $this->prosesLogKePresensi(
                $karyawan,
                $log
            );

        }

        PresensiLog::whereIn(
                'id',
                $logs->pluck('id')
            )
            ->update([
                'catatan' => 'Presensi berhasil dibuat'
            ]);

        DB::commit();

    } catch (\Throwable $e) {   

        DB::rollBack();

        Log::error($e->getMessage());

    }
}

    private function kirimKeVps($log)
    {
        try {

            $response = Http::retry(3,500)

                ->timeout(10)

                ->withHeaders([

                    'X-API-KEY'=>env('FINGERPRINT_API_KEY')

                ])

                ->post(

                    env('SERVER_API').'/api/presensi/upload',

                    [

                        'pin'=>$log->pin,

                        'nama'=>$log->nama,

                        'tanggal'=>$log->tanggal,

                        'jam'=>$log->jam,

                        'verify_code'=>$log->verify_code

                    ]

                );

            if($response->successful()){

                $log->update([

                    'status_server'=>'success'

                ]);

                Log::info(

                    'Upload VPS berhasil : '

                    .$log->pin

                );

            }

            else{

                $log->update([

                    'status_server'=>'failed'

                ]);

                Log::error(

                    'Upload VPS gagal : '

                    .$response->status()

                );

            }

        }

        catch(\Throwable $e){

            $log->update([

                'status_server'=>'failed'

            ]);

            Log::error(

                'Exception Upload VPS : '

                .$e->getMessage()

            );

        }
    }

    private function kirimDataPendingKeVps()
{
    PresensiLog::where('status_sinkron', 'matched')
        ->where(function ($q) {

            $q->whereNull('status_server')
                ->orWhere('status_server', 'pending')
                ->orWhere('status_server', 'failed');

        })
        ->select(
            'id',
            'pin',
            'nama',
            'tanggal',
            'jam',
            'verify_code',
            'status_server'
        )
        ->orderBy('id')
        ->limit(500)
        ->chunk(100, function ($logs) {

            foreach ($logs as $log) {

                $this->kirimKeVps($log);

            }

        });
}

        // ❌ DELETE
        public function destroy(Presensi $presensi)
        {
            $presensi->delete();

            return redirect()->route('presensi.index')
                            ->with('success', 'Data Presensi Berhasil Dihapus!');
        }

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

}