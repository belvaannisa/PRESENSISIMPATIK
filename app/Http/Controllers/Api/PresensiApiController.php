<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;
use App\Jobs\SinkronisasiPresensiJob;

class PresensiApiController extends Controller
{
    
 public function upload(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Validasi Request
    |--------------------------------------------------------------------------
    */

    $request->validate([

        'pin'      => 'required|string',

        'nama'     => 'required|string',

        'tanggal'  => 'required|date',

        'jam'      => 'required|date_format:H:i:s'

    ]);

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Normalisasi Data
        |--------------------------------------------------------------------------
        */

        $pin      = trim($request->pin);

        $nama     = trim($request->nama);

        $tanggal  = $request->tanggal;

        $jam      = $request->jam;

        /*
        |--------------------------------------------------------------------------
        | Generate Record Hash
        |--------------------------------------------------------------------------
        */

        $recordHash = $this->generateRecordHash(

            $pin,

            $tanggal,

            $jam

        );

        /*
        |--------------------------------------------------------------------------
        | Cek Duplicate Berdasarkan Record Hash
        |--------------------------------------------------------------------------
        */

        $existingLog = PresensiLog::where(

            'record_hash',

            $recordHash

        )->first();

        if ($existingLog) {

            DB::rollBack();

            return response()->json([

                'success'      => false,

                'duplicate'    => true,

                'message'      => 'Data fingerprint sudah pernah diterima.',

                'record_hash'  => $recordHash,

                'log_id'        => $existingLog->id

            ],409);

        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Presensi Log
        |--------------------------------------------------------------------------
        */

        $log = PresensiLog::create([

            'record_hash'     => $recordHash,

            'pin'             => $pin,

            'nama'            => $nama,

            'tanggal'         => $tanggal,

            'jam'             => $jam,

            'verify_code'     => 'API',

            'status_sinkron'  => 'pending',

            'status_server'   => 'success'

        ]);

        /*
        |--------------------------------------------------------------------------
        | Jalankan Queue Sinkronisasi
        |--------------------------------------------------------------------------
        */

        SinkronisasiPresensiJob::dispatch(

            $log->id

        );

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Response Berhasil
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success'      => true,

            'duplicate'    => false,

            'message'      => 'Data berhasil diterima.',

            'record_hash'  => $recordHash,

            'log_id'        => $log->id

        ],200);

    }

    catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([

            'success' => false,

            'message' => $e->getMessage()

        ],500);

    }
}
    /**
     * Sinkronisasi satu log ke tabel presensi
     */
  private function prosesSinkronisasi(PresensiLog $log)
{
    /*
    |--------------------------------------------------------------------------
    | Cari Karyawan Berdasarkan PIN
    |--------------------------------------------------------------------------
    */

    $karyawan = Karyawan::where(
        'pin',
        trim($log->pin)
    )->first();

    /*
    |--------------------------------------------------------------------------
    | PIN Tidak Ditemukan
    |--------------------------------------------------------------------------
    */

    if (!$karyawan) {

        $log->update([

            'status_sinkron' => 'unmatched',

            'catatan' => 'PIN tidak ditemukan'

        ]);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Cari Presensi Hari Yang Sama
    |--------------------------------------------------------------------------
    */

    $presensi = Presensi::where(

            'karyawan_id',

            $karyawan->id

        )

        ->where(

            'tanggal',

            $log->tanggal

        )

        ->first();

    /*
    |--------------------------------------------------------------------------
    | Jika Belum Ada Maka Buat Baru
    |--------------------------------------------------------------------------
    */

    if (!$presensi) {

        $presensi = new Presensi();

        $presensi->karyawan_id = $karyawan->id;

        $presensi->tanggal = $log->tanggal;

        $presensi->jam_masuk = $log->jam;

        $presensi->jam_keluar = null;

        $presensi->status = 'Hadir';

        $presensi->keterangan = 'Hadir';

        $presensi->sumber = 'fingerprint';

    }

    /*
    |--------------------------------------------------------------------------
    | Jika Sudah Ada
    |--------------------------------------------------------------------------
    */

    else {

        /*
        |--------------------------------------------------------------
        | Jam Masuk = paling awal
        |--------------------------------------------------------------
        */

        if (

            empty($presensi->jam_masuk)

            ||

            $log->jam < $presensi->jam_masuk

        ) {

            $presensi->jam_masuk = $log->jam;

        }

        /*
        |--------------------------------------------------------------
        | Jam Keluar = paling akhir
        |--------------------------------------------------------------
        */

        if (

            empty($presensi->jam_keluar)

            ||

            $log->jam > $presensi->jam_keluar

        ) {

            $presensi->jam_keluar = $log->jam;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Tentukan Status
    |--------------------------------------------------------------------------
    */

    if (

        $presensi->jam_masuk >

        '08:15:00'

    ) {

        $presensi->status = 'Terlambat';

    }

    else {

        $presensi->status = 'Tepat Waktu';

    }

    /*
    |--------------------------------------------------------------------------
    | Simpan Presensi
    |--------------------------------------------------------------------------
    */

    $presensi->save();

    /*
    |--------------------------------------------------------------------------
    | Update Presensi Log
    |--------------------------------------------------------------------------
    */

    $log->update([

        'karyawan_id' => $karyawan->id,

        'status_sinkron' => 'matched',

        'catatan' => 'Sinkronisasi berhasil'

    ]);
}
}