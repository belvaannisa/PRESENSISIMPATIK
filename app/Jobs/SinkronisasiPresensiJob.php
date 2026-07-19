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
        $pendingLogs = PresensiLog::where('status_sinkron', 'pending')->get();
        if ($pendingLogs->isEmpty()) return;

        $affectedPins = $pendingLogs->pluck('pin')->map(function($p) { return trim($p); })->unique();
        $affectedDates = $pendingLogs->pluck('tanggal')->unique();

        $allScans = PresensiLog::whereIn('pin', $affectedPins)
            ->whereIn('tanggal', $affectedDates)
            ->get();
            
        $karyawanMap = Karyawan::whereIn('pin', $affectedPins)
            ->get()
            ->keyBy(function($k) { return trim($k->pin); });

        $groupedLogs = $allScans->groupBy(function ($log) {
            return trim($log->pin) . '_' . $log->tanggal;
        });

        DB::beginTransaction();

        try {
            $batasTunggal = config('jabatan.batas_scan_tunggal', '12:30:00');

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

                // Susun array jam untuk karyawan ini
                $scansArray = $logs->pluck('jam')->sort()->values()->all();
                
                $jamMasuk = null;
                $jamKeluar = null;

                if (count($scansArray) == 1) {
                    if (strtotime($scansArray[0]) >= strtotime($batasTunggal)) {
                        $jamKeluar = $scansArray[0]; 
                    } else {
                        $jamMasuk = $scansArray[0];  
                    }
                } else {
                    $minJam = $scansArray[0];
                    $maxJam = end($scansArray);

                    if (strtotime($maxJam) - strtotime($minJam) < 3600) {
                        if (strtotime($minJam) >= strtotime($batasTunggal)) {
                            $jamKeluar = $maxJam; 
                        } else {
                            $jamMasuk = $minJam;  
                        }
                    } else {
                        $jamMasuk = $minJam;
                        $jamKeluar = $maxJam;
                    }
                }

                $presensi->jam_masuk = $jamMasuk;
                $presensi->jam_keluar = $jamKeluar;

                // Logika Tidak Terbatas
                if (empty($presensi->jam_keluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $presensi->jam_keluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                // Logika Status
                if (empty($jamMasuk)) {
                    // PERBAIKAN: Disimpan ke $presensi->status agar masuk ke database
                    $presensi->status = !empty($jamKeluar) ? 'Tidak Absen Pagi' : 'Belum Hadir';
                } else {
                    $tanggalCarbon = Carbon::parse($presensi->tanggal);
                    
                    if ($tanggalCarbon->isSunday()) {
                        $totalSundays = 0;
                        for ($i = 1; $i <= $tanggalCarbon->daysInMonth; $i++) {
                            if ($tanggalCarbon->copy()->day($i)->isSunday()) $totalSundays++;
                        }
                        $mingguKe = ceil($tanggalCarbon->day / 7);

                        if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
                            $presensi->status = strtotime($presensi->jam_masuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                        } else {
                            $presensi->status = 'Tepat Waktu'; 
                        }
                    } else {
                        $presensi->status = strtotime($presensi->jam_masuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                $presensi->save();

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