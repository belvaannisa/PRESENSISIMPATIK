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

            $incomingPath .
            '/*.{csv,xls,xlsx}',

            GLOB_BRACE

        );

        if (empty($files)) {

            return back()->with(
                'error',
                'Tidak ada file presensi.'
            );

        }

        foreach ($files as $file) {

            try {

                $rows = $this->bacaFile($file);

                $this->prosesRows($rows);

                rename(

                    $file,

                    $processedPath .
                    '/' .
                    basename($file)

                );

            } catch (Throwable $e) {

                Log::error(

                    'Import gagal : ' .

                    $e->getMessage()

                );

                rename(

                    $file,

                    $failedPath .
                    '/' .
                    basename($file)

                );
            }
        }

        $this->sinkronisasiPresensi();

        $this->kirimDataPendingKeVps();

        return back()->with(
            'success',
            'Auto Import berhasil.'
        );
    }

    public function upload(Request $request)
    {
        $request->validate([

            'file' =>

            'required|mimes:csv,txt,xls,xlsx'

        ]);

        try {

            $file =

                $request->file('file');

            $rows =

                $this->bacaFile(

                    $file->getRealPath(),

                    $file->getClientOriginalExtension()

                );

            $this->prosesRows($rows);

            $this->sinkronisasiPresensi();

            $this->kirimDataPendingKeVps();

            Log::info(

                'Manual Upload : ' .

                $file->getClientOriginalName()

            );

            return back()->with(

                'success',

                'Import berhasil.'

            );

        }

        catch(Throwable $e){

            Log::error(

                $e->getMessage()

            );

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
        foreach ($rows as $index => $row) {

            if ($index == 0) {

                continue;

            }

            if (!$this->validasiBaris($row, $index)) {

                continue;

            }

            try {

                $datetime = new \DateTime(

                    trim($row[3])

                );

            }

            catch (\Exception $e) {

                Log::warning(

                    "Datetime salah pada baris {$index}"

                );

                continue;
            }

            $pin = trim($row[2]);

            $nama = trim($row[1]);

            $tanggal =

                $datetime->format('Y-m-d');

            $jam =

                $datetime->format('H:i:s');

            $verify =

                trim($row[6]);

            $duplicate =

                PresensiLog::where(

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

            if ($duplicate) {

                Log::info(

                    "Duplikat {$pin}"

                );

                continue;

            }

            $karyawan = Karyawan::where('pin', $pin)->first();

            $status = $karyawan ? 'matched' : 'unmatched';

            $log = PresensiLog::create([

                'pin' => $pin,

                'nama' => $nama,

                'tanggal' => $tanggal,

                'jam' => $jam,

                'verify_code' => $verify,

                'karyawan_id' => $karyawan?->id,

                'status_sinkron' => $status,

                'status_server' => 'pending',

                'catatan' => $karyawan
                    ? 'PIN ditemukan'
                    : 'PIN tidak ditemukan'

            ]);

            Log::info(

                'Log dibuat ID=' .

                $log->id

            );

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

    private function prosesLogKePresensi($karyawan,$log)
    {
        DB::transaction(function () use ($karyawan,$log){

            $presensi = Presensi::firstOrCreate(

                [

                    'karyawan_id'=>$karyawan->id,

                    'tanggal'=>$log->tanggal

                ],

                [

                    'keterangan'=>'Hadir',

                    'status'=>'Hadir'

                ]

            );

            if(

                !$presensi->jam_masuk ||

                $log->jam < $presensi->jam_masuk

            ){

                $presensi->jam_masuk =

                    $log->jam;

            }

            if(

                !$presensi->jam_keluar ||

                $log->jam > $presensi->jam_keluar

            ){

                $presensi->jam_keluar =

                    $log->jam;

            }

            $presensi->status =

                $this->tentukanStatus(

                    $presensi->jam_masuk

                );

            $presensi->keterangan =

                'Hadir';

            $presensi->save();

        });
    }

    private function sinkronisasiPresensi()
    {
        $logs = PresensiLog::where(
                    'status_sinkron',
                    'matched'
                )
                ->orderBy('id')
                ->get();

        foreach ($logs as $log) {

            try {

                $karyawan = Karyawan::find($log->karyawan_id);

                if (!$karyawan) {

                    Log::warning(
                        'Karyawan ID '.$log->karyawan_id.' tidak ditemukan'
                    );

                    continue;
                }

                $this->prosesLogKePresensi(
                    $karyawan,
                    $log
                );

                $log->update([
                    'catatan' => 'Presensi berhasil dibuat'
                ]);

                Log::info(
                    'Sinkron berhasil PIN '.$log->pin
                );

            } catch (\Throwable $e) {

                Log::error(
                    'Sinkron gagal PIN '.$log->pin.' : '.$e->getMessage()
                );

            }
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
        $logs =

            PresensiLog::where(

                'status_sinkron',

                'matched'

            )

            ->where(function($q){

                $q->whereNull('status_server')

                ->orWhere(

                    'status_server',

                    'failed'

                )

                ->orWhere(

                    'status_server',

                    'pending'

                );

            })

            ->orderBy('id')

            ->get();

        foreach($logs as $log){

            $this->kirimKeVps($log);

        }

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