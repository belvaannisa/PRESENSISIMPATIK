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
use Carbon\Carbon;

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
        // Ambil semua log yang belum diproses
        $pendingLogs = PresensiLog::where('status_sinkron', 'pending')->get();
        if ($pendingLogs->isEmpty()) return;

        // Ambil data terkait
        $affectedPins = $pendingLogs->pluck('pin')->map(function($p) { return trim($p); })->unique();
        $affectedDates = $pendingLogs->pluck('tanggal')->unique();

        // 1. Kumpulkan semua histori scan di hari itu (Biar Jam Masuk & Keluar selalu presisi)
        $allScans = PresensiLog::whereIn('pin', $affectedPins)
            ->whereIn('tanggal', $affectedDates)
            ->get();
            
        $karyawanMap = Karyawan::whereIn('pin', $affectedPins)
            ->get()
            ->keyBy(function($k) { return trim($k->pin); });

        // Group per PIN dan Tanggal
        $groupedLogs = $allScans->groupBy(function ($log) {
            return trim($log->pin) . '_' . $log->tanggal;
        });

        DB::beginTransaction();

        try {
            foreach ($groupedLogs as $groupKey => $logs) {
                $firstLog = $logs->first();
                $pinStr = trim($firstLog->pin);
                $karyawan = $karyawanMap->get($pinStr);

                if (!$karyawan) {
                    PresensiLog::whereIn('id', $logs->pluck('id'))->update([
                        'status_sinkron' => 'unmatched',
                        'catatan'        => 'PIN tidak ditemukan',
                        'updated_at'     => now()
                    ]);
                    continue;
                }

                $presensi = Presensi::firstOrNew([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $firstLog->tanggal
                ]);

                if (!$presensi->exists) {
                    $presensi->keterangan = 'Hadir';
                    $presensi->sumber     = 'fingerprint';
                }

                // 2. Logika Penentuan Jam Masuk & Keluar
                $minJam = $logs->min('jam');
                $maxJam = $logs->max('jam');

                $presensi->jam_masuk = $minJam;
                
                if ($minJam == $maxJam || (strtotime($maxJam) - strtotime($minJam) < 3600)) {
                    $presensi->jam_keluar = null;
                } else {
                    $presensi->jam_keluar = $maxJam;
                }

                // 3. Logika Tidak Terbatas
                if (empty($presensi->jam_keluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $presensi->jam_keluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                // 4. Logika Hari Biasa & Hari Minggu
                if (empty($presensi->jam_masuk)) {
                    $presensi->status = 'Belum Hadir';
                } else {
                    $tanggalCarbon = Carbon::parse($presensi->tanggal);
                    
                    if ($tanggalCarbon->isSunday()) {
                        $totalSundays = 0;
                        for ($i = 1; $i <= $tanggalCarbon->daysInMonth; $i++) {
                            if ($tanggalCarbon->copy()->day($i)->isSunday()) $totalSundays++;
                        }
                        $mingguKe = ceil($tanggalCarbon->day / 7);

                        // Aturan: Hanya masuk di 2 minggu terakhir
                        if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
                            $presensi->status = strtotime($presensi->jam_masuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                        } else {
                            $presensi->status = 'Tepat Waktu'; // Libur
                        }
                    } else {
                        // Hari Biasa
                        $presensi->status = strtotime($presensi->jam_masuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                $presensi->save();

                // Tandai Selesai
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