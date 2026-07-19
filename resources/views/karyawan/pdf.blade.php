@php
use Carbon\Carbon;

Carbon::setLocale('id');
setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian');
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Presensi Karyawan</title>

    <style>

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
            padding:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
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
            vertical-align:middle;
        }

        .text-center{
            text-align:center;
        }

        .text-right{
            text-align:right;
        }

        .tepat{
            color:green;
            font-weight:bold;
        }

        .terlambat{
            color:red;
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

    <img src="{{ public_path('images/logo.jpeg') }}"
         class="logo">

    <h2 style="margin:10px 0 0 0;">
        PT. Simpatik Borneo Utama
    </h2>

    <h3 style="margin:5px 0;">
        DETAIL RIWAYAT PRESENSI KARYAWAN
    </h3>

</div>

<table style="margin-bottom:15px;">

    <tr>

        <td width="20%"><b>Nama</b></td>
        <td>{{ $karyawan->nama }}</td>

        <td width="20%"><b>Jabatan</b></td>
        <td>{{ $karyawan->jabatan }}</td>

    </tr>

    <tr>

        <td><b>No. HP</b></td>
        <td>{{ $karyawan->no_hp ?: '-' }}</td>

        <td><b>Tanggal Masuk</b></td>
        <td>
            {{ $karyawan->tanggal_masuk
                ? \Carbon\Carbon::parse($karyawan->tanggal_masuk)->translatedFormat('d F Y')
                : '-' }}
        </td>

    </tr>

</table>

<table>

    <thead>

    <tr>

        <th width="40">No</th>
        <th>Hari</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Jam Keluar</th>
        <th>Status</th>

    </tr>

    </thead>

   {{-- Cari bagian <tbody> di dalam tabel presensi pada file pdf.blade.php Anda --}}

<tbody>
    @forelse($presensis as $presensi)
    <tr>
        <td class="text-center">{{ $loop->iteration }}</td>
        <td class="text-center">{{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l') }}</td>
        <td class="text-center">{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}</td>
        <td class="text-center">{{ $presensi->jam_masuk ?: '-' }}</td>
        <td class="text-center">{{ $presensi->jam_keluar ?: '-' }}</td>
        <td class="text-center">

            {{-- REVISI: Tambahkan kondisi untuk "Tidak Absen Pagi" agar berwarna merah seperti Terlambat --}}
            @if($presensi->status == 'Tepat Waktu')
                <span class="tepat">Tepat Waktu</span>
            @elseif($presensi->status == 'Terlambat' || $presensi->status == 'Tidak Absen Pagi')
                <span class="terlambat">{{ $presensi->status }}</span>
            @else
                <span class="terlambat">Tidak Hadir</span>
            @endif

        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" class="text-center">Belum Ada Data Presensi.</td>
    </tr>
    @endforelse
</tbody>

</table>

<div class="footer">

    <p>

        Banjarbaru,

        {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}

    </p>

    <br><br><br>

    <p>
        <b>
            {{ $user->role == 'pimpinan'
                ? 'Pimpinan'
                : 'Kepala Personalia' }}
        </b>
    </p>

</div>

</body>
</html>