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
            
            /*
            |--------------------------------------------------------------------------
            | 1. Kumpulkan Data (Group) Per PIN dan Tanggal Terlebih Dahulu
            |--------------------------------------------------------------------------
            */
            $groupedData = [];
            foreach ($dataAbsen as $item) {
                $pin = trim($item['pin']);
                $nama = trim($item['nama']);
                
                // Konversi format tanggal & jam
                try {
                    $tanggal = Carbon::createFromFormat('d/m/Y', trim($item['tanggal']))->format('Y-m-d');
                } catch (\Exception $e) {
                    $tanggal = Carbon::parse(trim($item['tanggal']))->format('Y-m-d');
                }
                
                $jam = Carbon::parse(trim($item['jam']))->format('H:i:s');
                $key = $pin . '|' . $tanggal;
                
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'pin'     => $pin,
                        'nama'    => $nama,
                        'tanggal' => $tanggal,
                        'scans'   => []
                    ];
                }
                $groupedData[$key]['scans'][] = $jam;
            }

            // Ambil data Karyawan untuk dicocokkan
            $karyawanMap = Karyawan::whereIn('pin', array_column($groupedData, 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $presensiUpsert = [];
            $logUpsert = [];
            $now = now();

            /*
            |--------------------------------------------------------------------------
            | 2. Proses Logika Penentuan Jam Masuk, Keluar, dan Status
            |--------------------------------------------------------------------------
            */
            foreach ($groupedData as $key => $data) {
                $karyawan = $karyawanMap->get($data['pin']);
                $scans = $data['scans'];
                
                // Urutkan jam dari terkecil ke terbesar
                sort($scans); 
                
                $jamMasuk = $scans[0];
                $jamKeluar = end($scans);

                // Siapkan data untuk PresensiLog
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

                // Jika scan cuma 1, atau jarak masuk & keluar kurang dari 1 jam (Double Tap)
                if ($jamMasuk == $jamKeluar || (strtotime($jamKeluar) - strtotime($jamMasuk) < 3600)) {
                    $jamKeluar = null;
                }

                // Logika Tidak Terbatas
                if (empty($jamKeluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $jamKeluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                // Logika Status Hari Biasa & Hari Minggu (Batas Telat 08:15 / 09:15)
                $tanggalCarbon = Carbon::parse($data['tanggal']);
                
                if ($tanggalCarbon->isSunday()) {
                    // Hitung total hari minggu dalam bulan tersebut
                    $totalSundays = 0;
                    for ($i = 1; $i <= $tanggalCarbon->daysInMonth; $i++) {
                        if ($tanggalCarbon->copy()->day($i)->isSunday()) $totalSundays++;
                    }
                    $mingguKe = ceil($tanggalCarbon->day / 7);

                    // Aturan: Hanya masuk di 2 minggu terakhir (Minggu 3&4 ATAU Minggu 4&5)
                    if ($mingguKe == $totalSundays || $mingguKe == ($totalSundays - 1)) {
                        $status = strtotime($jamMasuk) > strtotime('09:15:00') ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $status = 'Tepat Waktu'; // Libur
                    }
                } else {
                    // Hari Senin - Sabtu
                    $status = strtotime($jamMasuk) > strtotime('08:15:00') ? 'Terlambat' : 'Tepat Waktu';
                }

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
            | 3. Eksekusi Upload ke Database
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
                'message'    => "Proses Bulk API Selesai. Data 100% sinkron.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses bulk upload: ' . $e->getMessage()
            ], 500);
        }
    }
}