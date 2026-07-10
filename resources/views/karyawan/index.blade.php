@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ONLY ================= */
@media (max-width: 768px) {

    .container {
        padding: 10px !important;
    }

    /* SEMBUNYIKAN TABLE */
    .table-responsive {
        display: none;
    }

    /* CARD MOBILE */
    .karyawan-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .karyawan-card h6 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .karyawan-card small {
        color: #888;
    }

    .karyawan-card .row {
        font-size: 14px;
    }

    .karyawan-card .btn {
        width: 48%;
    }
}

/* DESKTOP ONLY (card disembunyikan) */
@media (min-width: 769px) {
    .karyawan-mobile {
        display: none;
    }
}

</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Karyawan</h5>

            @if(auth()->user()->role != 'pimpinan')
           <a href="{{ route('karyawan.tambah') }}"
                class="btn text-dark btn-sm"
                style="background-color: #FEECC8;">
                    ➕ Tambah
            </a>
            @endif

        </div>

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

            {{-- ================= DESKTOP TABLE ================= --}}
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
                            <th width="180">Aksi</th>
  
                        </tr>
                    </thead>
                   <tbody>
                        @forelse ($karyawans as $k)
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->jabatan }}</td>
                            <td>{{ $k->no_hp }}</td>
                            <td>{{ $k->alamat }}</td>
                            <td>{{ $k->email }}</td>
                            <td>{{ $k->tanggal_masuk ? $k->tanggal_masuk->format('d-m-Y') : '-' }}</td>

                            <td class="text-center">
                                @if($k->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                           <td class="text-center">

                                {{-- Detail tampil untuk semua role --}}
                                <a href="{{ route('karyawan.detail', $k->id) }}"
                                    class="btn btn-info btn-sm"
                                    title="Detail">
                                    <i class="bi bi-eye-fill"></i>
                                </a>

                                {{-- Edit & Hapus hanya untuk Kepala Personalia --}}
                                @if(auth()->user()->role != 'pimpinan')

                                    <a href="{{ route('karyawan.edit', $k->id) }}"
                                        class="btn btn-warning btn-sm"
                                        title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('karyawan.destroy', $k->id) }}"
                                        method="POST"
                                        class="d-inline">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Hapus"
                                            onclick="return confirm('Yakin ingin menghapus data karyawan ini?')">

                                            <i class="bi bi-trash-fill"></i>

                                        </button>

                                    </form>

                                @endif

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                Data Karyawan Belum Tersedia
                            </td>
                        </tr>
                        @endforelse
                        </tbody>
                </table>
            </div>

            {{-- ================= MOBILE CARD ================= --}}
            <div class="karyawan-mobile">
                @forelse ($karyawans as $k)
                <div class="karyawan-card">

                    <h6>{{ $k->nama }}</h6>
                    <small>{{ $k->jabatan }}</small>

                    <div class="mt-2">
                        📞 {{ $k->no_hp }} <br>
                        📧 {{ $k->email }} <br>
                        📍 {{ $k->alamat }}
                    </div>

                    <div class="mt-2">
                        <strong>Tanggal Masuk:</strong><br>
                        {{ $k->tanggal_masuk ? $k->tanggal_masuk->format('d-m-Y') : '-' }}
                    </div>

                   <div class="mt-2">
                        <strong>Status:</strong><br>
                        @if($k->status_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mt-3">

                    <a href="{{ route('karyawan.detail', $k->id) }}"
                        class="btn btn-info btn-sm">
                        <i class="bi bi-eye-fill"></i>
                    </a>

                    @if(auth()->user()->role != 'pimpinan')

                        <a href="{{ route('karyawan.edit', $k->id) }}"
                            class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <form action="{{ route('karyawan.destroy', $k->id) }}"
                            method="POST"
                            class="d-inline">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="btn btn-danger btn-sm">
                                <i class="bi bi-trash-fill"></i>
                            </button>

                        </form>

                    @endif

                </div>
                @empty
                <div class="text-center text-muted">
                    Data karyawan Belum Tersedia
                </div>
                @endforelse
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 text-center">
                <div class="d-flex justify-content-center">
                    {{ $karyawans->appends(['search' => request('search')])->links() }}
                </div>

            </div>
        </div>
    </div>

</div>
@endsection