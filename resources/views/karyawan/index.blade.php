@extends('layouts.app')

@section('content')
<div class="container mt-4">

    {{-- WRAPPER UTAMA --}}
    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Karyawan</h5>

            <a href="{{ route('karyawan.create') }}" 
               class="btn text-dark btn-sm"
               style="background-color: #FEECC8;">
                ➕ Tambah
            </a>
        </div>

        {{-- BODY --}}
        <div class="card-body">

            {{-- SEARCH --}}
            <div class="d-flex justify-content-end mb-3">
                <form action="{{ route('karyawan.index') }}" method="GET" class="d-flex">
                    <input type="text" 
                           name="search" 
                           class="form-control me-2" 
                           placeholder="Cari karyawan..." 
                           value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary">Cari</button>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center text-white" style="background-color: #FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>No. HP</th>
                            <th>Alamat</th>
                            <th>Email</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($karyawans as $k)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->jabatan }}</td>
                            <td>{{ $k->no_hp }}</td>
                            <td>{{ $k->alamat }}</td>
                            <td>{{ $k->email }}</td>
                            
                            {{-- FORMAT TANGGAL --}}
                            <td>
                                {{ $k->tanggal_masuk ? $k->tanggal_masuk->format('d-m-Y') : '-' }}
                            </td>

                            {{-- STATUS BADGE --}}
                            <td class="text-center">
                                @if($k->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td class="text-center">
                                <a href="{{ route('karyawan.edit', $k->id) }}" 
                                   class="btn btn-warning btn-sm">
                                    ✏️
                                </a>

                                <form action="{{ route('karyawan.destroy', $k->id) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm"
                                            onclick="return confirm('Yakin hapus data?')">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                Data karyawan belum tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $karyawans->appends(['search' => request('search')])->links() }}
            </div>

        </div>
    </div>

</div>
@endsection