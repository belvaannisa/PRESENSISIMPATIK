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
        try {

            $request->validate([
                'pin'      => 'required',
                'nama'     => 'required',
                'tanggal'  => 'required|date',
                'jam'      => 'required',
            ]);

            // Cek duplicate log
            $cek = PresensiLog::where('pin', $request->pin)
                ->where('tanggal', $request->tanggal)
                ->where('jam', $request->jam)
                ->exists();

            if ($cek) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate'
                ], 200);
            }

            // Simpan log
            $log = PresensiLog::create([
                'pin'             => $request->pin,
                'nama'            => $request->nama,
                'tanggal'         => $request->tanggal,
                'jam'             => $request->jam,
                'verify_code'     => 'API',
                'status_sinkron'  => 'pending'
            ]);

            // Sinkronkan ke tabel presensi
            $this->prosesSinkronisasi($log);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diterima',
                'data'    => $log
            ], 200);

        } catch (\Exception $e) {

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
        $karyawan = Karyawan::where('pin', $log->pin)
            ->orWhere('nama', $log->nama)
            ->first();

        if (!$karyawan) {

            $log->update([
                'status_sinkron' => 'unmatched'
            ]);

            return;
        }

        DB::transaction(function () use ($karyawan, $log) {

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

            // Jam masuk paling awal
            if (!$presensi->jam_masuk || $log->jam < $presensi->jam_masuk) {
                $presensi->jam_masuk = $log->jam;
            }

            // Jam keluar paling akhir
            if (!$presensi->jam_keluar || $log->jam > $presensi->jam_keluar) {
                $presensi->jam_keluar = $log->jam;
            }

            // Status
            $presensi->status =
                strtotime($presensi->jam_masuk) > strtotime('08:15:00')
                ? 'Terlambat'
                : 'Tepat Waktu';

            $presensi->keterangan = 'Hadir';

            $presensi->save();

            $log->update([
                'karyawan_id'    => $karyawan->id,
                'status_sinkron' => 'matched'
            ]);
        });
    }
}