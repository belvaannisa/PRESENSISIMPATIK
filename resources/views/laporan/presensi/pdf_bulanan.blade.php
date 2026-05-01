<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi Bulanan</title>

    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            padding: 6px;
        }

        td {
            padding: 6px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            width: 100%;
        }

        .signature {
            width: 200px;
            text-align: center;
            float: right;
        }
    </style>
</head>
<body>

    <h2>LAPORAN PRESENSI BULANAN</h2>

    <div class="subtitle">
        Periode: {{ \Carbon\Carbon::parse(request('bulan'))->format('F Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Karyawan</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Absen</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>

            @php $no = 1; @endphp

            @foreach($rekap as $r)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $r['nama'] }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center">{{ $r['telat'] }}</td>
                <td class="text-center">{{ $r['absen'] }}</td>
                <td class="text-center">{{ $r['persen'] }}%</td>
            </tr>
            @endforeach

        </tbody>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        <div class="signature">
            <p>{{ now()->format('d M Y') }}</p>
            <br><br><br>
            <p>(_____________________)</p>
        </div>
    </div>

</body>
</html>