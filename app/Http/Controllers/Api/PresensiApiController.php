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

    DB::beginTransaction();

    try {
        $dataAbsen = $request->input('absen_list');
        $suksesCount = 0;
        $duplicateCount = 0;
        $responseLogIds = [];
        
        // Simpan kombinasi unik untuk dikirim ke Job nanti
        $jobsToDispatch = []; 

        foreach ($dataAbsen as $item) {
            $pin = trim($item['pin']);
            $nama = trim($item['nama']);
            
            try {
                $tanggal = \Carbon\Carbon::createFromFormat('d/m/Y', $item['tanggal'])->format('Y-m-d');
            } catch (\Exception $e) {
                $tanggal = \Carbon\Carbon::parse($item['tanggal'])->format('Y-m-d');
            }
            
            $jam = \Carbon\Carbon::parse($item['jam'])->format('H:i:s');
            $recordHash = $this->generateRecordHash($pin, $tanggal, $jam);
            
            $existing = PresensiLog::where('record_hash', $recordHash)->first();

            if ($existing) {
                $duplicateCount++;
                continue; 
            }

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

            // Kumpulkan Job unik
            $jobKey = $pin . '_' . $tanggal;
            if (!isset($jobsToDispatch[$jobKey])) {
                $jobsToDispatch[$jobKey] = ['pin' => $pin, 'tanggal' => $tanggal];
            }

            $suksesCount++;
            $responseLogIds[] = $log->id;
        }

        DB::commit();

        // Dispatch Job SETELAH commit (agar lebih aman)
        foreach ($jobsToDispatch as $job) {
            SinkronisasiPresensiJob::dispatch($job['pin'], $job['tanggal']);
        }

        return response()->json([
            'success'   => true,
            'message'   => "Proses Bulk Selesai. $suksesCount disimpan, $duplicateCount duplikat.",
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