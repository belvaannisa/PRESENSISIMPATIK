<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;

class PresensiApiController extends Controller
{
    public function upload(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDASI MANUAL
        | Mencegah Laravel mengirim kode 422 yang membuat Python error
        |--------------------------------------------------------------------------
        */
        $validator = Validator::make($request->all(), [
            'absen_list'           => 'required|array',
            'absen_list.*.pin'     => 'required',
            'absen_list.*.tanggal' => 'required',
            'absen_list.*.jam'     => 'required'
        ]);

        if ($validator->fails()) {
            // Paksa return 500 agar Python mencetak error aslinya (jika memang ada yang gagal)
            return response()->json([
                'success' => false,
                'message' => 'Validasi Data Gagal: ' . json_encode($validator->errors())
            ], 500); 
        }

        DB::beginTransaction();

        try {
            $dataAbsen = $request->input('absen_list');
            
            /*
            |--------------------------------------------------------------------------
            | 2. KELOMPOKKAN DATA (Berdasarkan PIN & Tanggal)
            |--------------------------------------------------------------------------
            */
            $groupedData = [];
            foreach ($dataAbsen as $item) {
                // Konversi paksa ke string agar trim() tidak error jika menerima angka
                $pin = trim((string) $item['pin']);
                $nama = isset($item['nama']) ? trim((string) $item['nama']) : '';
                
                try {
                    $tanggal = Carbon::createFromFormat('d/m/Y', trim((string) $item['tanggal']))->format('Y-m-d');
                } catch (\Exception $e) {
                    $tanggal = Carbon::parse(trim((string) $item['tanggal']))->format('Y-m-d');
                }
                
                $jam = Carbon::parse(trim((string) $item['jam']))->format('H:i:s');
                $key = $pin . '|' . $tanggal;
                
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'pin'     => $pin,
                        'nama'    => $nama,
                        'tanggal' => $tanggal,
                        'scans'   => []
                    ];
                }
                
                // Mencegah jam yang sama persis masuk ke array
                if (!in_array($jam, $groupedData[$key]['scans'])) {
                    $groupedData[$key]['scans'][] = $jam;
                }
            }

            $karyawanMap = Karyawan::whereIn('pin', array_column($groupedData, 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $presensiUpsert = [];
            $logUpsert = [];
            $now = now();
            $batasTunggal = config('jabatan.batas_scan_tunggal', '12:30:00');

            /*
            |--------------------------------------------------------------------------
            | 3. LOGIKA KEPUTUSAN JAM MASUK & KELUAR
            |--------------------------------------------------------------------------
            */
            foreach ($groupedData as $key => $data) {
                $karyawan = $karyawanMap->get($data['pin']);
                $scans = $data['scans'];
                
                // Urutkan dari pagi ke sore
                sort($scans); 
                
                foreach ($scans as $jam) {
                    $recordHash = hash('sha256', $data['pin'] . '|' . $data['tanggal'] . '|' . $jam);
                    $logUpsert[] = [
                        'record_hash'    => $recordHash,
                        'pin'            => $data['pin'],
                        'nama'           => $data['nama'],
                        'tanggal'        => $data['tanggal'],
                        'jam'            => $jam,
                        'verify_code'    => 'API',
                        'karyawan_id'    => $karyawan ? $karyawan->id : null,
                        'status_sinkron' => $karyawan ? 'matched' : 'unmatched',
                        'status_server'  => 'success',
                        'catatan'        => $karyawan ? 'Berhasil via API' : 'PIN tidak ditemukan',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                if (!$karyawan) continue;

                // Di dalam loop proses data (setelah sorting $scans):

$jamMasuk = null;
$jamKeluar = null;

// 1. Tentukan Jam Masuk & Keluar dengan Logika Tunggal/Ganda
if (count($scans) == 1) {
    if (strtotime($scans[0]) >= strtotime($batasTunggal)) {
        $jamKeluar = $scans[0]; 
    } else {
        $jamMasuk = $scans[0];  
    }
} else {
    $minJam = $scans[0];
    $maxJam = end($scans);
    // Double tap
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

// 2. LOGIKA PENENTUAN STATUS (PENTING: Mencegah salah status)
if (empty($jamMasuk)) {
    // Jika tidak ada scan pagi, statusnya Belum Hadir (jangan Tepat Waktu!)
    $status = 'Belum Hadir';
} else {
    // Hanya jika ADA jam masuk, kita hitung telat/tidaknya
    $tanggalCarbon = Carbon::parse($data['tanggal']);
    
    if ($tanggalCarbon->isSunday()) {
        $totalSundays = 0;
        for ($i = 1; $i <= $tanggalCarbon->daysInMonth; $i++) {
            if ($tanggalCarbon->copy()->day($i)->isSunday()) $totalSundays++;
        }
        $mingguKe = ceil($tanggalCarbon->day / 7);
        
        if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
            $status = strtotime($jamMasuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
        } else {
            $status = 'Tepat Waktu';
        }
    } else {
        // HARI BIASA: Jika jam masuk ada, cek apakah telat dari 08:15
        $status = strtotime($jamMasuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
    }
}

// 3. Simpan ke tabel Presensi (Wajib dilakukan di sini agar sinkron)
Presensi::updateOrCreate(
    ['karyawan_id' => $karyawan->id, 'tanggal' => $data['tanggal']],
    [
        'jam_masuk'  => $jamMasuk,
        'jam_keluar' => $jamKeluar,
        'status'     => $status,
        'keterangan' => 'Hadir',
        'sumber'     => 'api',
        'updated_at' => now()
    ]
);

                $presensiUpsert[] = [
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $data['tanggal'],
                    'jam_masuk'   => $jamMasuk,
                    'jam_keluar'  => $jamKeluar,
                    'status'      => $status,
                    'keterangan'  => 'Hadir',
                    'sumber'      => 'api',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 4. SIMPAN KE DATABASE (BULK INSERT)
            |--------------------------------------------------------------------------
            */
            foreach (array_chunk($logUpsert, 500) as $chunk) {
                PresensiLog::upsert($chunk, ['record_hash'], ['karyawan_id', 'status_sinkron', 'catatan', 'updated_at']);
            }
            
            foreach (array_chunk($presensiUpsert, 500) as $chunk) {
                Presensi::upsert($chunk, ['karyawan_id', 'tanggal'], ['jam_masuk', 'jam_keluar', 'status', 'keterangan', 'updated_at']);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => "Proses Bulk API Selesai. Data sinkron sepenuhnya.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            // Jika codingan PHP ada yang salah, errornya akan tercetak di layar Python
            return response()->json([
                'success' => false,
                'message' => 'Gagal Proses Server: ' . $e->getMessage() . ' (Baris: ' . $e->getLine() . ')'
            ], 500);
        }
    }
}