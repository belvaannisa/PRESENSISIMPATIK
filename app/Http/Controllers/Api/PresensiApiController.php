<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;

class PresensiApiController extends Controller
{
   public function upload(Request $request)
{
    $request->validate([
        'pin'      => 'required',
        'nama'     => 'required',
        'tanggal'  => 'required|date',
        'jam'      => 'required',
    ]);

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Generate Record Hash
        |--------------------------------------------------------------------------
        */
        $recordHash = hash(
            'sha256',
            trim($request->pin) . '|' .
            $request->tanggal . '|' .
            $request->jam
        );

        /*
        |--------------------------------------------------------------------------
        | Insert Log (Abaikan jika sudah ada)
        |--------------------------------------------------------------------------
        */
        $insert = PresensiLog::insertOrIgnore([
            'record_hash'     => $recordHash,
            'pin'             => trim($request->pin),
            'nama'            => trim($request->nama),
            'tanggal'         => $request->tanggal,
            'jam'             => $request->jam,
            'verify_code'     => 'API',
            'status_sinkron'  => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Jika Duplicate
        |--------------------------------------------------------------------------
        */
        if ($insert == 0) {

            DB::commit();

            return response()->json([
                'success' => true,
                'duplicate' => true,
                'message' => 'Data sudah pernah diterima.'
            ], 200);

        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Log Yang Baru Disimpan
        |--------------------------------------------------------------------------
        */
        $log = PresensiLog::where('record_hash', $recordHash)->first();

        /*
        |--------------------------------------------------------------------------
        | Sinkronisasi ke tabel presensi
        |--------------------------------------------------------------------------
        */
      SinkronisasiPresensiJob::dispatch($log->id);

        DB::commit();

        return response()->json([
            'success' => true,
            'duplicate' => false,
            'message' => 'Data berhasil diterima.'
        ], 200);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);

    }
}
    /**
     * Sinkronisasi satu log ke tabel presensi
     */
   private function prosesSinkronisasi(PresensiLog $log)
{
    /*
    |--------------------------------------------------------------------------
    | Cari karyawan berdasarkan PIN
    |--------------------------------------------------------------------------
    */

    $karyawan = Karyawan::select('id')
        ->where('pin', $log->pin)
        ->first();

    if (!$karyawan) {

        DB::table('presensi_logs')
            ->where('id', $log->id)
            ->update([
                'status_sinkron' => 'unmatched',
                'updated_at' => now()
            ]);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Cari presensi hari ini
    |--------------------------------------------------------------------------
    */

    $presensi = Presensi::where('karyawan_id', $karyawan->id)
        ->where('tanggal', $log->tanggal)
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Jika belum ada maka buat baru
    |--------------------------------------------------------------------------
    */

    if (!$presensi) {

        $presensi = new Presensi();

        $presensi->karyawan_id = $karyawan->id;
        $presensi->tanggal = $log->tanggal;
        $presensi->jam_masuk = $log->jam;
        $presensi->jam_keluar = $log->jam;
        $presensi->keterangan = 'Hadir';
    }
    else {

        /*
        |--------------------------------------------------------------------------
        | Jam masuk paling awal
        |--------------------------------------------------------------------------
        */

        if (!$presensi->jam_masuk || $log->jam < $presensi->jam_masuk) {
            $presensi->jam_masuk = $log->jam;
        }

        /*
        |--------------------------------------------------------------------------
        | Jam keluar paling akhir
        |--------------------------------------------------------------------------
        */

        if (!$presensi->jam_keluar || $log->jam > $presensi->jam_keluar) {
            $presensi->jam_keluar = $log->jam;
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Status Kehadiran
    |--------------------------------------------------------------------------
    */

    $presensi->status =
        ($presensi->jam_masuk > '08:15:00')
        ? 'Terlambat'
        : 'Tepat Waktu';

    $presensi->save();

    /*
    |--------------------------------------------------------------------------
    | Update status log
    |--------------------------------------------------------------------------
    */

    DB::table('presensi_logs')
        ->where('id', $log->id)
        ->update([
            'karyawan_id' => $karyawan->id,
            'status_sinkron' => 'matched',
            'updated_at' => now()
        ]);
}
}