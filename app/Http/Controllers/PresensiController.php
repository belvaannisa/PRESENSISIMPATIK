<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;

class PresensiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
    $search = $request->search;

    $presensis = Presensi::when($search, function ($query, $search) {
        return $query->where('karyawan_id', 'like', "%$search%")
                     ->orWhere('tanggal', 'like', "%$search%")
                     ->orWhere('status', 'like', "%$search%");
    })
    ->latest()
    ->paginate(5); // jumlah per halaman

    return view('presensi.index', compact('presensis', 'search'))
        ->with('no', ($presensis->currentPage() - 1) * $presensis->perPage() + 1);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
