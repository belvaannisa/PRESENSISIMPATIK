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
    // 1. Validasi struktur pembungkus array Bulk dari Python
    $request->validate([
        'absen_list' => 'required|array',
        'absen_list.*.pin'     => 'required|string',
        'absen_list.*.nama'    => 'required|string',
        'absen_list.*.tanggal' => 'required|string', // Diubah ke string untuk diparsing via Carbon
        'absen_list.*.jam'     => 'required|string'
    ]);


    DB::beginTransaction();


    try {
        $dataAbsen = $request->input('absen_list');
        $suksesCount = 0;
        $duplicateCount = 0;
        $responseLogIds = [];


        foreach ($dataAbsen as $item) {
            $pin = trim($item['pin']);
            $nama = trim($item['nama']);
            
            // Konversi format tanggal DD/MM/YYYY dari Python menjadi YYYY-MM-DD standar MySQL
            try {
                $tanggal = \Carbon\Carbon::createFromFormat('d/m/Y', $item['tanggal'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback jika Python mengirim format YYYY-MM-DD langsung
                $tanggal = \Carbon\Carbon::parse($item['tanggal'])->format('Y-m-d');
            }
            
            // Format jam dipastikan standar H:i:s
            $jam = \Carbon\Carbon::parse($item['jam'])->format('H:i:s');


            /*
            |--------------------------------------------------------------------------
            | Generate Hash & Cek Duplicate
            |--------------------------------------------------------------------------
            */
            $recordHash = $this->generateRecordHash($pin, $tanggal, $jam);
            $existing = PresensiLog::where('record_hash', $recordHash)->first();


            if ($existing) {
                $duplicateCount++;
                continue; // Lewati data ini dan lanjut ke baris berikutnya
            }


            /*
            |--------------------------------------------------------------------------
            | Simpan Log ke Database
            |--------------------------------------------------------------------------
            */
            $log = PresensiLog::create([
                'record_hash'    => $recordHash,
                'pin'            => $pin,
                'nama'           => $nama,
                'tanggal'        => $tanggal,
                'jam'            => $jam,
                'verify_code'    => 'API',
                'status_sinkron' => 'pending',
                'status_server'  => 'success',
                'catatan'        => 'Menunggu sinkronisasi'
            ]);


            /*
            |--------------------------------------------------------------------------
            | Antrean Queue (Dispatched Per Log Id)
            |--------------------------------------------------------------------------
            */
            SinkronisasiPresensiJob::dispatch($log->id);


            $suksesCount++;
            $responseLogIds[] = $log->id;
        }


        DB::commit();


        return response()->json([
            'success'   => true,
            'message'   => "Proses Bulk Selesai. $suksesCount data berhasil disimpan, $duplicateCount data duplikat dilewati.",
            'inserted'  => $suksesCount,
            'duplicate' => $duplicateCount,
            'log_ids'   => $responseLogIds
        ], 200);


    } catch (\Throwable $e) {
        DB::rollBack();


        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses bulk upload server: ' . $e->getMessage()
        ], 500);
    }
}

}