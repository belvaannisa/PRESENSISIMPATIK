<?php

namespace App\Jobs;

use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SinkronisasiPresensiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $logId;

    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    public function handle()
    {
        $log = PresensiLog::find($this->logId);

        if (!$log) {
            return;
        }

        $karyawan = Karyawan::select('id')
            ->where('pin', $log->pin)
            ->first();

        if (!$karyawan) {

            $log->update([
                'status_sinkron' => 'unmatched'
            ]);

            return;
        }

        $presensi = Presensi::firstOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'tanggal' => $log->tanggal
            ]
        );

        if (!$presensi->jam_masuk || $log->jam < $presensi->jam_masuk) {
            $presensi->jam_masuk = $log->jam;
        }

        if (!$presensi->jam_keluar || $log->jam > $presensi->jam_keluar) {
            $presensi->jam_keluar = $log->jam;
        }

        $presensi->status =
            $presensi->jam_masuk > '08:15:00'
            ? 'Terlambat'
            : 'Tepat Waktu';

        $presensi->keterangan = 'Hadir';

        $presensi->save();

        $log->update([
            'karyawan_id' => $karyawan->id,
            'status_sinkron' => 'matched'
        ]);
    }
}