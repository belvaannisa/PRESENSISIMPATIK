@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ONLY ================= */
@media (max-width: 768px) {

    .container {
        padding: 10px !important;
    }

    .table-mobile {
        display: none;
    }

    .card-mobile {
        display: block;
    }

    .card-item {
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .card-item h6 {
        font-size: 14px;
        margin-bottom: 5px;
    }

    .card-item p {
        font-size: 13px;
        margin-bottom: 3px;
    }
}

/* DESKTOP ONLY */
@media (min-width: 769px) {
    .card-mobile {
        display: none;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white" style="background: #FA713F;">
            <h5 class="mb-0">Laporan Kedisiplinan</h5>
        </div>

        {{-- BODY --}}
        <div class="card-body">

            {{-- ================= DESKTOP TABLE ================= --}}
            <div class="table-responsive table-mobile">
                <table class="table table-bordered table-striped">
                    <thead class="text-white" style="background:#0d6efd;">
                        <tr>
                            <th>Nama</th>
                            <th>Hadir</th>
                            <th>Tepat</th>
                            <th>Terlambat</th>
                            <th>Persen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                        <tr>
                            <td>{{ $d['nama'] }}</td>
                            <td>{{ $d['hadir'] }}</td>
                            <td class="text-success fw-bold">{{ $d['tepat'] }}</td>
                            <td class="text-danger">{{ $d['telat'] }}</td>
                            <td class= "text-center">
                                <span class="badge bg-primary">
                                    {{ $d['persen_disiplin'] }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= MOBILE CARD ================= --}}
            <div class="card-mobile">
                @forelse($data as $d)
                <div class="card-item">

                    <h6><strong>{{ $d['nama'] }}</strong></h6>

                    <p>Hadir: <strong>{{ $d['hadir'] }}</strong></p>

                    <p>
                        Tepat: 
                        <span class="badge bg-success">
                            {{ $d['tepat'] }}
                        </span>
                    </p>

                    <p>
                        Terlambat: 
                        <span class="badge bg-danger">
                            {{ $d['telat'] }}
                        </span>
                    </p>

                    <p>
                        Disiplin: 
                        <span class="badge bg-primary">
                            {{ $d['persen_disiplin'] }}%
                        </span>
                    </p>

                </div>
                @empty
                <div class="text-center text-muted">
                    Tidak ada data
                </div>
                @endforelse
            </div>

        </div>
    </div>

</div>
@endsection