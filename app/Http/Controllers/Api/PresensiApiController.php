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
        | 1. VALIDASI LONGGAR (Anti-Format Ditolak)
        |--------------------------------------------------------------------------
        */
        $validator = Validator::make($request->all(), [
            'absen_list'           => 'required|array',
            'absen_list.*.pin'     => 'required',
            'absen_list.*.tanggal' => 'required',
            'absen_list.*.jam'     => 'required'
        ]);

        if ($validator->fails()) {
            // Return 200 agar Python tidak ngambek, tapi memunculkan pesan error aslinya
            return response()->json([
                'success' => false,
                'message' => 'Validasi Data Gagal: ' . json_encode($validator->errors())
            ], 200); 
        }

        DB::beginTransaction();

        try {
            $dataAbsen = $request->input('absen_list');
            
            /*
            |--------------------------------------------------------------------------
            | 2. KELOMPOKKAN DATA PER ORANG & TANGGAL (Grouping)
            |--------------------------------------------------------------------------
            */
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
                
                // Mencegah jam ganda di memori
                if (!in_array($jam, $groupedData[$key]['scans'])) {
                    $groupedData[$key]['scans'][] = $jam;
                }
            }

            // Ambil Karyawan sekaligus
            $karyawanMap = Karyawan::whereIn('pin', array_column($groupedData, 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $logUpsert = [];
            $now = now();
            $batasTunggal = config('jabatan.batas_scan_tunggal', '12:30:00');

            /*
            |--------------------------------------------------------------------------
            | 3. LOGIKA CERDAS: JAM MASUK, JAM KELUAR & STATUS
            |--------------------------------------------------------------------------
            */
            foreach ($groupedData as $key => $data) {
                $karyawan = $karyawanMap->get($data['pin']);
                $scans = $data['scans'];
                
                // Urutkan scan dari pagi ke sore
                sort($scans); 
                
                // Siapkan data history PresensiLog
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

                // Jika PIN tidak ada di database, cukup simpan log, lewati Presensi
                if (!$karyawan) continue;

                $jamMasuk = null;
                $jamKeluar = null;

                // ---> A. LOGIKA SCAN TUNGGAL/GANDA
                if (count($scans) == 1) {
                    // Cuma 1 Scan: Apakah pagi atau sore?
                    if (strtotime($scans[0]) >= strtotime($batasTunggal)) {
                        $jamKeluar = $scans[0]; // Absen Pulang
                    } else {
                        $jamMasuk = $scans[0];  // Absen Masuk
                    }
                } else {
                    $minJam = $scans[0];
                    $maxJam = end($scans);

                    // Selisih kurang dari 1 jam = Double tap spam!
                    if (strtotime($maxJam) - strtotime($minJam) < 3600) {
                        if (strtotime($minJam) >= strtotime($batasTunggal)) {
                            $jamKeluar = $maxJam; // Dua-duanya tap sore
                        } else {
                            $jamMasuk = $minJam;  // Dua-duanya tap pagi
                        }
                    } else {
                        // Normal
                        $jamMasuk = $minJam;
                        $jamKeluar = $maxJam;
                    }
                }

                // ---> B. LOGIKA "TIDAK TERBATAS"
                if (empty($jamKeluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $jamKeluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                // ---> C. LOGIKA STATUS & HARI MINGGU
                if (empty($jamMasuk)) {
                    // Tidak ada jam masuk = Belum Hadir (jangan isi Tepat Waktu)
                    $status = 'Belum Hadir';
                } else {
                    $tanggalCarbon = Carbon::parse($data['tanggal']);
                    
                    if ($tanggalCarbon->isSunday()) {
                        // Cari tahu jumlah hari minggu di bulan itu (4 atau 5)
                        $totalSundays = 0;
                        for ($i = 1; $i <= $tanggalCarbon->daysInMonth; $i++) {
                            if ($tanggalCarbon->copy()->day($i)->isSunday()) $totalSundays++;
                        }
                        $mingguKe = ceil($tanggalCarbon->day / 7);

                        // Aturan: Hanya masuk di 2 hari minggu terakhir (Batas 09:15)
                        if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
                            $status = strtotime($jamMasuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                        } else {
                            $status = 'Tepat Waktu'; // Libur
                        }
                    } else {
                        // Hari Biasa: Batas Telat 08:15
                        $status = strtotime($jamMasuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 4. SIMPAN OTOMATIS KE TABEL PRESENSI
                | Menggunakan updateOrCreate untuk mencegah data stuck di log!
                |--------------------------------------------------------------------------
                */
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
                    ]
                );
            }

            // Upsert hanya untuk log (karena record_hash dijamin unik)
            foreach (array_chunk($logUpsert, 500) as $chunk) {
                PresensiLog::upsert($chunk, ['record_hash'], ['karyawan_id', 'status_sinkron', 'catatan', 'updated_at']);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => "Proses Bulk API Selesai. Logika presensi berhasil dijalankan.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            // JIKA ADA ERROR LARAVEL, PYTHON AKAN MENCETAK BARIS ERRORNYA
            return response()->json([
                'success' => false,
                'message' => 'Terjadi Kesalahan Koding Laravel: ' . $e->getMessage() . ' (Baris: ' . $e->getLine() . ')'
            ], 200); 
        }
    }
}