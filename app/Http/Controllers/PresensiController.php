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
    
    private function getTipeJamKeluar(string $jabatan): string
    {
        return in_array(
            $jabatan,
            config('jabatan.jabatan_tidak_terbatas')
        )
            ? config('jabatan.tidak_terbatas')
            : config('jabatan.terbatas');
    }

   public function index(Request $request)
    {
        $search = $request->search;

        $presensis = Presensi::with('karyawan')

            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('tanggal', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('karyawan', function ($karyawan) use ($search) {

                            $karyawan->where(
                                'nama',
                                'like',
                                "%{$search}%"
                            );

                    });

                });

            })

            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')

            ->paginate(10);

        $no =
            ($presensis->currentPage() - 1)
            * $presensis->perPage()
            + 1;

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
        if (empty($rows) || count($rows) <= 1) {
            return;
        }

        // Ambil seluruh data karyawan sekali saja
        $karyawanMap = Karyawan::select('id', 'pin')
            ->get()
            ->keyBy(function ($item) {
                return trim($item->pin);
            });

        $insertData = [];

        foreach ($rows as $index => $row) {

            // Skip Header
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

            // Cari karyawan dari RAM (bukan query DB)
            $karyawan = $karyawanMap->get($pin);

            // Generate Record Hash
            $recordHash = $this->generateRecordHash(
                $pin,
                $tanggal,
                $jam
            );

            $insertData[] = [

                'record_hash'     => $recordHash,

                'pin'             => $pin,

                'nama'            => $nama,

                'tanggal'         => $tanggal,

                'jam'             => $jam,

                'verify_code'     => $verify,

                'karyawan_id'     => $karyawan?->id,

                'status_sinkron'  => $karyawan
                    ? 'matched'
                    : 'unmatched',

                'status_server'   => 'pending',

                'catatan'         => $karyawan
                    ? 'PIN ditemukan'
                    : 'PIN tidak ditemukan',

                'created_at'      => now(),

                'updated_at'      => now(),

            ];

            // Batch Insert setiap 1000 data
            if (count($insertData) >= 1000) {

                DB::table('presensi_logs')
                    ->insertOrIgnore($insertData);

                $insertData = [];
            }
        }

        // Insert sisa data
        
        if (!empty($insertData)) {

            DB::table('presensi_logs')
                ->insertOrIgnore($insertData);

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

private function prosesLogKePresensi($karyawan, $logs)
{
    $logs = $logs->sortBy('jam')->values();

    $scanMasuk = $logs->first();

    $scanKeluar = $logs->count() > 1
        ? $logs->last()
        : null;

    $presensi = Presensi::firstOrNew([
        'karyawan_id' => $karyawan->id,
        'tanggal' => $scanMasuk->tanggal,
    ]);

    $presensi->jam_masuk = $scanMasuk->jam;

    $tidakTerbatas =
        $karyawan->tipe_jam_keluar ==
        config('jabatan.tidak_terbatas');

    /*
    |--------------------------------------------------------------------------
    | Pegawai Terbatas
    |--------------------------------------------------------------------------
    */

    if (!$tidakTerbatas) {

        if ($scanKeluar) {

            $presensi->jam_keluar = $scanKeluar->jam;

        } else {

            $presensi->jam_keluar = null;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Pegawai Tidak Terbatas
    |--------------------------------------------------------------------------
    */

    else {

        if ($scanKeluar) {

            $presensi->jam_keluar = $scanKeluar->jam;

        } else {

            $presensi->jam_keluar =
                config('jabatan.jam_keluar_default');

        }

    }

    $presensi->status =
        $this->tentukanStatus($presensi->jam_masuk);

    $presensi->keterangan =
        $presensi->status == 'Terlambat'
            ? 'Terlambat'
            : 'Hadir';

    $presensi->save();
}

private function sinkronisasiPresensi()
{
    $logs = PresensiLog::where('status_sinkron', 'matched')
        ->where('catatan', '!=', 'Presensi berhasil dibuat')
        ->orderBy('tanggal')
        ->orderBy('jam')
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

        $groupLogs = $logs->groupBy(function ($item) {
            return $item->karyawan_id . '_' . $item->tanggal;
        });

        foreach ($groupLogs as $group) {

            $karyawan = $karyawanMap->get(
                $group->first()->karyawan_id
            );

            if (!$karyawan) {
                continue;
            }

            $this->prosesLogKePresensi(
                $karyawan,
                $group
            );
        }

        PresensiLog::whereIn(
            'id',
            $logs->pluck('id')
        )->update([
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

    private function generateRecordHash($pin, $tanggal, $jam)
    {
        return hash(
            'sha256',
            trim($pin).'|'.trim($tanggal).'|'.trim($jam)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'pin'             => 'nullable|string|unique:karyawans,pin',
            'nama'            => 'required|string|max:255',
            'jabatan'         => 'required|in:' . implode(',', Karyawan::$jabatanList),
            'no_hp'           => 'nullable|string|max:20',
            'tanggal_masuk'   => 'nullable|date',
            'status_aktif'    => 'nullable|boolean',
            'jam_keluar'      => 'nullable|date_format:H:i',
        ]);

        $tipeJamKeluar = $this->getTipeJamKeluar($request->jabatan);

        $jamKeluar = $tipeJamKeluar == config('jabatan.tidak_terbatas')
            ? null
            : ($request->jam_keluar ?: config('jabatan.jam_keluar_default'));

        Karyawan::create([

            'pin'                  => $request->pin,
            'nama'                 => strtoupper($request->nama),
            'jabatan'              => $request->jabatan,
            'no_hp'                => $request->no_hp,
            'tanggal_masuk'        => $request->tanggal_masuk,

            'jam_masuk'            => config('jabatan.jam_masuk_default'),

            'jam_keluar'           => $jamKeluar,

            'tipe_jam_keluar'      => $tipeJamKeluar,

            'status_aktif'         => $request->boolean('status_aktif'),

            'sinkron_fingerprint'  => true,

        ]);

        $lastPage = ceil(Karyawan::count() / 10);

        return redirect()
            ->route('karyawan.index', ['page' => $lastPage])
            ->with('success', 'Data Karyawan Berhasil Ditambahkan!');
    }

    // ❌ DELETE
    public function destroy(Presensi $presensi)
    {
        try {

            $presensi->delete();

            return redirect()
                ->route('presensi.index')
                ->with('success', 'Data Presensi Berhasil Dihapus!');

        } catch (\Exception $e) {

            return redirect()
                ->route('presensi.index')
                ->with('error', 'Data Presensi Gagal Dihapus.');

        }
    }

    public function edit(Presensi $presensi)
    {
        return view('presensi.edit', compact('presensi'));
    }

    // 🔄 UPDATE
    public function update(Request $request, Karyawan $karyawan)
    {
            $request->validate([
                'pin' => 'nullable|string|unique:karyawans,pin,' . $karyawan->id,
                'nama' => 'required|string|max:255',
                'jabatan' => 'required',
                'no_hp' => 'nullable|string|max:20',
                'tanggal_masuk' => 'nullable|date',
                'status_aktif' => 'nullable|boolean',
                'jam_keluar' => 'nullable'
            ]);

            $tipeJamKeluar = $this->getTipeJamKeluar($request->jabatan);

            $jamKeluar = $tipeJamKeluar == config('jabatan.tidak_terbatas')
                ? null
                : ($request->jam_keluar ?: config('jabatan.jam_keluar_default'));

            $karyawan->update([
                'pin' => $request->pin,
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'no_hp' => $request->no_hp,
                'tanggal_masuk' => $request->tanggal_masuk,
                'status_aktif' => $request->status_aktif,
                'tipe_jam_keluar' => $tipeJamKeluar,
                'jam_keluar' => $jamKeluar,
            ]);

            return redirect()
                ->route('karyawan.index')
                ->with('success', 'Data Karyawan Berhasil Diedit!');
    }

}