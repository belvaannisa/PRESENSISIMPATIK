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
        // 1. TANGKAP SEMUA ERROR AGAR PYTHON BISA MEMBACANYA
        try {
            $dataAbsen = $request->input('absen_list');

            // Cek manual absen_list agar tidak memancing validasi 422 Laravel
            if (empty($dataAbsen) || !is_array($dataAbsen)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format absen_list tidak ditemukan dari mesin.'
                ], 200); // Dikirim sebagai 200 agar Python tidak crash
            }

            DB::beginTransaction();

            // 2. KELOMPOKKAN DATA PER ORANG PER HARI
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
                // Cegah jam yang sama persis masuk berulang kali
                if (!in_array($jam, $groupedData[$key]['scans'])) {
                    $groupedData[$key]['scans'][] = $jam;
                }
            }

            $karyawanMap = Karyawan::whereIn('pin', array_column($groupedData, 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $logUpsert = [];
            $now = now();
            $batasTunggal = config('jabatan.batas_scan_tunggal', '12:30:00');

            foreach ($groupedData as $key => $data) {
                $karyawan = $karyawanMap->get($data['pin']);
                $scans = $data['scans'];
                
                sort($scans); 
                
                // Siapkan data history scan
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

                /*
                |--------------------------------------------------------------------------
                | LOGIKA JAM MASUK & KELUAR 
                |--------------------------------------------------------------------------
                */
                $jamMasuk = null;
                $jamKeluar = null;

                if (count($scans) == 1) {
                    // CUMA 1 SCAN: Jika lewat 12:30 berarti itu absen sore
                    if (strtotime($scans[0]) >= strtotime($batasTunggal)) {
                        $jamKeluar = $scans[0]; 
                    } else {
                        $jamMasuk = $scans[0];  
                    }
                } else {
                    $minJam = $scans[0];
                    $maxJam = end($scans);

                    // DOUBLE TAP: Selisih tap kurang dari 1 jam
                    if (strtotime($maxJam) - strtotime($minJam) < 3600) {
                        if (strtotime($minJam) >= strtotime($batasTunggal)) {
                            $jamKeluar = $maxJam; // Spam tap sore
                        } else {
                            $jamMasuk = $minJam;  // Spam tap pagi
                        }
                    } else {
                        // NORMAL: Tap pagi dan sore
                        $jamMasuk = $minJam;
                        $jamKeluar = $maxJam;
                    }
                }

                // Terapkan Logika Tidak Terbatas
                if (empty($jamKeluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $jamKeluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | LOGIKA HARI BISA & HARI MINGGU
                |--------------------------------------------------------------------------
                */
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

                        // Aturan: Hanya masuk di 2 minggu terakhir
                        if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
                            $status = strtotime($jamMasuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                        } else {
                            $status = 'Tepat Waktu'; // Libur
                        }
                    } else {
                        // Aturan Senin - Sabtu
                        $status = strtotime($jamMasuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | SIMPAN KE PRESENSI (SANGAT AMAN)
                |--------------------------------------------------------------------------
                */
                $presensi = Presensi::firstOrNew([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $data['tanggal']
                ]);
                $presensi->jam_masuk  = $jamMasuk;
                $presensi->jam_keluar = $jamKeluar;
                $presensi->status     = $status;
                $presensi->keterangan = 'Hadir';
                $presensi->sumber     = 'api';
                $presensi->save(); // Menggunakan save standar agar lolos database tanpa error
            }

            // Simpan log history (Upsert aman untuk log karena punya kolom hash unik)
            foreach (array_chunk($logUpsert, 500) as $chunk) {
                PresensiLog::upsert($chunk, ['record_hash'], ['karyawan_id', 'status_sinkron', 'catatan', 'updated_at']);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => "Proses API Selesai. Data berhasil masuk DB.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            // JIKA ADA ERROR, KEMBALIKAN 200 AGAR PYTHON MENCETAK ERROR ASLINYA
            return response()->json([
                'success' => false,
                'message' => 'ERROR BACKEND: ' . $e->getMessage() . ' (Baris: ' . $e->getLine() . ')'
            ], 200); 
        }
    }
}
