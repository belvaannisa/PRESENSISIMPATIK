<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\Presensi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class KaryawanController extends Controller
{
    private function getTipeJamKeluar(string $jabatan): string
    {
        return in_array(
            $jabatan,
            config('jabatan.jabatan_tidak_terbatas')
        )
            ? config('jabatan.tidak_terbatas')
            : config('jabatan.terbatas');
    }

    private function getJamKeluar(?string $jamKeluar, string $tipeJamKeluar): ?string
    {
        return $jamKeluar ?: config('jabatan.jam_keluar_default');
    }

    // 🔍 LIST + SEARCH + PAGINATION
   public function index(Request $request)
{
    $search = $request->search;

    // Daftar jabatan Staff
    $staffJabatan = [
        'KEPALA CABANG',
        'HAF',
        'KEPALA GUDANG & KEPALA PERSONALIA',
        'KASIR',
        'KOORD AR',
        'ADM AR',
        'KORWIL BJB',
        'COLLECTOR',
        'KANVAS DRIVER',
        'DRIVER GUDANG',
        'HELPER',
        'OFFICE BOY CABANG'
    ];

    // Daftar jabatan Non Staff
    $nonStaffJabatan = [
        'SPV SR BJB',
        'SPV SF BERLIAN',
        'SR BJB',
        'SF BERLIAN'
    ];

    // =========================
    // Data Staff
    // =========================
    $staff = Karyawan::whereIn('jabatan', $staffJabatan)
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        })
        ->orderBy('id', 'asc')
        ->paginate(10, ['*'], 'staff_page');

    // =========================
    // Data Non Staff
    // =========================
    $nonStaff = Karyawan::whereIn('jabatan', $nonStaffJabatan)
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        })
        ->orderBy('id', 'asc')
        ->paginate(10, ['*'], 'nonstaff_page');

    // Nomor urut Staff
    $noStaff = ($staff->currentPage() - 1) * $staff->perPage() + 1;

    // Nomor urut Non Staff
    $noNonStaff = ($nonStaff->currentPage() - 1) * $nonStaff->perPage() + 1;

    return view('karyawan.index', compact(
        'staff',
        'nonStaff',
        'search',
        'noStaff',
        'noNonStaff'
    ));
}

    // 📝 FORM CREATE
    public function create()
    {
        $jabatanList = Karyawan::$jabatanList;
        return view('karyawan.tambah', compact('jabatanList'));
    }

    // 💾 SIMPAN DATA
public function store(Request $request)
{
    $request->validate([
        'pin'             => 'nullable|string|unique:karyawans,pin',
        'nama'            => 'required|string|max:255',
        'jabatan'         => 'required|in:' . implode(',', Karyawan::$jabatanList),
        'no_hp'           => 'nullable|string|max:20',
        'tanggal_masuk'   => 'nullable|date',
        'status_aktif'    => 'nullable|boolean',
        'jam_keluar'      => 'nullable|date_format:H:i',
    ]);

    $tipeJamKeluar = $this->getTipeJamKeluar($request->jabatan);

    $jamKeluar = $tipeJamKeluar == config('jabatan.tidak_terbatas')
        ? null
        : ($request->jam_keluar ?: config('jabatan.jam_keluar_default'));

    Karyawan::create([

        'pin'                 => $request->pin,
        'nama'                => strtoupper($request->nama),
        'jabatan'             => $request->jabatan,
        'no_hp'               => $request->no_hp,
        'tanggal_masuk'       => $request->tanggal_masuk,

        'jam_masuk'           => config('jabatan.jam_masuk_default'),
        'jam_keluar'          => $jamKeluar,
        'tipe_jam_keluar'     => $tipeJamKeluar,

        'status_aktif'        => $request->boolean('status_aktif'),

        'sinkron_fingerprint' => true,
    ]);

    $lastPage = ceil(Karyawan::count() / 10);

    return redirect()
        ->route('karyawan.index', ['page' => $lastPage])
        ->with('success', 'Data Karyawan Berhasil Ditambahkan!');
}


    // 👁️ DETAIL
    public function show(Karyawan $karyawan)
    {
        return view('karyawan.show', compact('karyawan'));
    }

    // ✏️ FORM EDIT
    public function edit(Karyawan $karyawan)
    {
        $jabatanList = Karyawan::$jabatanList;
        return view('karyawan.edit', compact('karyawan', 'jabatanList'));
    }

    // 🔄 UPDATE
    public function update(Request $request, Karyawan $karyawan)
{

    $request->validate([
        'pin' => 'nullable|string|unique:karyawans,pin,' . $karyawan->id,
        'nama' => 'required|string|max:255',
        'jabatan' => 'required',
        'no_hp' => 'nullable|string|max:20',
        'tanggal_masuk' => 'nullable|date',
        'status_aktif' => 'required|boolean',
        'jam_keluar' => 'nullable',
    ]);

    $tipeJamKeluar = $this->getTipeJamKeluar($request->jabatan);

    $jamKeluar = $this->getJamKeluar(
        $request->jam_keluar,
        $tipeJamKeluar
    );

    $karyawan->update([

        'pin' => $request->pin,
        'nama' => $request->nama,
        'jabatan' => $request->jabatan,
        'no_hp' => $request->no_hp,
        'tanggal_masuk' => $request->tanggal_masuk,
        'status_aktif' => $request->has('status_aktif')
            ? $request->status_aktif
            : $karyawan->status_aktif,
        'tipe_jam_keluar' => $tipeJamKeluar,
        'jam_keluar'      => $jamKeluar,

    ]);

    return redirect()
        ->route('karyawan.index')
        ->with('success', 'Data Karyawan Berhasil Diedit!');
}

    // ❌ DELETE
    public function destroy(Karyawan $karyawan)
    {
        $karyawan->delete();

        return redirect()->route('karyawan.index')
                         ->with('success', 'Data Karyawan Berhasil Dihapus!');
    }

    public function detail($id)
{
    $karyawan = Karyawan::findOrFail($id);

    $presensis = Presensi::where('karyawan_id', $id)
        ->orderBy('tanggal', 'desc')
        ->orderBy('jam_masuk', 'desc')
        ->paginate(10);

    return view('karyawan.detail', compact(
        'karyawan',
        'presensis'
    ));
}

   public function exportDetailPdf($id)
{
    $karyawan = Karyawan::findOrFail($id);

    $presensis = Presensi::where('karyawan_id', $id)
        ->orderBy('tanggal', 'desc')
        ->orderBy('jam_masuk', 'desc')
        ->get();

    $user = Auth::user();

    $pdf = Pdf::loadView('karyawan.pdf', compact(
        'karyawan',
        'presensis',
        'user'
    ))->setPaper('a4', 'landscape');

    return $pdf->download(
        'detail-presensi-' . $karyawan->nama . '.pdf'
    );
}

}