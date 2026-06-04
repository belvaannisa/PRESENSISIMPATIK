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
            <div class="row">

                <div class="col-md-4 col-12 mb-3">
                    <div class="card text-white" style="background-color: #FA713F;">
                        <div class="card-body text-center text-md-start">
                            <h6>Total Karyawan</h6>
                            <h3>{{ $totalKaryawan ?? 0 }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="card text-dark" style="background-color: #FEECC8;">
                        <div class="card-body text-center text-md-start">
                            <h6>Total Presensi</h6>
                            <h3>{{ $totalPresensi ?? 0 }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="card text-white" style="background-color: #FA713F;">
                        <div class="card-body text-center text-md-start">
                            <h6>Presensi Hari Ini</h6>
                            <h3>{{ $presensiHariIni ?? 0 }}</h3>
                        </div>
                    </div>
                </div>

            </div>

            {{-- TABEL --}}
            <div class="card mt-3">
                <div class="card-header text-white" style="background-color: #FA713F;">
                    Presensi Terbaru
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
                                <td>{{ $p->tanggal }}</td>
                                <td>{{ $p->jam_masuk ?? '-' }}</td>
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
               {{-- GRAFIK PRESENSI --}}
                <div class="card mt-3">
                    <div class="card-header text-white" style="background-color: #FA713F;">
                        Grafik Presensi
                    </div>

                    <div class="card-body text-center">

                        <canvas id="presensiChart"
                                style="max-width:250px; max-height:250px; margin:auto;">
                        </canvas>

                    </div>
                </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('presensiChart');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Tepat Waktu', 'Terlambat'],
        datasets: [{
            data: [
                {{ $tepatWaktu }},
                {{ $terlambat }}
            ],
            backgroundColor: [
                '#198754',
                '#dc3545'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endsection