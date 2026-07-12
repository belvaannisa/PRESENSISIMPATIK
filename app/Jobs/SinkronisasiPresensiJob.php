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


    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Queue
    |--------------------------------------------------------------------------
    */


    /**
     * Nama queue
     */
    public $queue = 'presensi';


    /**
     * Maksimal retry
     */
    public $tries = 5;


    /**
     * Delay retry (detik)
     */
    public $backoff = 10 ;


    /**
     * Timeout job (detik)
     */
    public $timeout = 120;


    /**
     * ID Log Presensi
     */
    protected $logId;


    /**
     * Create a new job instance.
     */
    public function __construct($logId)
    {
        $this->logId = $logId;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            /*
            |--------------------------------------------------------------------------
            | Ambil Log
            |--------------------------------------------------------------------------
            */
            $log = $this->ambilLog();


            if (!$log) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Cari Karyawan
            |--------------------------------------------------------------------------
            */
            $karyawan = $this->cariKaryawan($log);


            if (!$karyawan) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Ambil / Buat Presensi
            |--------------------------------------------------------------------------
            */
            $presensi = $this->ambilAtauBuatPresensi($karyawan, $log);


            /*
            |--------------------------------------------------------------------------
            | Tentukan Jam Masuk
            |--------------------------------------------------------------------------
            */
            $this->setJamMasuk($presensi, $log);


            /*
            |--------------------------------------------------------------------------
            | Tentukan Jam Keluar
            |--------------------------------------------------------------------------
            */
            $this->setJamKeluar($presensi, $log, $karyawan);


            /*
            |--------------------------------------------------------------------------
            | Tentukan Status
            |--------------------------------------------------------------------------
            */
            $this->tentukanStatus($presensi);


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
            $this->updateLog($log, $karyawan);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Ambil Log Presensi
    |--------------------------------------------------------------------------
    */
    private function ambilLog(): ?PresensiLog
    {
        $log = PresensiLog::find($this->logId);


        // Log tidak ditemukan
        if (!$log) {
            return null;
        }


        // Sudah pernah diproses
        if ($log->status_sinkron === 'matched') {
            return null;
        }


        // Record Hash kosong
        if (empty($log->record_hash)) {
            $log->update([
                'status_sinkron' => 'failed',
                'catatan' => 'Record Hash kosong'
            ]);
            return null;
        }


        return $log;
    }


    /*
    |--------------------------------------------------------------------------
    | Cari Karyawan Berdasarkan PIN
    |--------------------------------------------------------------------------
    */
    private function cariKaryawan(PresensiLog $log): ?Karyawan
    {
        $karyawan = Karyawan::where('pin', trim($log->pin))->first();


        // PIN Tidak Ditemukan
        if (!$karyawan) {
            $log->update([
                'status_sinkron' => 'unmatched',
                'catatan' => 'PIN tidak ditemukan',
                'updated_at' => now()
            ]);
            return null;
        }


        return $karyawan;
    }


    /*
    |--------------------------------------------------------------------------
    | Ambil Atau Buat Presensi Baru (Kelanjutan kode yang terpotong)
    |--------------------------------------------------------------------------
    */
    private function ambilAtauBuatPresensi(Karyawan $karyawan, PresensiLog $log): Presensi
    {
        // Cari Presensi Hari Yang Sama
        $presensi = Presensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $log->tanggal)
            ->lockForUpdate()
            ->first();


        // Jika Belum Ada Maka Buat Baru
        if (!$presensi) {
            $presensi = new Presensi();
            $presensi->karyawan_id = $karyawan->id;
            $presensi->tanggal = $log->tanggal;
            $presensi->jam_masuk = null;
            $presensi->jam_keluar = null;
            $presensi->status = 'Tepat Waktu';
            $presensi->keterangan = 'Hadir';
            $presensi->sumber = 'Mesin'; // Contoh pelengkap nilai terpotong
        }


        return $presensi;
    }
    /*
|--------------------------------------------------------------------------
| Tentukan Jam Masuk
|--------------------------------------------------------------------------
*/
private function setJamMasuk(
    Presensi $presensi,
    PresensiLog $log
): void {


    /*
    |--------------------------------------------------------------------------
    | Jika belum ada jam masuk
    |--------------------------------------------------------------------------
    */


    if (empty($presensi->jam_masuk)) {


        $presensi->jam_masuk = $log->jam;


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Ambil scan paling awal
    |--------------------------------------------------------------------------
    */


    if (


        strtotime($log->jam)


        <


        strtotime($presensi->jam_masuk)


    ) {


        $presensi->jam_masuk = $log->jam;


    }


}
/*
|--------------------------------------------------------------------------
| Tentukan Jam Keluar
|--------------------------------------------------------------------------
*/
private function setJamKeluar(
    Presensi $presensi,
    PresensiLog $log,
    Karyawan $karyawan
): void {


    /*
    |--------------------------------------------------------------------------
    | Ambil Tipe Jam Keluar Pegawai
    |--------------------------------------------------------------------------
    */


    $tipeJamKeluar = $karyawan->tipe_jam_keluar;


    /*
    |--------------------------------------------------------------------------
    | Pegawai Tidak Terbatas
    |--------------------------------------------------------------------------
    */


    if (


        $tipeJamKeluar == config('jabatan.tidak_terbatas')


    ) {


        if (


            empty($presensi->jam_keluar)


            ||


            strtotime($log->jam)


            >


            strtotime($presensi->jam_keluar)


        ) {


            $presensi->jam_keluar = $log->jam;


        }


        return;


    }


    /*
    |--------------------------------------------------------------------------
    | Pegawai Terbatas
    |--------------------------------------------------------------------------
    */


    $jamDefault = config('jabatan.jam_keluar_default');


    /*
    |--------------------------------------------------------------------------
    | Scan Sebelum Jam Pulang
    |--------------------------------------------------------------------------
    */


    if (


        strtotime($log->jam)


        <


        strtotime($jamDefault)


    ) {


        return;


    }


    /*
    |--------------------------------------------------------------------------
    | Scan Sesudah Jam Pulang
    |--------------------------------------------------------------------------
    */


    if (


        empty($presensi->jam_keluar)


        ||


        strtotime($log->jam)


        >


        strtotime($presensi->jam_keluar)


    ) {


        $presensi->jam_keluar = $log->jam;


    }


}

/*
|--------------------------------------------------------------------------
| Tentukan Status Kehadiran
|--------------------------------------------------------------------------
*/
private function tentukanStatus(Presensi $presensi): void
{
    /*
    |--------------------------------------------------------------------------
    | Jam Masuk Belum Ada
    |--------------------------------------------------------------------------
    */
    if (empty($presensi->jam_masuk)) {

        $presensi->status = 'Belum Hadir';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Terlambat / Tepat Waktu
    |--------------------------------------------------------------------------
    */

    $presensi->status =
        strtotime($presensi->jam_masuk) >
        strtotime(config('jabatan.jam_masuk_default'))
            ? 'Terlambat'
            : 'Tepat Waktu';
}

/*
|--------------------------------------------------------------------------
| Update Status Sinkronisasi Log
|--------------------------------------------------------------------------
*/
private function updateLog(
    PresensiLog $log,
    Karyawan $karyawan
): void
{
    $log->update([

        'karyawan_id'     => $karyawan->id,

        'status_sinkron'  => 'matched',

        'catatan'         => 'Sinkronisasi berhasil',

        'updated_at'      => now()

    ]);
}

/*
|--------------------------------------------------------------------------
| Job Gagal Setelah Retry Habis
|--------------------------------------------------------------------------
*/
public function failed(\Throwable $e): void
{
    $log = PresensiLog::find($this->logId);

    if ($log) {

        $log->update([

            'status_sinkron' => 'failed',

            'catatan' => $e->getMessage()

        ]);

    }

    Log::error(

        'Sinkronisasi Presensi Gagal',

        [

            'log_id' => $this->logId,

            'error' => $e->getMessage()

        ]

    );
}

}