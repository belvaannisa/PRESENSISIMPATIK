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
use Illuminate\Support\Facades\Http;


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
   


    /**
     * Maksimal retry
     */
   public $tries = 5;


    /**
     * Delay retry (detik)
     */
    public $backoff =10;


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
   protected string $pin;

protected string $tanggal;

public function __construct(
    string $pin,
    string $tanggal
)
{
    $this->pin = trim($pin);

    $this->tanggal = $tanggal;

    $this->onQueue('presensi');
}

    /**
     * Execute the job.
     */
   public function handle(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Cari Karyawan Berdasarkan PIN
        |--------------------------------------------------------------------------
        */
        $karyawan = Karyawan::where('pin', $this->pin)->first();

        if (!$karyawan) {
            // Jika PIN tidak ada, update semua log hari itu jadi unmatched
            PresensiLog::where('pin', $this->pin)
                ->where('tanggal', $this->tanggal)
                ->where('status_sinkron', 'pending')
                ->update([
                    'status_sinkron' => 'unmatched',
                    'catatan'        => 'PIN tidak ditemukan',
                    'updated_at'     => now()
                ]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil SEMUA Scan Hari Itu (Pending & Matched)
        |--------------------------------------------------------------------------
        | Kita mengambil semua data tanpa filter 'pending' agar sistem 
        | bisa membedakan scan paling awal dan paling akhir dengan akurat.
        */
        $logs = PresensiLog::where('pin', $this->pin)
            ->where('tanggal', $this->tanggal)
            ->orderBy('jam')
            ->lockForUpdate()
            ->get();

        if ($logs->isEmpty()) {
            return;
        }

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Ambil / Buat Presensi
            |--------------------------------------------------------------------------
            */
            $presensi = Presensi::firstOrNew([
                'karyawan_id' => $karyawan->id,
                'tanggal'     => $this->tanggal
            ]);

            if (!$presensi->exists) {
                $presensi->keterangan = 'Hadir';
                $presensi->sumber     = 'fingerprint';
            }

            /*
            |--------------------------------------------------------------------------
            | Reset Nilai untuk Dihitung Ulang
            |--------------------------------------------------------------------------
            */
            $presensi->jam_masuk  = null;
            $presensi->jam_keluar = null;

            /*
            |--------------------------------------------------------------------------
            | Proses Semua Scan
            |--------------------------------------------------------------------------
            */
            foreach ($logs as $scan) {
                
                // 1. Jam Masuk = Scan Paling Awal
                if (empty($presensi->jam_masuk) || strtotime($scan->jam) < strtotime($presensi->jam_masuk)) {
                    $presensi->jam_masuk = $scan->jam;
                }

                // 2. Jam Keluar untuk Pegawai Tidak Terbatas
                if ($karyawan->tipe_jam_keluar == config('jabatan.tidak_terbatas')) {
                    if (empty($presensi->jam_keluar) || strtotime($scan->jam) > strtotime($presensi->jam_keluar)) {
                        $presensi->jam_keluar = $scan->jam;
                    }
                } 
                // 3. Jam Keluar untuk Pegawai Terbatas
                else {
                    if (strtotime($scan->jam) >= strtotime(config('jabatan.jam_keluar_default'))) {
                        if (empty($presensi->jam_keluar) || strtotime($scan->jam) > strtotime($presensi->jam_keluar)) {
                            $presensi->jam_keluar = $scan->jam;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Cegah Jam Keluar Sama Persis Dengan Jam Masuk
            |--------------------------------------------------------------------------
            */
            if ($presensi->jam_keluar == $presensi->jam_masuk) {
                $presensi->jam_keluar = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Pegawai Terbatas Tidak Scan Pulang
            |--------------------------------------------------------------------------
            */
            if ($karyawan->tipe_jam_keluar == config('jabatan.terbatas') && empty($presensi->jam_keluar)) {
                $presensi->jam_keluar = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Status Kehadiran
            |--------------------------------------------------------------------------
            */
            $presensi->status = empty($presensi->jam_masuk) 
                ? 'Belum Hadir' 
                : (strtotime($presensi->jam_masuk) > strtotime(config('jabatan.jam_masuk_default')) ? 'Terlambat' : 'Tepat Waktu');

            $presensi->save();

            /*
            |--------------------------------------------------------------------------
            | Update Semua Log Hari Itu
            |--------------------------------------------------------------------------
            */
            PresensiLog::whereIn('id', $logs->pluck('id'))
                ->update([
                    'karyawan_id'    => $karyawan->id,
                    'status_sinkron' => 'matched',
                    'status_server'  => 'pending', 
                    'catatan'        => 'Sinkronisasi berhasil',
                    'updated_at'     => now()
                ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
   
  private function kirimKeVps(PresensiLog $log): void
{
    try {
        $response = Http::retry(2, 1000)
            ->timeout(8)
            ->withHeaders([
                'X-API-KEY' => env('API_KEY'),
            ])
            ->post(env('SERVER_API') . '/api/presensi/upload', [
                'log_id' => $log->id, // Contoh pengisian payload
            ]);

        if ($response->successful()) {
            $log->update([
                'status_server' => 'success',
                'updated_at'    => now(),
            ]);
            return;
        }

        $log->update([
            'status_server' => 'failed',
            'updated_at'    => now(),
        ]);

        Log::error('Upload VPS gagal', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

    } catch (\Throwable $e) {
        $log->update([
            'status_server' => 'failed',
            'updated_at'    => now(),
        ]);

        Log::error($e->getMessage(), [
            'exception' => $e
        ]);
    }
}

    /*
    |--------------------------------------------------------------------------
    | Cari Karyawan Berdasarkan PIN
    |--------------------------------------------------------------------------
    */
   


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
           $presensi->sumber = 'fingerprint';
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
        // Sesuaikan dengan parameter class (pin & tanggal)
        PresensiLog::where('pin', $this->pin)
            ->where('tanggal', $this->tanggal)
            ->where('status_sinkron', 'pending')
            ->update([
                'status_sinkron' => 'failed',
                'catatan' => substr($e->getMessage(), 0, 255)
            ]);

        Log::error('Sinkronisasi Presensi Gagal', [
            'pin' => $this->pin,
            'tanggal' => $this->tanggal,
            'error' => $e->getMessage()
        ]);
    }
}