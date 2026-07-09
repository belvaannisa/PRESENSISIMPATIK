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

 public function handle(): void
{
    DB::transaction(function () {

        /*
        |--------------------------------------------------------------------------
        | Ambil Log
        |--------------------------------------------------------------------------
        */

        $log = PresensiLog::find($this->logId);

        if (!$log) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Sudah pernah diproses
        |--------------------------------------------------------------------------
        */

        if ($log->status_sinkron == 'matched') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Cari Karyawan
        |--------------------------------------------------------------------------
        */

        $karyawan = Karyawan::where(
            'pin',
            trim($log->pin)
        )->first();

        if (!$karyawan) {

            $log->update([

                'status_sinkron' => 'unmatched'

            ]);

            return;

        }

        /*
        |--------------------------------------------------------------------------
        | Cari Presensi Hari Itu
        |--------------------------------------------------------------------------
        */

        $presensi = Presensi::firstOrNew([

            'karyawan_id' => $karyawan->id,

            'tanggal' => $log->tanggal

        ]);

        /*
        |--------------------------------------------------------------------------
        | Data Baru
        |--------------------------------------------------------------------------
        */

        if (!$presensi->exists) {

            $presensi->keterangan = 'Hadir';

            $presensi->status = 'Tepat Waktu';

            $presensi->sumber = 'fingerprint';

        }

        /*
        |--------------------------------------------------------------------------
        | Jam Masuk
        |--------------------------------------------------------------------------
        */

        if (

            empty($presensi->jam_masuk)

            ||

            $log->jam < $presensi->jam_masuk

        ) {

            $presensi->jam_masuk = $log->jam;

        }

        /*
        |--------------------------------------------------------------------------
        | Jam Keluar
        |--------------------------------------------------------------------------
        */

        if (

            empty($presensi->jam_keluar)

            ||

            $log->jam > $presensi->jam_keluar

        ) {

            $presensi->jam_keluar = $log->jam;

        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $presensi->status =

            $presensi->jam_masuk > '08:15:00'

            ?

            'Terlambat'

            :

            'Tepat Waktu';

        $presensi->save();

        /*
        |--------------------------------------------------------------------------
        | Update Log
        |--------------------------------------------------------------------------
        */

        $log->update([

            'karyawan_id' => $karyawan->id,

            'status_sinkron' => 'matched'

        ]);

    });
}
}