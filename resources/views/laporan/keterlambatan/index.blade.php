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

/* ================= DESKTOP ONLY ================= */
@media (min-width: 769px) {
    .card-mobile {
        display: none;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm">

        {{-- HEADER --}}
        <div class="card-header text-white" style="background: #FA713F;">
            <h5 class="mb-0">Laporan Keterlambatan</h5>
        </div>

        {{-- BODY --}}
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    <form method="GET" class="d-flex gap-2">
        <input type="month"
               name="bulan"
               value="{{ request('bulan', $bulan ?? now()->format('Y-m')) }}"
               class="form-control">

        <button class="btn btn-dark">
            Filter
        </button>
    </form>

    <a href="{{ route('laporan.keterlambatan.exportPdf', request()->all()) }}"
       class="btn btn-danger">
        🖨 Print PDF
    </a>

</div>
        <div class="card-body">

            {{-- ================= DESKTOP TABLE ================= --}}
            <div class="table-responsive table-mobile">
                <table class="table table-bordered table-striped">
                    <thead class="text-center" class="text-white" style="background:#dc3545;">
                        <tr>
                            <th>Nama</th>
                            <th>Terlambat</th>
                            <th>Hadir</th>
                            <th>Persen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                        <tr>
                            <td>{{ $d['nama'] }}</td>
                            <td class="text-center" class="text-danger fw-bold">{{ $d['telat'] }}</td>
                            <td class="text-center">{{ $d['hadir'] }}</td>
                            
                            <td class= "text-center">
                                <span class="badge bg-danger">
                                    {{ $d['persen_telat'] }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak Ada Data</td>
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

                    <p>
                        Terlambat: 
                        <span class="badge bg-danger">
                            {{ $d['telat'] }}
                        </span>
                    </p>

                    <p>
                        Hadir: 
                        <strong>{{ $d['hadir'] }}</strong>
                    </p>

                    <p>
                        Persen: 
                        <span class="badge bg-danger">
                            {{ $d['persen_telat'] }}%
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