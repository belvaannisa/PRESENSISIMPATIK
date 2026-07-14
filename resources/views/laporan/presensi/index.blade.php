@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ================= */
@media (max-width:768px){
    .container{
        padding:10px !important;
    }
    .table-mobile{
        display:none;
    }
    .card-mobile{
        display:block;
    }
    .card-item{
        border:1px solid #ddd;
        border-radius:10px;
        padding:12px;
        margin-bottom:10px;
        background:#fff;
    }
    .card-item h6{
        font-size:14px;
        margin-bottom:5px;
    }
    .card-item p{
        font-size:13px;
        margin-bottom:3px;
    }
}
/* ================= DESKTOP ================= */
@media(min-width:769px){
    .card-mobile{
        display:none;
    }
}
</style>

<div class="container mt-4">

<div class="card shadow-sm">

<div class="card-header text-white" style="background:#FA713F;">
    <h5 class="mb-0">Laporan Presensi Bulanan</h5>
</div>

<div class="card-body">

{{-- FILTER HANYA UNTUK BULANAN --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" class="row g-2 flex-grow-1">
        {{-- Sembunyikan input mode agar controller tetap membaca ini sebagai request bulanan --}}
        <input type="hidden" name="mode" value="bulanan">
        
        <div class="col-md-4">
            <input type="month" name="bulan" value="{{ request('bulan', date('Y-m')) }}" class="form-control" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100">Filter</button>
        </div>
    </form>
    <div>
        <a href="{{ route('laporan.presensi.exportPdf', request()->all() + ['mode' => 'bulanan']) }}" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf-fill"></i> Print PDF
        </a>
    </div>
</div>

{{-- ================= REKAP PRESENSI STAFF ================= --}}

<div class="table-responsive table-mobile">
    <h5 class="mb-3 d-flex align-items-center gap-2">
        Rekap Presensi Staff
        @if(isset($startDate) && isset($endDate))
            <span class="badge bg-secondary" style="font-size: 0.8rem; font-weight: normal;">
                {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
            </span>
        @endif
    </h5>
    <table class="table table-bordered table-striped">
        <thead class="text-center" style="background:#FA713F;color:white;">
            <tr>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Ketidakhadiran</th>
                <th>Keterangan</th>
                <th>Persentase</th>
                <th>Insentif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapStaff ?? [] as $r)
            <tr>
                <td>{{ $r['nama'] }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center text-danger">{{ $r['telat'] }}</td>
                <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
                <td class="text-center">
                    @if($r['keterangan']=='Disiplin')
                        <span class="badge bg-success">Disiplin</span>
                    @else
                        <span class="badge bg-danger">Kurang Disiplin</span>
                    @endif
                </td>
                <td class="text-center">{{ $r['persen'] }}%</td>
                <td class="text-end pe-3 text-success fw-bold">
                    Rp {{ number_format($r['insentif'],0,',','.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak Ada Data Staff</td>
            </tr>
            @endforelse
        </tbody>
    </table>

{{-- ================= REKAP PRESENSI NON STAFF ================= --}}

    <h5 class="mt-5 mb-3 d-flex align-items-center gap-2">
        Rekap Presensi Non Staff
        @if(isset($startDate) && isset($endDate))
            <span class="badge bg-secondary" style="font-size: 0.8rem; font-weight: normal;">
                {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
            </span>
        @endif
    </h5>
    <table class="table table-bordered table-striped">
        <thead class="text-center" style="background:#FA713F;color:white;">
            <tr>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Ketidakhadiran</th>
                <th>Keterangan</th>
                <th>Persentase</th>
                <th>Insentif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapNonStaff ?? [] as $r)
            <tr>
                <td>{{ $r['nama'] }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center text-danger">{{ $r['telat'] }}</td>
                <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
                <td class="text-center">
                    @if($r['keterangan']=='Disiplin')
                        <span class="badge bg-success">Disiplin</span>
                    @else
                        <span class="badge bg-danger">Kurang Disiplin</span>
                    @endif
                </td>
                <td class="text-center">{{ $r['persen'] }}%</td>
               <td class="text-end pe-3 text-success fw-bold">
                    Rp {{ number_format($r['insentif'],0,',','.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak Ada Data Non Staff</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div> {{-- table-responsive --}}

{{-- ================= MOBILE ================= --}}
<div class="card-mobile">
    {{-- STAFF --}}
    <h6 class="mb-3">
        Rekap Presensi Staff
        @if(isset($startDate) && isset($endDate))
            <br>
            <small class="text-muted">{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</small>
        @endif
    </h6>
    @forelse($rekapStaff ?? [] as $r)
    <div class="card-item">
        <h6>{{ $r['nama'] }}</h6>
        <p>Hadir : <strong>{{ $r['hadir'] }}</strong></p>
        <p>Terlambat : <span class="badge bg-danger">{{ $r['telat'] }}</span></p>
        <p>Ketidakhadiran : {{ $r['ketidakhadiran'] }}</p>
        <p>Keterangan :
            @if($r['keterangan'] == 'Disiplin')
                <span class="badge bg-success">Disiplin</span>
            @else
                <span class="badge bg-danger">Kurang Disiplin</span>
            @endif
        </p>
        <p>Persentase :
            @if($r['persen'] == '-')
                -
            @else
                <span class="badge bg-primary">{{ $r['persen'] }}%</span>
            @endif
        </p>
        <p>Insentif : <span class="badge bg-success">Rp {{ number_format($r['insentif'],0,',','.') }}</span></p>
    </div>
    @empty
    <div class="text-center text-muted">Tidak ada data Staff</div>
    @endforelse

    <hr class="my-4">

    {{-- NON STAFF --}}
    <h6 class="mb-3">
        Rekap Presensi Non Staff
        @if(isset($startDate) && isset($endDate))
            <br>
            <small class="text-muted">{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</small>
        @endif
    </h6>
    @forelse($rekapNonStaff ?? [] as $r)
    <div class="card-item">
        <h6>{{ $r['nama'] }}</h6>
        <p>Hadir : <strong>{{ $r['hadir'] }}</strong></p>
        <p>Terlambat : <span class="badge bg-danger">{{ $r['telat'] }}</span></p>
        <p>Ketidakhadiran : {{ $r['ketidakhadiran'] }}</p>
        <p>Keterangan :
            @if($r['keterangan'] == 'Disiplin')
                <span class="badge bg-success">Disiplin</span>
            @else
                <span class="badge bg-danger">Kurang Disiplin</span>
            @endif
        </p>
        <p>Persentase :
            @if($r['persen'] == '-')
                -
            @else
                <span class="badge bg-primary">{{ $r['persen'] }}%</span>
            @endif
        </p>
        <p>Insentif : <span class="badge bg-success">Rp {{ number_format($r['insentif'],0,',','.') }}</span></p>
    </div>
    @empty
    <div class="text-center text-muted">Tidak ada data Non Staff</div>
    @endforelse
</div>

{{-- Grafik --}}
<div class="mt-4">
    <canvas id="chartPresensi"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let hadir = {{ collect($rekapStaff ?? [])->sum('hadir') }} + {{ collect($rekapNonStaff ?? [])->sum('hadir') }};
let telat = {{ collect($rekapStaff ?? [])->sum('telat') }} + {{ collect($rekapNonStaff ?? [])->sum('telat') }};
let ketidakhadiran = {{ collect($rekapStaff ?? [])->sum('ketidakhadiran') }} + {{ collect($rekapNonStaff ?? [])->sum('ketidakhadiran') }};

new Chart(document.getElementById('chartPresensi'), {
    type: 'bar',
    data: {
        labels: ['Hadir', 'Terlambat', 'Ketidakhadiran'],
        datasets: [{
            label: 'Statistik Presensi',
            backgroundColor: ['#28a745', '#dc3545', '#6c757d'],
            data: [hadir, telat, ketidakhadiran]
        }]
    }
});
</script>

</div>
</div>
</div>

@endsection