@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ================= */
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

/* ================= DESKTOP ================= */
@media (min-width: 769px) {
    .card-mobile {
        display: none;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm">
        <div class="card-header text-white" style="background:#FA713F;">
            <h5 class="mb-0">Laporan Presensi</h5>
        </div>

        <div class="card-body">
            {{-- FILTER + DOWNLOAD --}}
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    {{-- FILTER --}}
    <form method="GET" class="row g-2 flex-grow-1">

        <div class="col-md-3 col-12">
            <select name="mode" class="form-control" onchange="this.form.submit()">
                <option value="harian" {{ $mode=='harian'?'selected':'' }}>Harian</option>
                <option value="mingguan" {{ $mode=='mingguan'?'selected':'' }}>Mingguan</option>
                <option value="bulanan" {{ $mode=='bulanan'?'selected':'' }}>Bulanan</option>
            </select>
        </div>

        <div class="col-md-3 col-12" id="tanggalField">
            <input type="date" name="tanggal" value="{{ $tanggal ?? '' }}" class="form-control">
        </div>

        <div class="col-md-3 col-12" id="bulanField">
            <input type="month" name="bulan" value="{{ $bulan ?? '' }}" class="form-control">
        </div>

        <div class="col-md-2 col-12">
            <button class="btn btn-dark w-100">Filter</button>
        </div>

    </form>

    {{-- DOWNLOAD BUTTON (KANAN) --}}
    <div>
        <a href="{{ route('laporan.presensi.exportPdf', request()->all()) }}"
   class="btn btn-danger">
    🖨 Print PDF
</a>
    </div>

</div>
            {{-- ================= HARIAN & MINGGUAN ================= --}}
            @if($mode == 'harian' || $mode == 'mingguan')

            {{-- DESKTOP --}}
            <div class="table-responsive table-mobile">
                <table class="table table-bordered table-striped">
                    <thead style="background:#FA713F;color:white;">
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                        <tr>
                            <td>{{ $d->karyawan->nama ?? '-' }}</td>
                            <td>{{ $d->tanggal }}</td>
                            <td>{{ $d->jam_masuk }}</td>
                            <td>{{ $d->jam_keluar }}</td>
                            <td>
                                @if($d->status == 'Terlambat')
                                    <span class="badge bg-danger">Terlambat</span>
                                @else
                                    <span class="badge bg-success">Tepat Waktu</span>
                                @endif
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

            {{-- MOBILE --}}
            <div class="card-mobile">
                @forelse($data as $d)
                <div class="card-item">

                    <h6><strong>{{ $d->karyawan->nama ?? '-' }}</strong></h6>

                    <p>Tanggal: {{ $d->tanggal }}</p>
                    <p>Masuk: {{ $d->jam_masuk }}</p>
                    <p>Keluar: {{ $d->jam_keluar }}</p>

                    <p>
                        Status:
                        @if($d->status == 'Terlambat')
                            <span class="badge bg-danger">Terlambat</span>
                        @else
                            <span class="badge bg-success">Tepat Waktu</span>
                        @endif
                    </p>

                </div>
                @empty
                <div class="text-center text-muted">Tidak ada data</div>
                @endforelse
            </div>

            @endif


            {{-- ================= BULANAN ================= --}}
            @if($mode == 'bulanan')

            {{-- DESKTOP --}}
            <div class="table-responsive table-mobile">
                <h5>Rekap Presensi</h5>
                <table class="table table-bordered">
                    <thead style="background:#FA713F;color:white;">
                        <tr>
                            <th>Nama</th>
                            <th>Hadir</th>
                            <th>Terlambat</th>
                            <th>Absen</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rekap as $r)
                        <tr>
                            <td>{{ $r['nama'] }}</td>
                            <td>{{ $r['hadir'] }}</td>
                            <td class="text-danger">{{ $r['telat'] }}</td>
                            <td>{{ $r['absen'] }}</td>
                            <td>{{ $r['persen'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <h5 class="mt-4">Laporan Insentif</h5>
                <table class="table table-bordered">
                    <thead style="background:#28a745;color:white;">
                        <tr>
                            <th>Nama</th>
                            <th>Hadir</th>
                            <th>Insentif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rekap as $r)
                        <tr>
                            <td>{{ $r['nama'] }}</td>
                            <td>{{ $r['hadir'] }}</td>
                            <td class="text-success">
                                Rp {{ number_format($r['insentif'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE --}}
            <div class="card-mobile">

                <h6 class="mb-2">Rekap Presensi</h6>

                @foreach($rekap as $r)
                <div class="card-item">
                    <h6>{{ $r['nama'] }}</h6>

                    <p>Hadir: <strong>{{ $r['hadir'] }}</strong></p>
                    <p>Terlambat: <span class="badge bg-danger">{{ $r['telat'] }}</span></p>
                    <p>Absen: {{ $r['absen'] }}</p>
                    <p>Persen: <span class="badge bg-primary">{{ $r['persen'] }}%</span></p>
                    <p>Insentif: <span class="badge bg-success">Rp {{ number_format($r['insentif'],0,',','.') }}</span></p>
                </div>
                @endforeach

            </div>

            {{-- GRAFIK --}}
            <canvas id="chartPresensi"></canvas>

            @endif

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
@if($mode == 'bulanan')
let hadir = {{ collect($rekap)->sum('hadir') }};
let telat = {{ collect($rekap)->sum('telat') }};
let absen = {{ collect($rekap)->sum('absen') }};

new Chart(document.getElementById('chartPresensi'), {
    type: 'bar',
    data: {
        labels: ['Hadir', 'Terlambat', 'Absen'],
        datasets: [{
            label: 'Statistik Presensi',
            data: [hadir, telat, absen]
        }]
    }
});
@endif
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    let mode = document.querySelector('[name="mode"]').value;
    let tanggalField = document.getElementById('tanggalField');
    let bulanField = document.getElementById('bulanField');

    function toggle() {
        if (mode === 'bulanan') {
            tanggalField.style.display = 'none';
            bulanField.style.display = 'block';
        } else {
            tanggalField.style.display = 'block';
            bulanField.style.display = 'none';
        }
    }

    toggle();

    document.querySelector('[name="mode"]').addEventListener('change', function(){
        mode = this.value;
        toggle();
    });

});
</script>

@endsection