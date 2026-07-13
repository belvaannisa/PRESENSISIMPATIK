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
        // 1. Ambil log pending
        $pendingLogs = PresensiLog::where('status_sinkron', 'pending')->get();
        if ($pendingLogs->isEmpty()) return;

        // Key-by PIN dengan trim agar tidak ada spasi yang nyangkut
        $karyawanMap = Karyawan::all()->keyBy(function($k) { return trim($k->pin); });

        $groupedLogs = $pendingLogs->groupBy(function ($log) {
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

                // 2. Ambil SEMUA scan untuk karyawan ini di tanggal tersebut
                $allScans = PresensiLog::where('pin', $pinStr)
                    ->where('tanggal', $firstLog->tanggal)
                    ->get();

                $presensi = Presensi::firstOrNew([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $firstLog->tanggal
                ]);

                if (!$presensi->exists) {
                    $presensi->keterangan = 'Hadir';
                    $presensi->sumber     = 'fingerprint';
                }

                /*
                |--------------------------------------------------------------------------
                | LOGIKA JAM MASUK & KELUAR (Sangat Akurat, Anti Terbalik)
                |--------------------------------------------------------------------------
                */
                $minJam = $allScans->min('jam'); // Waktu terkecil = Pasti Jam Masuk
                $maxJam = $allScans->max('jam'); // Waktu terbesar = Pasti Jam Keluar

                $presensi->jam_masuk = $minJam;
                
                // Jika cuma 1 kali scan, ATAU selisih waktu scan < 1 Jam (Double Tap)
                if ($minJam == $maxJam || (strtotime($maxJam) - strtotime($minJam) < 3600)) {
                    $presensi->jam_keluar = null; // Dianggap belum absen pulang
                } else {
                    $presensi->jam_keluar = $maxJam;
                }

                /*
                |--------------------------------------------------------------------------
                | LOGIKA DEFAULT 17:00 UNTUK "TIDAK TERBATAS"
                |--------------------------------------------------------------------------
                */
                if (empty($presensi->jam_keluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    // Pengecekan ekstra ketat agar config tidak gagal
                    if ($tipeKeluar == 'Tidak Terbatas' || $tipeKeluar == config('jabatan.tidak_terbatas')) {
                        $presensi->jam_keluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | LOGIKA HARI MINGGU & KETERLAMBATAN
                |--------------------------------------------------------------------------
                */
                if (empty($presensi->jam_masuk)) {
                    $presensi->status = 'Belum Hadir';
                } else {
                    $tanggalCarbon = Carbon::parse($presensi->tanggal);
                    
                    if ($tanggalCarbon->isSunday()) {
                        $mingguKe = ceil($tanggalCarbon->day / 7);
                        if ($mingguKe <= 2) {
                            $presensi->status = 'Tepat Waktu'; // Libur
                        } else {
                            $presensi->status = strtotime($presensi->jam_masuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                        }
                    } else {
                        // Hari Biasa
                        $batasNormal = config('jabatan.jam_masuk_default', '08:00:00');
                        $presensi->status = strtotime($presensi->jam_masuk) > strtotime($batasNormal) ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                $presensi->save();

                /*
                |--------------------------------------------------------------------------
                | TANDAI SELESAI
                |--------------------------------------------------------------------------
                */
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