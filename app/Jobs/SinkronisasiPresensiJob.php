<?php

namespace App\Jobs;

use App\Models\Karyawan;
use App\Models\Presensi;
use App\Models\PresensiLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SinkronisasiPresensiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; 

    public function __construct()
    {
        $this->onQueue('presensi');
    }

    public function handle(): void
    {
        // 1. Ambil SEMUA log yang masih pending sekaligus
        $pendingLogs = PresensiLog::where('status_sinkron', 'pending')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        if ($pendingLogs->isEmpty()) {
            return;
        }

        // 2. Ambil semua Karyawan agar tidak query berulang kali (Super Cepat)
        $karyawanMap = Karyawan::all()->keyBy('pin');

        // 3. Kelompokkan Log berdasarkan PIN dan Tanggal
        $groupedLogs = $pendingLogs->groupBy(function ($log) {
            return trim($log->pin) . '_' . $log->tanggal;
        });

        DB::beginTransaction();

        try {
            foreach ($groupedLogs as $groupKey => $logs) {
                $firstLog = $logs->first();
                $karyawan = $karyawanMap->get(trim($firstLog->pin));

                if (!$karyawan) {
                    PresensiLog::whereIn('id', $logs->pluck('id'))->update([
                        'status_sinkron' => 'unmatched',
                        'catatan'        => 'PIN tidak ditemukan',
                        'updated_at'     => now()
                    ]);
                    continue;
                }

                // 4. Ambil SEMUA scan hari itu (termasuk yg lama) agar jam masuk/keluar presisi
                $allScans = PresensiLog::where('pin', trim($firstLog->pin))
                    ->where('tanggal', $firstLog->tanggal)
                    ->orderBy('jam')
                    ->get();

                $presensi = Presensi::firstOrNew([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $firstLog->tanggal
                ]);

                if (!$presensi->exists) {
                    $presensi->keterangan = 'Hadir';
                    $presensi->sumber     = 'fingerprint';
                }

                $presensi->jam_masuk  = null;
                $presensi->jam_keluar = null;

                // 5. Cari Jam Masuk & Keluar Aktual
                foreach ($allScans as $scan) {
                    if (empty($presensi->jam_masuk) || strtotime($scan->jam) < strtotime($presensi->jam_masuk)) {
                        $presensi->jam_masuk = $scan->jam;
                    }
                    if (empty($presensi->jam_keluar) || strtotime($scan->jam) > strtotime($presensi->jam_keluar)) {
                        $presensi->jam_keluar = $scan->jam;
                    }
                }

                // 6. Validasi Mencegah Double Scan Pagi (Kurang dari 1 jam)
                if (!empty($presensi->jam_masuk) && !empty($presensi->jam_keluar)) {
                    $selisihDetik = strtotime($presensi->jam_keluar) - strtotime($presensi->jam_masuk);
                    if ($selisihDetik < 3600) { 
                        $presensi->jam_keluar = null; // Artinya belum absen pulang
                    }
                }

                // 7. Logika JABATAN (Terbatas & Tidak Terbatas)
                if (empty($presensi->jam_keluar)) {
                    if ($karyawan->tipe_jam_keluar == config('jabatan.tidak_terbatas')) {
                        $presensi->jam_keluar = config('jabatan.jam_keluar_default'); // Tembak 17:00
                    } else {
                        $presensi->jam_keluar = null; // Terbatas tanpa checkout = kosong (-)
                    }
                }

                $presensi->status = empty($presensi->jam_masuk) 
                    ? 'Belum Hadir' 
                    : (strtotime($presensi->jam_masuk) > strtotime(config('jabatan.jam_masuk_default')) ? 'Terlambat' : 'Tepat Waktu');

                $presensi->save();

                // 8. Tandai berhasil
                PresensiLog::whereIn('id', $logs->pluck('id'))->update([
                    'karyawan_id'    => $karyawan->id,
                    'status_sinkron' => 'matched',
                    'status_server'  => 'pending', 
                    'catatan'        => 'Sinkronisasi berhasil',
                    'updated_at'     => now()
                ]);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sinkronisasi Gagal: ' . $e->getMessage());
            throw $e;
        }
    }
}