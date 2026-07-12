@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ONLY ================= */
@media (max-width: 768px) {

    .container {
        padding: 10px !important;
    }

    .card-body h3 {
        font-size: 22px;
    }

    .card-body h6 {
        font-size: 14px;
    }

    /* Table biar gak pecah */
    .table-responsive-mobile {
        overflow-x: auto;
    }

    table {
        min-width: 500px;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">

        <div class="card-header text-white" style="background-color: #FA713F;">
            <h5 class="mb-0">Dashboard</h5>
        </div>

        <div class="card-body">

            {{-- CARD --}}
           <div class="row {{ Auth::user()->role == 'pimpinan' ? 'justify-content-center' : '' }}">

            {{-- Total Karyawan --}}
            <div class="{{ Auth::user()->role == 'pimpinan' ? 'col-md-5' : 'col-md-4' }} col-12 mb-3">
                <div class="card text-white" style="background-color:#FA713F;">
                    <div class="card-body">
                        <h6>Total Karyawan</h6>
                        <h3>{{ $totalKaryawan }}</h3>
                    </div>
                </div>
            </div>

            {{-- Hanya Kepala Personalia --}}
            @if(in_array(Auth::user()->role, [
                'kepala_personalia',
                'haf'
            ]))
            <div class="col-md-4 col-12 mb-3">
                <div class="card text-dark" style="background-color:#FEECC8;">
                    <div class="card-body">
                        <h6>Total Presensi</h6>
                        <h3>{{ $totalPresensi }}</h3>
                    </div>
                </div>
            </div>
            @endif

            {{-- Presensi Hari Ini --}}
            <div class="{{ Auth::user()->role == 'pimpinan' ? 'col-md-5' : 'col-md-4' }} col-12 mb-3">
                <div class="card text-white" style="background-color:#FA713F;">
                    <div class="card-body">
                        <h6>Presensi Hari Ini</h6>
                        <h3>{{ $presensiHariIni }}</h3>
                    </div>
                </div>
            </div>
        </div>

            {{-- TABEL --}}
            <div class="card mt-3">
                <div class="card-header text-white" style="background-color: #FA713F;">
                    Aktivitas Presensi Terbaru Berdasarkan Aktivitas Presensi Terakhir Yang Berhasil Direkam Sistem:
                </div>

                <div class="card-body table-responsive-mobile">
                    <table class="table table-bordered">
                        <thead class="text-white text-center" style="background-color: #FA713F;">
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($presensi as $p)
                            <tr>
                                <td>{{ $p->karyawan->nama ?? '-' }}</td>
                                <td class="text-center">{{ $p->tanggal ? \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') : '-' }}</td>
                                <td class="text-center">{{ $p->jam_masuk ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Data Belum Tersedia
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>

</div>

@endsection