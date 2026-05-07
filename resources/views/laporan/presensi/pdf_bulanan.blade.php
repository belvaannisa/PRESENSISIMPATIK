<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi</title>

    <style>

        body{
            font-family: sans-serif;
            font-size: 12px;
            padding: 20px;
        }

        table{
            width: 100%;
            border-collapse: collapse;
        }

        th, td{
            border: 1px solid #000;
            padding: 6px;
        }

        th{
            background: #FA713F;
            color: white;
            text-align: center;
        }

        td{
            vertical-align: middle;
        }

        .text-center{
            text-align: center;
        }

        .text-right{
            text-align: right;
        }

        .terlambat{
            color: red;
            font-weight: bold;
        }

        .tepat{
            color: green;
            font-weight: bold;
        }

        .header{
            text-align: center;
            margin-bottom: 20px;
        }

        .logo{
            width: 70px;
            border: 1px solid rgba(0,0,0,0.3);
            border-radius: 8px;
        }

        .footer{
            width: 100%;
            margin-top: 40px;
            text-align: right;
        }

    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">

        {{-- LOGO --}}
        <img src="{{ public_path('images/logo.jpeg') }}"
             class="logo">

        {{-- NAMA PT --}}
        <h2 style="margin:10px 0 0 0;">
            PT. Simpatik Borneo Utama
        </h2>

        {{-- JUDUL --}}
        <h3 style="margin:5px 0 0 0;">

            @if($mode == 'harian')
                Laporan Presensi Harian
            @elseif($mode == 'mingguan')
                Laporan Presensi Mingguan
            @else
                Laporan Presensi Bulanan
            @endif

        </h3>

        {{-- TANGGAL --}}
        <p style="margin-top:5px;">

            @if($mode == 'harian')
                Tanggal : {{ $tanggal }}
            @elseif($mode == 'mingguan')
                Minggu : {{ \Carbon\Carbon::parse($tanggal)->startOfWeek()->format('d-m-Y') }}
                s/d
                {{ \Carbon\Carbon::parse($tanggal)->endOfWeek()->format('d-m-Y') }}
            @else
                Bulan : {{ $bulan }}
            @endif

        </p>

    </div>


    {{-- ========================= --}}
    {{-- HARIAN & MINGGUAN --}}
    {{-- ========================= --}}
    @if($mode != 'bulanan')

    <table>

        <tr>
            <th>Nama</th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Status</th>
        </tr>

        @foreach($data as $d)

        <tr>

            <td>
                {{ $d->karyawan->nama }}
            </td>

            <td class="text-center">
                {{ $d->tanggal }}
            </td>

            <td class="text-center">
                {{ $d->jam_masuk }}
            </td>

            <td class="text-center">

                @if($d->status == 'Terlambat')

                    <span class="terlambat">
                        Terlambat
                    </span>

                @else

                    <span class="tepat">
                        Tepat Waktu
                    </span>

                @endif

            </td>

        </tr>

        @endforeach

    </table>

    @endif



    {{-- ========================= --}}
    {{-- BULANAN --}}
    {{-- ========================= --}}
    @if($mode == 'bulanan')

    <table>

        <tr>
            <th>Nama</th>
            <th>Hadir</th>
            <th>Tepat Waktu</th>
            <th>Terlambat</th>
            <th>Absen</th>
            <th>Persentase</th>
            <th>Insentif</th>
        </tr>

        @foreach($rekap as $r)

        <tr>

            <td>
                {{ $r['nama'] }}
            </td>

            <td class="text-center">
                {{ $r['hadir'] }}
            </td>

            <td class="text-center">
                {{ $r['tepat'] }}
            </td>

            <td class="text-center">
                {{ $r['telat'] }}
            </td>

            <td class="text-center">
                {{ $r['absen'] }}
            </td>

            <td class="text-center">
                {{ $r['persen'] }}%
            </td>

            <td class="text-center">
                Rp {{ number_format($r['insentif'], 0, ',', '.') }}
            </td>

        </tr>

        @endforeach

    </table>

    @endif



    {{-- FOOTER --}}
    <div class="footer">

        <p>
            Banjarbaru,
            {{ date('d-m-Y') }}
        </p>

        <br><br><br>

        <p>
            <b>Admin</b>
        </p>

    </div>

</body>
</html>