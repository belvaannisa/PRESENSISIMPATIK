<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\PresensiLog;
use App\Jobs\SinkronisasiPresensiJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PresensiController extends Controller
{
    
   /*
|--------------------------------------------------------------------------
| Menentukan Tipe Jam Keluar
|--------------------------------------------------------------------------
*/

private function getTipeJamKeluar(string $jabatan): string
{
    return in_array(
        strtoupper(trim($jabatan)),
        config('jabatan.jabatan_tidak_terbatas'),
        true
    )
        ? config('jabatan.tidak_terbatas')
        : config('jabatan.terbatas');
}

public function index(Request $request)
{
    $search = trim($request->search);

    $presensis = Presensi::query()

        ->with([
            'karyawan:id,nama,jabatan,pin',
            'editor:id,name'
        ])

        ->when($search, function ($query) use ($search) {

            $query->where(function ($q) use ($search) {

                $q->whereDate(
                    'tanggal',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'status',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas(
                    'karyawan',
                    function ($karyawan) use ($search) {

                        $karyawan

                            ->where(
                                'nama',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'pin',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'jabatan',
                                'like',
                                "%{$search}%"
                            );

                    }

                );

            });

        })

        ->orderByDesc('tanggal')

        ->orderBy('jam_masuk')

        ->paginate(10)

        ->withQueryString();

    $no = ($presensis->currentPage() - 1)
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

    if (!is_dir($incomingPath)) {
        return back()->with(
            'error',
            'Folder incoming tidak ditemukan.'
        );
    }

    $files = glob(
        $incomingPath . '/*.{csv,xls,xlsx}',
        GLOB_BRACE
    );

    if (empty($files)) {
        return back()->with(
            'error',
            'Tidak ada file presensi.'
        );
    }

    try {

        foreach ($files as $file) {

            DB::beginTransaction();

            $rows = $this->bacaFile($file);

            $this->prosesRows($rows);

            DB::commit();

            rename(
                $file,
                $processedPath . '/' . basename($file)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dispatch Queue Sinkronisasi
        |--------------------------------------------------------------------------
        */
$this->dispatchPendingLogs();

return back()->with(
    'success',
    'Import berhasil. Data sedang diproses di background.'
);

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error($e);

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

        DB::beginTransaction();

        $file = $request->file('file');

        $rows = $this->bacaFile(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        /*
        |--------------------------------------------------------------------------
        | Simpan Ke Presensi Log
        |--------------------------------------------------------------------------
        */

        $this->prosesRows($rows);

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Dispatch Queue Sinkronisasi
        |--------------------------------------------------------------------------
        */
$this->dispatchPendingLogs();

return back()->with(
    'success',
    'Import berhasil. Data sedang diproses di background.'
);

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error($e);

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}   

    private function validasiBaris(array $row, int $index): bool
{
    /*
    |--------------------------------------------------------------------------
    | Minimal Kolom
    |--------------------------------------------------------------------------
    */

    if (count($row) < 8) {

        Log::warning(
            "Baris {$index} dilewati. Jumlah kolom tidak sesuai."
        );

        return false;

    }

    /*
    |--------------------------------------------------------------------------
    | PIN Kosong
    |--------------------------------------------------------------------------
    */

    if (empty(trim($row[2]))) {

        Log::warning(
            "Baris {$index} dilewati. PIN kosong."
        );

        return false;

    }

    /*
    |--------------------------------------------------------------------------
    | Nama Kosong
    |--------------------------------------------------------------------------
    */

    if (empty(trim($row[1]))) {

        Log::warning(
            "Baris {$index} dilewati. Nama kosong."
        );

        return false;

    }

    /*
    |--------------------------------------------------------------------------
    | Tanggal Scan Kosong
    |--------------------------------------------------------------------------
    */

    if (empty(trim($row[3]))) {

        Log::warning(
            "Baris {$index} dilewati. Tanggal/Jam kosong."
        );

        return false;

    }

    return true;
}

   private function bacaFile($filePath, $extension = null): array
{
    /*
    |--------------------------------------------------------------------------
    | Ambil Extension Jika Tidak Dikirim
    |--------------------------------------------------------------------------
    */

    if (!$extension) {

        $extension = strtolower(

            pathinfo(

                $filePath,

                PATHINFO_EXTENSION

            )

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Excel
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | CSV
    |--------------------------------------------------------------------------
    */

    if ($extension == 'csv' || $extension == 'txt') {

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

    /*
    |--------------------------------------------------------------------------
    | Format Tidak Didukung
    |--------------------------------------------------------------------------
    */

    throw new \Exception(
        'Format file tidak didukung.'
    );
}
 private function dispatchPendingLogs(): void
{
    PresensiLog::select(
            'pin',
            'tanggal'
        )
        ->where('status_sinkron', 'pending')
        ->groupBy(
            'pin',
            'tanggal'
        )
        ->orderBy('tanggal')
        ->chunk(300, function ($groups) {

            foreach ($groups as $group) {

                SinkronisasiPresensiJob::dispatch(
                    $group->pin,
                    $group->tanggal
                );

            }

        });
}

private function prosesRows(array $rows): void
{
    if (empty($rows) || count($rows) <= 1) {
        return;
    }

    $karyawanMap = Karyawan::select(
            'id',
            'pin'
        )
        ->get()
        ->keyBy(function ($item) {
            return trim($item->pin);
        });

    $insertData = [];

    foreach ($rows as $index => $row) {

        if ($index === 0) {
            continue;
        }

        if (!$this->validasiBaris($row, $index)) {
            continue;
        }

        try {

            $datetime = new \DateTime(
                trim($row[3])
            );

        } catch (\Throwable $e) {

            Log::warning(
                "Baris {$index} gagal diparse."
            );

            continue;
        }

        $pin = trim($row[2]);

        $nama = strtoupper(
            trim($row[1])
        );

        $tanggal = $datetime->format('Y-m-d');

        $jam = $datetime->format('H:i:s');

        $verify = trim($row[6]);

        $recordHash = $this->generateRecordHash(
            $pin,
            $tanggal,
            $jam
        );

        $karyawan = $karyawanMap->get($pin);

        $insertData[] = [

            'record_hash'    => $recordHash,

            'pin'            => $pin,

            'nama'           => $nama,

            'tanggal'        => $tanggal,

            'jam'            => $jam,

            'verify_code'    => $verify,

            'karyawan_id'    => $karyawan?->id,

            'status_sinkron' => $karyawan
                ? 'pending'
                : 'unmatched',

            'status_server'  => 'pending',

            'catatan'        => $karyawan
                ? 'Menunggu sinkronisasi'
                : 'PIN tidak ditemukan',

            'created_at'     => now(),

            'updated_at'     => now()

        ];

        if (count($insertData) >= 1000) {

            PresensiLog::upsert(

                $insertData,

                ['record_hash'],

                [

                    'pin',

                    'nama',

                    'tanggal',

                    'jam',

                    'verify_code',

                    'karyawan_id',

                    'status_sinkron',

                    'status_server',

                    'catatan',

                    'updated_at'

                ]

            );

            $insertData = [];

        }

    }

    if (!empty($insertData)) {

        PresensiLog::upsert(

            $insertData,

            ['record_hash'],

            [

                'pin',

                'nama',

                'tanggal',

                'jam',

                'verify_code',

                'karyawan_id',

                'status_sinkron',

                'status_server',

                'catatan',

                'updated_at'

            ]

        );

    }


}

  private function prosesLogKePresensi(
    Karyawan $karyawan,
    $logs
): void {

    /*
    |--------------------------------------------------------------------------
    | Urutkan Scan Berdasarkan Jam
    |--------------------------------------------------------------------------
    */

    $logs = $logs
        ->sortBy('jam')
        ->values();

    if ($logs->isEmpty()) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil / Buat Presensi
    |--------------------------------------------------------------------------
    */

    $presensi = Presensi::firstOrNew([

        'karyawan_id' => $karyawan->id,

        'tanggal'     => $logs->first()->tanggal

    ]);

    if (!$presensi->exists) {

        $presensi->keterangan = 'Hadir';

        $presensi->sumber = 'fingerprint';

    }

    /*
    |--------------------------------------------------------------------------
    | Proses Semua Scan
    |--------------------------------------------------------------------------
    */

    foreach ($logs as $log) {

        /*
        |--------------------------------------------------------------------------
        | Jam Masuk
        |--------------------------------------------------------------------------
        */

        if (

            empty($presensi->jam_masuk)

            ||

            strtotime($log->jam)

            <

            strtotime($presensi->jam_masuk)

        ) {

            $presensi->jam_masuk = $log->jam;

        }

        /*
        |--------------------------------------------------------------------------
        | Jam Keluar
        |--------------------------------------------------------------------------
        */

        if (

            $karyawan->tipe_jam_keluar

            ==

            config('jabatan.tidak_terbatas')

        ) {

            if (

                empty($presensi->jam_keluar)

                ||

                strtotime($log->jam)

                >

                strtotime($presensi->jam_keluar)

            ) {

                $presensi->jam_keluar = $log->jam;

            }

        } else {

            if (

                strtotime($log->jam)

                >=

                strtotime(config('jabatan.jam_keluar_default'))

            ) {

                if (

                    empty($presensi->jam_keluar)

                    ||

                    strtotime($log->jam)

                    >

                    strtotime($presensi->jam_keluar)

                ) {

                    $presensi->jam_keluar = $log->jam;

                }

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Pegawai Terbatas Tidak Absen Pulang
    |--------------------------------------------------------------------------
    */

    if (

        $karyawan->tipe_jam_keluar

        ==

        config('jabatan.terbatas')

        &&

        empty($presensi->jam_keluar)

    ) {

        $presensi->jam_keluar = null;

    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    $presensi->status = $this->tentukanStatus(
        $presensi->jam_masuk
    );

    /*
    |--------------------------------------------------------------------------
    | Simpan
    |--------------------------------------------------------------------------
    */

    $presensi->save();
}

private function sinkronisasiPresensi(): void
{
    /*
    |--------------------------------------------------------------------------
    | Ambil Log Pending
    |--------------------------------------------------------------------------
    */

    $logs = PresensiLog::where(

            'status_sinkron',

            'pending'

        )

        ->orderBy('tanggal')

        ->orderBy('jam')

        ->get();

    if ($logs->isEmpty()) {

        return;

    }

    /*
    |--------------------------------------------------------------------------
    | Ambil Semua Karyawan Sekali
    |--------------------------------------------------------------------------
    */

    $karyawanMap = Karyawan::whereIn(

            'id',

            $logs->pluck('karyawan_id')
                ->filter()
                ->unique()

        )

        ->get()

        ->keyBy('id');

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Group Per Karyawan + Tanggal
        |--------------------------------------------------------------------------
        */

        $groupLogs = $logs->groupBy(function ($log) {

            return

                $log->karyawan_id

                . '_'

                . $log->tanggal;

        });

        foreach ($groupLogs as $group) {

            $karyawan = $karyawanMap->get(

                $group->first()->karyawan_id

            );

            if (!$karyawan) {

                PresensiLog::whereIn(

                    'id',

                    $group->pluck('id')

                )->update([

                    'status_sinkron' => 'unmatched',

                    'catatan' => 'PIN tidak ditemukan',

                    'updated_at' => now()

                ]);

                continue;

            }

            /*
            |--------------------------------------------------------------------------
            | Sinkronkan Menjadi Presensi
            |--------------------------------------------------------------------------
            */

            $this->prosesLogKePresensi(

                $karyawan,

                $group

            );

            /*
            |--------------------------------------------------------------------------
            | Update Status Log
            |--------------------------------------------------------------------------
            */

            PresensiLog::whereIn(

                'id',

                $group->pluck('id')

            )->update([

                'karyawan_id' => $karyawan->id,

                'status_sinkron' => 'matched',

                'catatan' => 'Sinkronisasi berhasil',

                'updated_at' => now()

            ]);

        }

        DB::commit();

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error($e);

    }
}


private function tentukanStatus($jamMasuk): string
{
    /*
    |--------------------------------------------------------------------------
    | Belum Ada Jam Masuk
    |--------------------------------------------------------------------------
    */

    if (empty($jamMasuk)) {

        return 'Belum Hadir';

    }

    /*
    |--------------------------------------------------------------------------
    | Ambil Jam Masuk Default Dari Config
    |--------------------------------------------------------------------------
    */

    $jamDefault = config(
        'jabatan.jam_masuk_default'
    );

    /*
    |--------------------------------------------------------------------------
    | Tentukan Status
    |--------------------------------------------------------------------------
    */

    return strtotime($jamMasuk)

        >

        strtotime($jamDefault)

        ?

        'Terlambat'

        :

        'Tepat Waktu';
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
    public function update(Request $request, Presensi $presensi)
{
    $request->validate([
        'jam_masuk'  => 'nullable',
        'jam_keluar' => 'nullable',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Hitung Status Otomatis
    |--------------------------------------------------------------------------
    */

    if ($request->filled('jam_masuk')) {

        $status = $this->tentukanStatus($request->jam_masuk);

    } else {

        $status = '-';

    }

    /*
    |--------------------------------------------------------------------------
    | Hitung Keterangan Otomatis
    |--------------------------------------------------------------------------
    */

    $keterangan = ($request->filled('jam_masuk') || $request->filled('jam_keluar'))
        ? 'Hadir'
        : null;

    /*
    |--------------------------------------------------------------------------
    | Update Data Presensi
    |--------------------------------------------------------------------------
    */

   $presensi->update([

        'jam_masuk'  => $request->jam_masuk,
        'jam_keluar' => $request->jam_keluar,
        'status'     => $status,
        'keterangan' => $keterangan,

        'diedit_oleh' => auth()->id(),

        'waktu_edit' => now(),

    ]);

    return redirect()
        ->route('presensi.index')
        ->with('success', 'Data Presensi Berhasil Diupdate!');
}

}
