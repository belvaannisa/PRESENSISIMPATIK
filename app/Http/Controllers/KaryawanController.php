<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    // 🔍 LIST + SEARCH + PAGINATION
    public function index(Request $request)
    {
        $search = $request->search;

        $karyawans = Karyawan::when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%$search%")
                      ->orWhere('jabatan', 'like', "%$search%");
                });
            })
            ->latest()
            ->paginate(10);

        $no = ($karyawans->currentPage() - 1) * $karyawans->perPage() + 1;

        return view('karyawan.index', compact('karyawans', 'search', 'no'));
    }

    // 📝 FORM CREATE
    public function create()
    {
        $jabatanList = Karyawan::$jabatanList;
        return view('karyawan.create', compact('jabatanList'));
    }

    // 💾 SIMPAN DATA
    public function store(Request $request)
    {
        $request->validate([
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

        Karyawan::create($request->only([
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
                         ->with('success', 'Data karyawan berhasil ditambahkan');
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
            'nama' => 'required|string|max:255',
            'jabatan' => 'required',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'email' => 'nullable|email|unique:karyawans,email,' . $karyawan->id,
            'tanggal_masuk' => 'nullable|date',
            'status_aktif' => 'nullable|boolean',
        ]);

        $karyawan->update($request->only([
            'nama',
            'jabatan',
            'no_hp',
            'alamat',
            'email',
            'tanggal_masuk',
            'status_aktif'
        ]));

        return redirect()->route('karyawan.index')
                         ->with('success', 'Data karyawan berhasil diupdate');
    }

    // ❌ DELETE
    public function destroy(Karyawan $karyawan)
    {
        $karyawan->delete();

        return redirect()->route('karyawan.index')
                         ->with('success', 'Data karyawan berhasil dihapus');
    }
}