<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;

class PresensiApiController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $dataAbsen = $request->input('absen_list');

            if (empty($dataAbsen) || !is_array($dataAbsen)) {
                return response()->json(['success' => false, 'message' => 'Data kosong'], 200); 
            }

            DB::beginTransaction();

            $groupedData = [];
            foreach ($dataAbsen as $item) {
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
                
                if (!in_array($jam, $groupedData[$key]['scans'])) {
                    $groupedData[$key]['scans'][] = $jam;
                }
            }

            // Gunakan array_values agar array_column bisa bekerja dengan sempurna
            $karyawanMap = Karyawan::whereIn('pin', array_column(array_values($groupedData), 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $logUpsert = [];
            
            // Ubah now() menjadi string agar fungsi upsert MySQL tidak error
            $now = now()->toDateTimeString(); 
            $batasTunggal = config('jabatan.batas_scan_tunggal', '12:30:00');

            foreach ($groupedData as $key => $data) {
                $karyawan = $karyawanMap->get($data['pin']);
                $scans = $data['scans'];
                
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

                $jamMasuk = null;
                $jamKeluar = null;

                if (count($scans) == 1) {
                    if (strtotime($scans[0]) >= strtotime($batasTunggal)) {
                        $jamKeluar = $scans[0]; 
                    } else {
                        $jamMasuk = $scans[0];  
                    }
                } else {
                    $minJam = $scans[0];
                    $maxJam = end($scans);

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

                if (empty($jamKeluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $jamKeluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                if (empty($jamMasuk)) {
                    $status = 'Belum Hadir';
                } else {
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
                        $status = strtotime($jamMasuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                // Simpan ke Tabel Presensi
                Presensi::updateOrCreate(
                    [
                        'karyawan_id' => $karyawan->id,
                        'tanggal'     => $data['tanggal']
                    ],
                    [
                        'jam_masuk'  => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'status'     => $status,
                        'keterangan' => 'Hadir',
                        'sumber'     => 'api',
                        'updated_at' => $now
                    ]
                );
            }

            // Simpan Data Log 
            foreach (array_chunk($logUpsert, 500) as $chunk) {
                PresensiLog::upsert($chunk, ['record_hash'], ['karyawan_id', 'status_sinkron', 'catatan', 'updated_at']);
            }

            DB::commit();

            // Jika sukses, hapus file error log agar bersih
            if (file_exists(public_path('error_api_log.txt'))) {
                unlink(public_path('error_api_log.txt'));
            }

            return response()->json([
                'success'    => true,
                'message'    => "Proses Bulk API Selesai. Data sinkron sepenuhnya.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            
            // =========================================================
            // PEREKAM ERROR OTOMATIS (Mencatat apa yang error di server)
            // =========================================================
            $pesanError = "WAKTU ERROR: " . now() . "\n";
            $pesanError .= "PESAN: " . $e->getMessage() . "\n";
            $pesanError .= "BARIS: " . $e->getLine() . "\n";
            $pesanError .= "FILE: " . $e->getFile() . "\n";
            
            file_put_contents(public_path('error_api_log.txt'), $pesanError);
            
            // Tetap return 200 agar Python tidak mendeteksi format salah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi error, silakan cek file log.'
            ], 200); 
        }
    }
}