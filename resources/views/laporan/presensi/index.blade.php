@extends('layouts.app')

@section('content')
@php
    $no = 1;
    // Menggabungkan rekap staff dan non-staff agar tampil dalam satu tabel utama
    $semuaKaryawan = array_merge($rekapStaff, $rekapNonStaff);
@endphp

<style>
    .table-warning {
        background-color: #FFF3CD !important;
    }
    .table-warning:hover {
        background-color: #FFE69C !important;
    }
</style>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="background-color: #FA713F;">
            <h5 class="mb-0">Data Rekap Presensi Bulanan</h5>
            <form action="{{ route('presensi.import') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">⭐ Auto Import</button>
            </form>
        </div>
        
        <div class="card-body">
            {{-- Form Upload --}}
            <form action="{{ route('presensi.upload') }}" method="POST" enctype="multipart/form-data" class="mb-3 d-flex flex-column flex-lg-row gap-2">
                @csrf
                <input type="file" name="file" class="form-control" required>
                <button class="btn btn-primary">Upload CSV</button>
            </form>

            {{-- Form Filter Bulan --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</strong>
                </div>
                <form action="" method="GET" class="d-flex">
                    <input type="month" name="bulan" class="form-control me-2" value="{{ request('bulan', $bulan) }}">
                    <button class="btn btn-outline-secondary">Filter</button>
                </form>
            </div>

            <div class="alert alert-warning py-2 mb-3">
                <strong>Keterangan :</strong> Status Terlambat & Tidak Absen Pagi mengurangi akumulasi nilai kedisiplinan dan perhitungan insentif.
            </div>

            {{-- ================= DESKTOP TABLE ================= --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="text-center text-white" style="background-color: #FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>Hadir</th>
                            <th>Telat / TPA</th>
                            <th>Mangkir</th>
                            <th>Persentase</th>
                            <th>Insentif (Rp)</th>
                            <th>Status Kelayakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($semuaKaryawan as $p)
                            <tr>
                                <td class="text-center">{{ $no++ }}</td>
                                <td><strong>{{ $p['nama'] }}</strong></td>
                                <td class="text-center">{{ $p['hadir'] }} hari</td>
                                <td class="text-center">
                                    @if($p['telat'] > 0)
                                        <span class="badge bg-danger">{{ $p['telat'] }} x</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $p['ketidakhadiran'] }} hari</td>
                                <td class="text-center fw-bold">{{ $p['persen'] }}%</td>
                                <td class="text-end pe-3">{{ number_format($p['insentif'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($p['keterangan'] == 'Disiplin')
                                        <span class="badge bg-success">Disiplin</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Kurang Disiplin</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Data Rekap Belum Tersedia</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= MOBILE CARD ================= --}}
            <div class="d-lg-none">
                @forelse ($semuaKaryawan as $p)
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $p['nama'] }}</strong>
                                @if($p['keterangan'] == 'Disiplin')
                                    <span class="badge bg-success">Disiplin</span>
                                @else
                                    <span class="badge bg-warning text-dark">Kurang Disiplin</span>
                                @endif
                            </div>
                            <hr class="my-2">
                            <div class="row small text-muted">
                                <div class="col-6 mb-2"><strong>Hadir:</strong> {{ $p['hadir'] }} hari</div>
                                <div class="col-6 mb-2"><strong>Telat/TPA:</strong> {{ $p['telat'] }} x</div>
                                <div class="col-6"><strong>Mangkir:</strong> {{ $p['ketidakhadiran'] }} hari</div>
                                <div class="col-6"><strong>Persen:</strong> {{ $p['persen'] }}%</div>
                            </div>
                            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center small">
                                <strong>Estimasi Insentif:</strong>
                                <span class="text-success fw-bold">Rp {{ number_format($p['insentif'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3">Data Rekap Belum Tersedia</div>
                @endforelse
            </div>

            {{-- Navigasi links() sudah dihapus total di sini karena tidak menggunakan pagination --}}

        </div>
    </div>
</div>
@endsection