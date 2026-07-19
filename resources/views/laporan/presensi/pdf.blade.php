<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi Bulanan</title>
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
        LAPORAN PRESENSI KARYAWAN (BULANAN)
    </h3>
    <p>Periode : {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</p>
</div>

<table style="margin-bottom:15px;">
    <tr>
        <td width="25%"><b>Jumlah Karyawan</b></td>
        <td>{{ count($rekapStaff) + count($rekapNonStaff) }} Orang</td>
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
   {{-- ... (Cari bagian thead di dalam tabel rekap laporan bulanan) ... --}}

<thead class="text-center" style="background:#FA713F; color:white;">
    <tr>
        <th>Nama</th>
        <th>Hadir</th>
        {{-- REVISI: Judul kolom diubah agar mencakup kedua status pemotongan --}}
        <th>Terlambat / Tdk Absen Pagi</th> 
        <th>Ketidakhadiran</th>
        <th>Keterangan</th>
        <th>Persentase</th>
        <th>Insentif</th>
    </tr>
</thead>

<tbody>
    @foreach($rekapStaff as $r)
    <tr>
        <td>{{ $r['nama'] }}</td>
        <td class="text-center">{{ $r['hadir'] }}</td>
        {{-- Data di bawah ini tetap mengambil variabel yang sama yang sudah kita hitung di Controller --}}
        <td class="text-center">{{ $r['telat'] }}</td> 
        <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
        <td class="text-center">{{ $r['keterangan'] }}</td>
        <td class="text-center">{{ $r['persen'] }}%</td>
        <td class="text-center">Rp {{ number_format($r['insentif'],0,',','.') }}</td>
    </tr>
    @endforeach
    
    {{-- Lakukan perubahan judul kolom yang sama pada tabel REKAP PRESENSI NON STAFF di bawahnya --}}
</tbody>
</table>

<div class="footer">
<p>Banjarbaru, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
<br><br><br>
<p><b>Kepala Personalia</b></p>
</div>

</body>
</html>