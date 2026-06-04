<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    // ================= INDEX =================
    public function index(Request $request)
    {
        $search = $request->search;

        $presensis = Presensi::with('karyawan')
            ->when($search, function ($query) use ($search) {
                $query->where('tanggal', 'like', "%$search%")
                      ->orWhere('status', 'like', "%$search%")
                      ->orWhereHas('karyawan', function ($q) use ($search) {
                          $q->where('nama', 'like', "%$search%");
                      });
            })
            ->orderBy('id', 'asc') 
            ->paginate(10);

        $no = ($presensis->currentPage() - 1) * $presensis->perPage() + 1;

        return view('presensi.index', compact('presensis', 'search', 'no'));
    }

    // ================= AUTO =================
    
    public function importLocal()
{
    $path = 'C:/Users/LENOVO/datapresensi/incoming/';
    $processedPath = 'C:/Users/LENOVO/datapresensi/processed/';
    $failedPath = 'C:/Users/LENOVO/datapresensi/failed/';

    $files = glob($path . '*.{csv,CSV}', GLOB_BRACE);

    if (!$files) {
        return back()->with('error', 'Tidak Ada File Untuk Diimport!');
    }

    foreach ($files as $file) {
        try {
            if (!file_exists($file)) {
                throw new \Exception('File tidak ditemukan');
            }

            $this->prosesCSV($file);

            if (!is_dir($processedPath)) {
                mkdir($processedPath, 0777, true);
            }

            rename($file, $processedPath . basename($file));

        } catch (\Exception $e) {

            if (!is_dir($failedPath)) {
                mkdir($failedPath, 0777, true);
            }

            rename($file, $failedPath . basename($file));
        }
    }

    return back()->with('success', 'Semua File Berhasil Diproses!');
}
    // ================= MANUAL =================
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file')->getRealPath();

        $this->prosesCSV($file);

        return back()->with('success', 'Data Presensi Berhasil Ditambahkan!');
    }

    // ================= CORE =================
    private function prosesCSV($file)
{
    $rows = array_map('str_getcsv', file($file));

    foreach ($rows as $index => $row) {

        if ($index == 0) continue;

        if (count($row) < 4) continue;

        [$nama, $tanggal, $jam, $keterangan] = $row;

        $keterangan = preg_replace('/^\d{2}:\d{2}\s*/', '', trim($keterangan));

        $karyawan = Karyawan::where('nama', 'LIKE', "%$nama%")->first();

        if (!$karyawan) continue;

        // ===============================
        // 🔥 LOGIKA TERLAMBAT
        // ===============================
        $jamMasuk = $jam;
        $batasMasuk = '08:15';

        $statusMasuk = (strtotime($jamMasuk) > strtotime($batasMasuk))
            ? 'Terlambat'
            : 'Tepat Waktu';

        // ===============================
        // 🔥 LOGIKA JAM KELUAR
        // ===============================
        $jamKeluar = null;

        $jabatanDefault17 = [
            'Supervisor',
            'Sales',
            'Pengiriman',
            'Helper Pengiriman',
            'Driver'
        ];

        if ($karyawan->tipe_jam_keluar == 'terbatas') {

            $jamKeluar = $karyawan->jam_keluar;

        } else {

            if (in_array($karyawan->jabatan, $jabatanDefault17)) {
                $jamKeluar = '17:00';
            }
        }

        // ===============================
        // SIMPAN DATA
        // ===============================
        Presensi::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'tanggal' => $tanggal,
            ],
            [
                'jam_masuk' => $jamMasuk,
                'jam_keluar' => $jamKeluar,
                'status' => $statusMasuk,
                'keterangan' => $keterangan
            ]
        );
        
    }
}
        // ✏️ FORM EDIT
    public function edit(Presensi $presensi)
    {
        return view('presensi.edit', compact('presensi'));
    }

    // 🔄 UPDATE
    public function update(Request $request, Presensi $presensi)
    {
        $request->validate([
            'jam_masuk' => 'required',
            'keterangan' => 'nullable|string'
        ]);

        $jamMasuk = $request->jam_masuk;

        // Jam kerja normal = 08:00
        if ($jamMasuk > '08:15:00') {
            $status = 'Terlambat';
        } else {
            $status = 'Hadir';
        }

        $presensi->update([
            'jam_masuk' => $jamMasuk,
            'status' => $status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->route('presensi.index')
            ->with('success', 'Data Presensi Berhasil Diedit!');
    }

    // ❌ DELETE
    public function destroy(Presensi $presensi)
    {
        $presensi->delete();

        return redirect()->route('presensi.index')
                         ->with('success', 'Data Presensi Berhasil Dihapus!');
    }
}