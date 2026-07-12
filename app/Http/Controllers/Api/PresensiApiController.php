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
    $request->validate([
        'absen_list'           => 'required|array',
        'absen_list.*.pin'     => 'required|string',
        'absen_list.*.nama'    => 'required|string',
        'absen_list.*.tanggal' => 'required|string',
        'absen_list.*.jam'     => 'required|string'
    ]);

    try {
        $dataAbsen = $request->input('absen_list');
        $insertData = [];
        $jobsToDispatch = [];
        $now = now();

        // 1. Looping hanya untuk menyusun array (Sangat Cepat, tanpa query DB)
        foreach ($dataAbsen as $item) {
            $pin = trim($item['pin']);
            $nama = trim($item['nama']);
            
            // Perbaikan Parsing Tanggal
            try {
                $tanggal = \Carbon\Carbon::createFromFormat('d/m/Y', trim($item['tanggal']))->format('Y-m-d');
            } catch (\Exception $e) {
                $tanggal = \Carbon\Carbon::parse(trim($item['tanggal']))->format('Y-m-d');
            }
            
            $jam = \Carbon\Carbon::parse(trim($item['jam']))->format('H:i:s');
            $recordHash = hash('sha256', $pin . '|' . $tanggal . '|' . $jam);

            $insertData[] = [
                'record_hash'    => $recordHash,
                'pin'            => $pin,
                'nama'           => $nama,
                'tanggal'        => $tanggal,
                'jam'            => $jam,
                'verify_code'    => 'API',
                'status_sinkron' => 'pending',
                'status_server'  => 'success',
                'catatan'        => 'Menunggu sinkronisasi',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

           
            $jobKey = $pin . '_' . $tanggal;
            if (!isset($jobsToDispatch[$jobKey])) {
                $jobsToDispatch[$jobKey] = ['pin' => $pin, 'tanggal' => $tanggal];
            }
        }

       
        $chunks = array_chunk($insertData, 500);
        foreach ($chunks as $chunk) {
            \App\Models\PresensiLog::upsert(
                $chunk,
                ['record_hash'], // Patokan data unik
                ['updated_at']   // Jika duplikat, cukup update waktunya
            );
        }

        // 3. Trik agar Python langsung dapat balasan SUKSES tanpa menunggu Job selesai didaftarkan
        $response = response()->json([
            'success'    => true,
            'message'    => "Proses Bulk Selesai. Data masuk antrean.",
            'total_data' => count($insertData)
        ], 200);

        if (function_exists('fastcgi_finish_request')) {
            $response->send();
            fastcgi_finish_request(); // Memutus koneksi dengan sukses, tapi PHP tetap lanjut jalan ke bawah
        }

        // 4. Mendaftarkan Antrean ke Supervisor
        foreach ($jobsToDispatch as $job) {
            \App\Jobs\SinkronisasiPresensiJob::dispatch($job['pin'], $job['tanggal']);
        }

        return function_exists('fastcgi_finish_request') ? null : $response;

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses bulk upload: ' . $e->getMessage()
        ], 500);
    }
}
}