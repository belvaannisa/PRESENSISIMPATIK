<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
        }

        h2,h4,p{
            margin:0;
            text-align:center;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        table th,
        table td{
            border:1px solid #000;
            padding:6px;
        }

        table th{
            background:#eeeeee;
            text-align:center;
        }

        td{
            text-align:center;
        }

        .left{
            text-align:left;
        }

    </style>

</head>

<body>

<h2>PT Simpatik Borneo Jaya Mandiri</h2>
<h4>Detail Riwayat Presensi Karyawan</h4>

<br>

<p>
    <strong>Nama :</strong> {{ $karyawan->nama }}
</p>

<p>
    <strong>Jabatan :</strong> {{ $karyawan->jabatan }}
</p>

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

    <tbody>

    @forelse($presensis as $presensi)

    <tr>

        <td>{{ $loop->iteration }}</td>

        <td>
            {{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l') }}
        </td>

        <td>
            {{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}
        </td>

        <td>
            {{ $presensi->jam_masuk ?: '-' }}
        </td>

        <td>
            {{ $presensi->jam_keluar ?: '-' }}
        </td>

        <td>
            {{ $presensi->status }}
        </td>

    </tr>

    @empty

    <tr>

        <td colspan="6">
            Belum ada data presensi.
        </td>

    </tr>

    @endforelse

    </tbody>

</table>

<br><br>

<table style="border:none">

<tr style="border:none">

<td style="border:none;width:60%"></td>

<td style="border:none;text-align:center">

Banjarmasin,
{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}

<br><br><br><br>

_____________________

</td>

</tr>

</table>

</body>

</html>