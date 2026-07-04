{{-- resources/views/laporan/keterlambatan/pdf.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keterlambatan</title>

    <style>

        body{
            font-family: sans-serif;
            font-size: 12px;
            padding: 20px;
        }

        table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td{
            border: 1px solid #000;
            padding: 7px;
        }

        th{
            background: #FA713F;
            color: white;
            text-align: center;
        }

        .text-center{
            text-align: center;
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
            margin-top: 50px;
            text-align: right;
        }

    </style>

</head>

<body>

    {{-- HEADER --}}
    <div class="header">

        <img src="{{ public_path('images/logo.jpeg') }}"
             class="logo">

        <h2 style="margin:10px 0 0 0;">
            PT. Simpatik Borneo Utama
        </h2>

        <h3 style="margin:5px 0 0 0;">
            Laporan Keterlambatan Karyawan
        </h3>

        <p>
            Bulan :
            {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
        </p>

    </div>


    {{-- TABLE --}}
    <table>

        <tr>
            <th width="5%">No</th>
            <th>Nama</th>
            <th width="15%">Total Hadir</th>
            <th width="15%">Terlambat</th>
            <th width="20%">Persentase</th>
        </tr>

        @forelse($data as $d)

        <tr>

            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td>
                {{ $d['nama'] }}
            </td>

            <td class="text-center">
                {{ $d['hadir'] }}
            </td>

            <td class="text-center">
                {{ $d['telat'] }}
            </td>

            <td class="text-center">
                {{ $d['persen_telat'] }}%
            </td>

        </tr>

        @empty

        <tr>
            <td colspan="5" class="text-center">
                Tidak ada data keterlambatan
            </td>
        </tr>

        @endforelse

    </table>


    {{-- FOOTER --}}
    <div class="footer">

        <p>
            Banjarbaru,
            {{ date('d-m-Y') }}
        </p>

        <br><br><br>

        <p>
            <b>Kepala Personalia</b>
        </p>

    </div>

</body>
</html>