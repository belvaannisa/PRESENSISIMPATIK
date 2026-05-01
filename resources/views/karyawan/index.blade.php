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

            <a href="{{ route('karyawan.create') }}" 
               class="btn text-dark btn-sm"
               style="background-color: #FEECC8;">
                ➕ Tambah
            </a>
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
                            <td>{{ $k->tanggal_masuk ? $k->tanggal_masuk->format('d-m-Y') : '-' }}</td>

                            <td class="text-center">
                                @if($k->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <a href="{{ route('karyawan.edit', $k->id) }}" 
                                   class="btn btn-warning btn-sm">✏️</a>

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

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('karyawan.edit', $k->id) }}" 
                        class="btn btn-warning btn-sm w-10">
                            ✏️
                        </a>

                        <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" class="flex-fill">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm w-100"
                                    onclick="return confirm('Yakin hapus data?')">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted">
                    Data karyawan belum tersedia
                </div>
                @endforelse
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $karyawans->appends(['search' => request('search')])->links() }}
            </div>

        </div>
    </div>

</div>
@endsection