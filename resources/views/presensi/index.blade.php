@extends('layouts.app')

@section('content')
<div class="container mt-4">

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Presensi</h5>

            <form action="{{ route('presensi.import') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    ⬆ Auto Import
                </button>
            </form>
        </div>

        <div class="card-body">

            {{-- MANUAL UPLOAD --}}
            <form action="{{ route('presensi.upload') }}" 
                  method="POST" 
                  enctype="multipart/form-data"
                  class="mb-3 d-flex flex-column flex-lg-row gap-2">
                @csrf
                <input type="file" name="file" class="form-control" required>
                <button class="btn btn-primary">Upload CSV</button>
            </form>

            {{-- SEARCH --}}
            <div class="mb-3">
                <form action="{{ route('presensi.index') }}" method="GET"
                      class="d-flex flex-column flex-lg-row gap-2 justify-content-end">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Cari nama / tanggal..." 
                           value="{{ $search }}">
                    <button class="btn btn-outline-secondary">Cari</button>
                </form>
            </div>

            {{-- ================= DESKTOP TABLE ================= --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="text-center text-white" style="background-color: #FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($presensis as $p)
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>{{ $p->karyawan->nama ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}</td>
                            <td>{{ $p->jam_masuk ?? '-' }}</td>
                            <td>{{ $p->jam_keluar ?? '-' }}</td>

                            <td class="text-center">
                                @if($p->status == 'Terlambat')
                                    <span class="badge bg-danger">Terlambat</span>
                                @else
                                    <span class="badge bg-success">Tepat Waktu</span>
                                @endif
                            </td>

                            <td class= "text-center">
                                <span class="badge bg-success">{{ $p->keterangan ?? '-' }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Data presensi belum tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= MOBILE CARD ================= --}}
            <div class="d-lg-none">

                @forelse ($presensis as $p)
                <div class="card mb-3 shadow-sm border-0">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">
                            <strong>{{ $p->karyawan->nama ?? '-' }}</strong>
                            <span class="text-muted small">
                                {{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}
                            </span>
                        </div>

                        <hr class="my-2">

                        <div class="row small">
                            <div class="col-6">
                                <strong>Masuk:</strong><br>
                                {{ $p->jam_masuk ?? '-' }}
                            </div>

                            <div class="col-6">
                                <strong>Keluar:</strong><br>
                                {{ $p->jam_keluar ?? '-' }}
                            </div>
                        </div>

                        <div class="mt-2">
                            <strong>Status:</strong><br>
                            @if($p->status == 'Terlambat')
                                <span class="badge bg-danger">Terlambat</span>
                            @else
                                <span class="badge bg-success">Tepat Waktu</span>
                            @endif
                        </div>

                        <div class="mt-2">
                            <strong>Keterangan:</strong><br>
                            <span class="badge bg-success">{{ $p->keterangan ?? '-' }}</span>
                        </div>

                    </div>
                </div>
                @empty
                    <div class="text-center text-muted">
                        Data presensi belum tersedia
                    </div>
                @endforelse

            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $presensis->links() }}
            </div>

        </div>
    </div>

</div>
@endsection