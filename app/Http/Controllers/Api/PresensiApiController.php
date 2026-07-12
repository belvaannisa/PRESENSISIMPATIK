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

        foreach ($dataAbsen as $item) {
            $pin = trim($item['pin']);
            $nama = trim($item['nama']);
            
            // Konversi Tanggal
            try {
                $tanggal = \Carbon\Carbon::createFromFormat('d/m/Y', $item['tanggal'])->format('Y-m-d');
            } catch (\Exception $e) {
                $tanggal = \Carbon\Carbon::parse($item['tanggal'])->format('Y-m-d');
            }
            
            // Konversi Jam
            $jam = \Carbon\Carbon::parse($item['jam'])->format('H:i:s');
            
            // Generate Hash
            $recordHash = $this->generateRecordHash($pin, $tanggal, $jam);

            // 1. Siapkan Array untuk Bulk Insert
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
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            // 2. Kumpulkan kombinasi Job yang unik
            $jobKey = $pin . '_' . $tanggal;
            if (!isset($jobsToDispatch[$jobKey])) {
                $jobsToDispatch[$jobKey] = [
                    'pin' => $pin,
                    'tanggal' => $tanggal
                ];
            }
        }

        // 3. Eksekusi Bulk Insert / Upsert (Hanya butuh sekian milidetik)
        // Array di-chunk per 500 data agar MySQL tidak kewalahan
        $chunks = array_chunk($insertData, 500);
        foreach ($chunks as $chunk) {
            PresensiLog::upsert(
                $chunk,
                ['record_hash'], // Acuan data unik (cegah duplikat otomatis)
                ['updated_at']   // Jika duplikat, cukup update waktunya saja
            );
        }

        // 4. Masukkan ke Antrean Queue
        foreach ($jobsToDispatch as $job) {
            SinkronisasiPresensiJob::dispatch($job['pin'], $job['tanggal']);
        }

        return response()->json([
            'success'    => true,
            'message'    => "Proses Bulk Selesai. Data berhasil dimasukkan ke antrean.",
            'total_data' => count($insertData)
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses bulk upload server: ' . $e->getMessage()
        ], 500);
    }
}
}