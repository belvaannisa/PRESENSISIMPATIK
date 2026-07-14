<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi</title>
    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            padding:20px;
        }
        table{
            width:100%;
            border-collapse: collapse;
        }
        th,td{
            border:1px solid #000;
            padding:6px;
        }
        th{
            background:#FA713F;
            color:white;
            text-align:center;
        }
        td{
            vertical-align: middle;
        }
        .text-center{
            text-align:center;
        }
        .text-right{
            text-align:right;
        }
        .terlambat{
            color:red;
            font-weight:bold;
        }
        .tepat{
            color:green;
            font-weight:bold;
        }
        .header{
            text-align:center;
            margin-bottom:20px;
        }
        .logo{
            width:70px;
            border-radius:8px;
        }
        .footer{
            width:100%;
            margin-top:50px;
            text-align:right;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo.jpeg') }}" class="logo">
    <h2 style="margin:10px 0 0 0;">
        PT. Simpatik Borneo Utama
    </h2>
    <h3 style="margin:5px 0 0 0;">
        LAPORAN PRESENSI KARYAWAN
    </h3>
    @if($mode=='harian')
        <p>Tanggal : {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
    @elseif($mode=='mingguan')
        <p>Periode : {{ \Carbon\Carbon::parse($tanggal)->startOfWeek()->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($tanggal)->endOfWeek()->translatedFormat('d F Y') }}</p>
    @elseif($mode=='bulanan')
        <p>Periode : {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</p>
    @endif
</div>

{{-- ========================= --}}
{{-- HARIAN & MINGGUAN --}}
{{-- ========================= --}}

@if($mode!='bulanan')
<table>
    <thead>
    <tr>
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Jam Keluar</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @forelse($data as $d)
    <tr>
        <td>{{ $d->karyawan->nama ?? '-' }}</td>
        <td class="text-center">{{ $d->tanggal }}</td>
        <td class="text-center">{{ $d->jam_masuk }}</td>
        <td class="text-center">{{ $d->jam_keluar ?? '-' }}</td>
        <td class="text-center">
            @if($d->status=="Terlambat")
                <span class="terlambat">Terlambat</span>
            @elseif($d->status=="Tepat Waktu")
                <span class="tepat">Tepat Waktu</span>
            @else
                <span style="color:#d35400; font-weight:bold;">{{ $d->status }}</span>
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="5" class="text-center">Tidak ada data.</td>
    </tr>
    @endforelse
    </tbody>
</table>
@endif

{{-- ========================= --}}
{{-- BULANAN --}}
{{-- ========================= --}}

@if($mode=='bulanan')
<table style="margin-bottom:15px;">
    <tr>
        <td width="25%"><b>Jumlah Karyawan</b></td>
        <td>{{ count($rekapStaff) + count($rekapNonStaff) }} Orang</td>
    </tr>
    <tr>
        <td><b>Periode</b></td>
        <td>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</td>
    </tr>
</table>

<h3 style="margin-bottom:10px;">Rekap Presensi Staff</h3>
<table style="margin-bottom:25px;">
    <thead>
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
    @forelse($rekapStaff as $r)
        <tr>
            <td>{{ $r['nama'] }}</td>
            <td class="text-center">{{ $r['hadir'] }}</td>
            <td class="text-center">{{ $r['telat'] }}</td>
            <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
            <td class="text-center">
                @if($r['keterangan']=="Disiplin")
                    <span class="tepat">Disiplin</span>
                @else
                    <span class="terlambat">Kurang Disiplin</span>
                @endif
            </td>
            <td class="text-center">{{ $r['persen'] }}%</td>
            <td class="text-right">Rp {{ number_format($r['insentif'],0,',','.') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center">Tidak ada data Staff.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<h3 style="margin-bottom:10px;">Rekap Presensi Non Staff</h3>
<table>
    <thead>
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
    @forelse($rekapNonStaff as $r)
        <tr>
            <td>{{ $r['nama'] }}</td>
            <td class="text-center">{{ $r['hadir'] }}</td>
            <td class="text-center">{{ $r['telat'] }}</td>
            <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
            <td class="text-center">
                @if($r['keterangan']=="Disiplin")
                    <span class="tepat">Disiplin</span>
                @else
                    <span class="terlambat">Kurang Disiplin</span>
                @endif
            </td>
            <td class="text-center">{{ $r['persen'] }}%</td>
            <td class="text-right">Rp {{ number_format($r['insentif'],0,',','.') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center">Tidak ada data Non Staff.</td>
        </tr>
    @endforelse
    </tbody>
</table>
@endif

<div class="footer">
<p>Banjarbaru, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
<br><br><br>
<p><b>Kepala Personalia</b></p>
</div>

</body>
</html>