@php
\Carbon\Carbon::setLocale('id');
@endphp

@extends('layouts.app')

@section('content')

<style>
/* MOBILE */
@media (max-width:768px){

    .desktop-table{
        display:none;
    }

    .mobile-presensi{
        display:block;
    }

    .presensi-card{
        border:1px solid #e5e5e5;
        border-radius:12px;
        padding:12px;
        margin-bottom:12px;
        background:#fff;
        box-shadow:0 2px 6px rgba(0,0,0,.05);
    }

    .presensi-card h6{
        margin-bottom:8px;
        font-weight:600;
    }

    .presensi-card p{
        margin-bottom:4px;
        font-size:14px;
    }
}

/* DESKTOP */
@media (min-width:769px){

    .mobile-presensi{
        display:none;
    }

    .desktop-table{
        display:block;
    }

}
</style>

<div class="container mt-4">

    <div class="card shadow">

        <div class="card-header text-white"
             style="background:#FA713F;">

            <h4 class="mb-0">
                {{ $karyawan->nama }}
            </h4>

            <small>Riwayat Presensi Karyawan</small>

        </div>
        
        <div class="card-body">

            <div class="table-responsive desktop-table">
                <div class="d-flex justify-content-end mb-3">
                    <a href="{{ route('karyawan.pdf',$karyawan->id) }}"
                class="btn btn-danger btn-sm">

                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    PDF
                    </a>
                </div>
                <table class="table table-bordered table-striped">

                    <thead class="text-center text-white"
                           style="background:#FA713F;">

                        <tr>
                            <th width="60">No</th>
                            <th>Hari</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse($presensis as $presensi)

                    <tr>

                        <td class="text-center">
                            {{ ($presensis->currentPage() - 1) * $presensis->perPage() + $loop->iteration }}
                        </td>

                        {{-- Hari --}}
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l') }}
                        </td>

                        {{-- Tanggal --}}
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}
                        </td>

                        {{-- Jam Masuk --}}
                        <td class="text-center">
                            {{ $presensi->jam_masuk ?: '-' }}
                        </td>

                        {{-- Jam Keluar --}}
                        <td class="text-center">
                            {{ $presensi->jam_keluar ?: '-' }}
                        </td>

                        {{-- Status --}}
                        <td class="text-center">

                            @if($presensi->status == 'Tepat Waktu')

                                <span class="badge bg-success">
                                    Tepat Waktu
                                </span>

                            @elseif($presensi->status == 'Terlambat')

                                <span class="badge bg-warning">
                                    Terlambat
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Tidak Hadir
                                </span>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Belum Ada Data Presensi.
                        </td>
                    </tr>

                    @endforelse

                    </tbody>

                </table>

                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $presensis->links() }}
                </div>
                
                <div class="mobile-presensi">

                {{-- ================= MOBILE CARD ================= --}}

                @forelse($presensis as $presensi)

                <div class="presensi-card">

                    <h6>
                        {{ ($presensis->currentPage()-1)*$presensis->perPage()+$loop->iteration }}
                        . {{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l') }}
                    </h6>

                    <p>
                        <strong>Tanggal :</strong>
                        {{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}
                    </p>

                    <p>
                        <strong>Jam Masuk :</strong>
                        {{ $presensi->jam_masuk ?: '-' }}
                    </p>

                    <p>
                        <strong>Jam Keluar :</strong>
                        {{ $presensi->jam_keluar ?: '-' }}
                    </p>

                    <p>
                        <strong>Status :</strong>

                        @if($presensi->status == 'Tepat Waktu')

                            <span class="badge bg-success">
                                Tepat Waktu
                            </span>

                        @elseif($presensi->status == 'Terlambat')

                            <span class="badge bg-warning text-dark">
                                Terlambat
                            </span>

                        @else

                            <span class="badge bg-danger">
                                Tidak Hadir
                            </span>

                        @endif

                    </p>

                </div>

                @empty

                <div class="text-center text-muted">
                    Belum Ada Data Presensi.
                </div>

                @endforelse

                </div>

                <a href="{{ route('karyawan.index') }}"
                class="btn btn-secondary mt-3">

                    Kembali

                </a>
        </div>

    </div>

</div>

@endsection