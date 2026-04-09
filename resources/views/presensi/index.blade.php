@extends('layouts.app')

@section('content')
<div class="container mt-4">

    {{-- WRAPPER UTAMA --}}
    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Presensi</h5>

            <a href="{{ route('presensi.create') }}" 
               class="btn text-dark btn-sm"
               style="background-color: #FEECC8;">
                ➕ Tambah
            </a>
        </div>

        {{-- BODY --}}
        <div class="card-body">

            {{-- SEARCH --}}
            <div class="d-flex justify-content-end mb-3">
                <form action="{{ route('presensi.index') }}" method="GET" class="d-flex">
                    <input type="text" 
                           name="search" 
                           class="form-control me-2" 
                           placeholder="Cari presensi..." 
                           value="{{ $search }}">
                    <button class="btn btn-outline-secondary">Cari</button>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center text-white" style="background-color: #FA713F;">
                        <tr>
                            <th>No</th>
                            <th>ID Karyawan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($presensis as $p)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $p->karyawan_id }}</td>
                            <td>{{ $p->tanggal }}</td>
                            <td>{{ $p->jam_masuk }}</td>
                            <td>{{ $p->jam_keluar }}</td>
                            <td>{{ $p->status }}</td>
                            <td>{{ $p->keterangan }}</td>
                            <td class="text-center">

                                {{-- Edit --}}
                                <a href="{{ route('presensi.edit', $p->id) }}" 
                                   class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                {{-- Hapus --}}
                                <form action="{{ route('presensi.destroy', $p->id) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm"
                                            onclick="return confirm('Yakin hapus data?')">
                                        Hapus
                                    </button>
                                </form>

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Data presensi belum tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $presensis->links() }}
            </div>

        </div>
    </div>

</div>
@endsection