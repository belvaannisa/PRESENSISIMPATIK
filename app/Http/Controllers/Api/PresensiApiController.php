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
        // PERBAIKAN VALIDASI: Menghapus aturan strict 'string' agar PIN angka tidak ditolak
        $request->validate([
            'absen_list'           => 'required|array',
            'absen_list.*.pin'     => 'required',
            'absen_list.*.nama'    => 'nullable',
            'absen_list.*.tanggal' => 'required',
            'absen_list.*.jam'     => 'required'
        ]);

        DB::beginTransaction();

        try {
            $dataAbsen = $request->input('absen_list');
            
            $groupedData = [];
            foreach ($dataAbsen as $item) {
                $pin = trim($item['pin']);
                $nama = isset($item['nama']) ? trim($item['nama']) : '';
                
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

            $karyawanMap = Karyawan::whereIn('pin', array_column($groupedData, 'pin'))
                ->get()
                ->keyBy(function($k) { return trim($k->pin); });
            
            $presensiUpsert = [];
            $logUpsert = [];
            $now = now();
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

                /*
                |--------------------------------------------------------------------------
                | LOGIKA JAM MASUK & KELUAR SUPER PRESISI
                |--------------------------------------------------------------------------
                */
                $jamMasuk = null;
                $jamKeluar = null;

                if (count($scans) == 1) {
                    // CUMA 1 SCAN: Cek apakah ini absen pagi atau absen sore
                    if (strtotime($scans[0]) >= strtotime($batasTunggal)) {
                        $jamKeluar = $scans[0]; // Absen sore
                    } else {
                        $jamMasuk = $scans[0];  // Absen pagi
                    }
                } else {
                    $minJam = $scans[0];
                    $maxJam = end($scans);

                    // DOUBLE TAP (Selisih di bawah 1 jam)
                    if (strtotime($maxJam) - strtotime($minJam) < 3600) {
                        if (strtotime($minJam) >= strtotime($batasTunggal)) {
                            $jamKeluar = $maxJam; // Dua-duanya tap sore
                        } else {
                            $jamMasuk = $minJam;  // Dua-duanya tap pagi
                        }
                    } else {
                        // NORMAL: Tap pagi dan sore
                        $jamMasuk = $minJam;
                        $jamKeluar = $maxJam;
                    }
                }

                // Logika Tidak Terbatas
                if (empty($jamKeluar)) {
                    $tipeKeluar = trim($karyawan->tipe_jam_keluar);
                    if (strcasecmp($tipeKeluar, 'Tidak Terbatas') == 0 || strcasecmp($tipeKeluar, config('jabatan.tidak_terbatas')) == 0) {
                        $jamKeluar = config('jabatan.jam_keluar_default', '17:00:00');
                    }
                }

                // Logika Status Telat
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

            foreach (array_chunk($logUpsert, 500) as $chunk) {
                PresensiLog::upsert($chunk, ['record_hash'], ['karyawan_id', 'status_sinkron', 'catatan', 'updated_at']);
            }
            
            foreach (array_chunk($presensiUpsert, 500) as $chunk) {
                Presensi::upsert($chunk, ['karyawan_id', 'tanggal'], ['jam_masuk', 'jam_keluar', 'status', 'keterangan', 'updated_at']);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => "Proses API Selesai. Data sinkron.",
                'total_data' => count($logUpsert)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}