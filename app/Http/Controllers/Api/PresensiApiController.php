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
    private function generateRecordHash(
    string $pin,
    string $tanggal,
    string $jam
): string
{
    return hash(
        'sha256',
        trim($pin) . '|' .
        trim($tanggal) . '|' .
        trim($jam)
    );
}
 public function upload(Request $request)
{
    $request->validate([
        'pin'      => 'required|string',
        'nama'     => 'required|string',
        'tanggal'  => 'required|date',
        'jam'      => 'required|date_format:H:i:s'
    ]);

    DB::beginTransaction();

    try {

        $pin = trim($request->pin);
        $nama = trim($request->nama);
        $tanggal = $request->tanggal;
        $jam = $request->jam;

        /*
        |--------------------------------------------------------------------------
        | Generate Hash
        |--------------------------------------------------------------------------
        */

        $recordHash = $this->generateRecordHash(
            $pin,
            $tanggal,
            $jam
        );

        /*
        |--------------------------------------------------------------------------
        | Cek Duplicate
        |--------------------------------------------------------------------------
        */

        $existing = PresensiLog::where(
            'record_hash',
            $recordHash
        )->first();

        if ($existing) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'duplicate' => true,
                'message' => 'Data fingerprint sudah pernah diterima.',
                'record_hash' => $recordHash,
                'log_id' => $existing->id
            ],409);

        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Log
        |--------------------------------------------------------------------------
        */

        $log = PresensiLog::create([

            'record_hash' => $recordHash,

            'pin' => $pin,

            'nama' => $nama,

            'tanggal' => $tanggal,

            'jam' => $jam,

            'verify_code' => 'API',

            'status_sinkron' => 'pending',

            'status_server' => 'success',

            'catatan' => 'Menunggu sinkronisasi'

        ]);

        /*
        |--------------------------------------------------------------------------
        | Queue
        |--------------------------------------------------------------------------
        */

        SinkronisasiPresensiJob::dispatch($log->id);

        DB::commit();

        return response()->json([

            'success' => true,

            'duplicate' => false,

            'message' => 'Data berhasil diterima.',

            'record_hash' => $recordHash,

            'log_id' => $log->id

        ],200);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([

            'success' => false,

            'message' => $e->getMessage()

        ],500);

    }
}
}