@extends('layouts.app')

@section('content')

<style>
@media (max-width:768px){
    .container{ padding:10px !important; }
    .table-mobile{ display:none; }
    .card-mobile{ display:block; }
    .card-item{ border:1px solid #ddd; border-radius:10px; padding:12px; margin-bottom:10px; background:#fff; }
    .card-item h6{ font-size:14px; margin-bottom:5px; }
    .card-item p{ font-size:13px; margin-bottom:3px; }
}
@media(min-width:769px){
    .card-mobile{ display:none; }
}
</style>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header text-white" style="background:#FA713F;">
            <h5 class="mb-0">Laporan Presensi (Rekapitulasi)</h5>
        </div>

        <div class="card-body">

            {{-- FILTER --}}
            <div class="mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mode Laporan</label>
                        <select name="mode" id="modeSelect" class="form-select">
                            <option value="bulanan" {{ request('mode') == 'bulanan' ? 'selected' : '' }}>Bulanan (Default 26 ke 25)</option>
                            <option value="custom" {{ request('mode') == 'custom' ? 'selected' : '' }}>Rentang Waktu (Custom)</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="bulanField">
                        <label class="form-label fw-bold">Pilih Bulan</label>
                        <input type="month" name="bulan" value="{{ request('bulan', now()->format('Y-m')) }}" class="form-control">
                    </div>

                    <div class="col-md-3" id="startDateField" style="display:none;">
                        <label class="form-label fw-bold">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="form-control">
                    </div>

                    <div class="col-md-3" id="endDateField" style="display:none;">
                        <label class="form-label fw-bold">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="form-control">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark w-100">Filter</button>
                        <a href="{{ route('laporan.presensi.exportPdf', request()->all()) }}" class="btn btn-danger w-100">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive table-mobile">
                {{-- STAFF --}}
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
                            <th>Terlambat / Tdk Absen Pagi</th>
                            <th>Ketidakhadiran</th>
                            <th>Keterangan</th>
                            <th>Persentase</th>
                            <th>Insentif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapStaff as $r)
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
                            <td class="text-end pe-3 text-success fw-bold">Rp {{ number_format($r['insentif'],0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">Tidak Ada Data Staff</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- NON STAFF --}}
                <h5 class="mt-5 mb-3 d-flex align-items-center gap-2">
                    Rekap Presensi Non Staff
                </h5>
                <table class="table table-bordered table-striped">
                    <thead class="text-center" style="background:#FA713F;color:white;">
                        <tr>
                            <th>Nama</th>
                            <th>Hadir</th>
                            <th>Terlambat / Tdk Absen Pagi</th>
                            <th>Ketidakhadiran</th>
                            <th>Keterangan</th>
                            <th>Persentase</th>
                            <th>Insentif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapNonStaff as $r)
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
                            <td class="text-end pe-3 text-success fw-bold">Rp {{ number_format($r['insentif'],0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">Tidak Ada Data Non Staff</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE BULANAN/CUSTOM --}}
            <div class="card-mobile">
                <h6 class="mb-3">Rekap Presensi Staff</h6>
                @forelse($rekapStaff as $r)
                <div class="card-item">
                    <h6>{{ $r['nama'] }}</h6>
                    <p>Hadir: <strong>{{ $r['hadir'] }}</strong> | Telat/Tdk Absen Pagi: <span class="badge bg-danger">{{ $r['telat'] }}</span></p>
                    <p>Alpa: {{ $r['ketidakhadiran'] }} | Persentase: <span class="badge bg-primary">{{ $r['persen'] }}%</span></p>
                    <p>Status: @if($r['keterangan'] == 'Disiplin') <span class="badge bg-success">Disiplin</span> @else <span class="badge bg-danger">Kurang Disiplin</span> @endif </p>
                    <p>Insentif: <span class="badge bg-success">Rp {{ number_format($r['insentif'],0,',','.') }}</span></p>
                </div>
                @empty
                <div class="text-center text-muted">Tidak ada data</div>
                @endforelse

                <hr class="my-4">

                <h6 class="mb-3">Rekap Presensi Non Staff</h6>
                @forelse($rekapNonStaff as $r)
                <div class="card-item">
                    <h6>{{ $r['nama'] }}</h6>
                    <p>Hadir: <strong>{{ $r['hadir'] }}</strong> | Telat/Tdk Absen Pagi: <span class="badge bg-danger">{{ $r['telat'] }}</span></p>
                    <p>Alpa: {{ $r['ketidakhadiran'] }} | Persentase: <span class="badge bg-primary">{{ $r['persen'] }}%</span></p>
                    <p>Status: @if($r['keterangan'] == 'Disiplin') <span class="badge bg-success">Disiplin</span> @else <span class="badge bg-danger">Kurang Disiplin</span> @endif </p>
                    <p>Insentif: <span class="badge bg-success">Rp {{ number_format($r['insentif'],0,',','.') }}</span></p>
                </div>
                @empty
                <div class="text-center text-muted">Tidak ada data</div>
                @endforelse
            </div>

            {{-- Grafik --}}
            <div class="mt-4">
                <canvas id="chartPresensi"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            let hadir = {{ collect($rekapStaff)->sum('hadir') }} + {{ collect($rekapNonStaff)->sum('hadir') }};
            let telat = {{ collect($rekapStaff)->sum('telat') }} + {{ collect($rekapNonStaff)->sum('telat') }};
            let ketidakhadiran = {{ collect($rekapStaff)->sum('ketidakhadiran') }} + {{ collect($rekapNonStaff)->sum('ketidakhadiran') }};

            new Chart(document.getElementById('chartPresensi'), {
                type: 'bar',
                data: {
                    labels: ['Hadir', 'Pelanggaran (Telat/Tdk Absen Pagi)', 'Ketidakhadiran'],
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modeSelect = document.getElementById('modeSelect');
    const bulanField = document.getElementById('bulanField');
    const startDateField = document.getElementById('startDateField');
    const endDateField = document.getElementById('endDateField');

    function toggleFields() {
        const mode = modeSelect.value;
        bulanField.style.display = (mode === 'bulanan') ? 'block' : 'none';
        startDateField.style.display = (mode === 'custom') ? 'block' : 'none';
        endDateField.style.display = (mode === 'custom') ? 'block' : 'none';
    }

    modeSelect.addEventListener('change', toggleFields);
    toggleFields(); // Init on load
});
</script>
@endsection