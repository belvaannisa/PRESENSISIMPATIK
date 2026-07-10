<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Models\Presensi;

class KaryawanController extends Controller
{
    // 🔍 LIST + SEARCH + PAGINATION
   public function index(Request $request)
    {
    $search = $request->search;

    $karyawans = Karyawan::when($search, function ($query) use ($search) {

            $query->where(function ($q) use ($search) {

                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");

            });

        })
        ->orderBy('id', 'asc') 
        ->paginate(10);

    $no = ($karyawans->currentPage() - 1) * $karyawans->perPage() + 1;

    return view('karyawan.index', compact(
        'karyawans',
        'search',
        'no'
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
         'pin' => 'nullable|string|unique:karyawans,pin',
        'nama' => 'required|string|max:255',
        'jabatan' => 'required',
        'no_hp' => 'nullable|string|max:20',
        'alamat' => 'nullable|string',
        'email' => 'nullable|email|unique:karyawans,email',
        'tanggal_masuk' => 'nullable|date',
        'status_aktif' => 'nullable|boolean',
        'tipe_jam_keluar' => 'required',
        'jam_keluar' => 'nullable|required_if:tipe_jam_keluar,terbatas'
    ]);
    
    $jam_keluar = $request->jam_keluar;

    if ($request->tipe_jam_keluar == 'Tidak Terbatas' || empty($jam_keluar)) {
        $jam_keluar = '00:00:00'; 
    }
    
    Karyawan::create([
        'pin' => $request->pin,
        'nama' => $request->nama,
        'jabatan' => $request->jabatan,
        'no_hp' => $request->no_hp,
        'alamat' => $request->alamat,
        'email' => $request->email,
        'tanggal_masuk' => $request->tanggal_masuk,
        'status_aktif' => $request->status_aktif,
        'tipe_jam_keluar' => $request->tipe_jam_keluar,
        'jam_keluar' => $jam_keluar
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
            'alamat' => 'nullable|string',
            'email' => 'nullable|email|unique:karyawans,email,' . $karyawan->id,
            'tanggal_masuk' => 'nullable|date',
            'status_aktif' => 'nullable|boolean',
        ]);

            $karyawan->update($request->only([
            'pin',
            'nama',
            'jabatan',
            'no_hp',
            'alamat',
            'email',
            'tanggal_masuk',
            'status_aktif',
            'tipe_jam_keluar',
            'jam_keluar'
        ]));

        return redirect()->route('karyawan.index')
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
            ->get();

        return view('karyawan.detail', compact(
            'karyawan',
            'presensis'
        ));
    }
}