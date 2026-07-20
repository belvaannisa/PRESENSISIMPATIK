<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Presensi</title>
    <style>
        /* PERBAIKAN: Paksa orientasi kertas menjadi Landscape agar tabel tidak terpotong */
        @page {
            size: A4 landscape;
            margin: 30px 40px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            color: #555;
        }
        h3 {
            font-size: 13px;
            margin-bottom: 5px;
            margin-top: 20px;
            color: #FA713F;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        /* PERBAIKAN: Mencegah baris tabel terpotong di tengah saat berganti halaman */
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            vertical-align: middle;
        }
        th {
            background-color: #FA713F;
            color: white;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-danger { color: #d9534f; font-weight: bold; }
        .text-success { color: #5cb85c; font-weight: bold; }
        /* Mencegah teks turun ke bawah untuk nominal uang */
        .nowrap { white-space: nowrap; } 
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN REKAPITULASI PRESENSI KARYAWAN</h2>
        <p>
            @if($mode == 'custom')
                Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
            @else
                Periode: 26 {{ \Carbon\Carbon::parse($startDate)->translatedFormat('F Y') }} - 25 {{ \Carbon\Carbon::parse($endDate)->translatedFormat('F Y') }}
            @endif
        </p>
    </div>

    {{-- ================= TABEL STAFF ================= --}}
    <h3>A. Rekap Presensi Staff</h3>
    <table>
        <thead>
            <tr>
                {{-- PERBAIKAN: Pembagian lebar kolom memakai Persentase (%) agar proporsional --}}
                <th style="width: 4%;">No</th>
                <th style="width: 25%;">Nama</th>
                <th style="width: 8%;">Hadir</th>
                <th style="width: 15%;">Terlambat /<br>Tdk Absen Pagi</th>
                <th style="width: 8%;">Alpa</th>
                <th style="width: 15%;">Keterangan</th>
                <th style="width: 10%;">Persentase</th>
                <th style="width: 15%;">Insentif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapStaff as $index => $r)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $r['nama'] }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center text-danger">{{ $r['telat'] }}</td>
                <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
                <td class="text-center">{{ $r['keterangan'] }}</td>
                <td class="text-center">{{ $r['persen'] }}%</td>
                <td class="text-right nowrap">Rp {{ number_format($r['insentif'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Tidak ada data Staff pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ================= TABEL NON STAFF ================= --}}
    <h3>B. Rekap Presensi Non Staff</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 25%;">Nama</th>
                <th style="width: 8%;">Hadir</th>
                <th style="width: 15%;">Terlambat /<br>Tdk Absen Pagi</th>
                <th style="width: 8%;">Alpa</th>
                <th style="width: 15%;">Keterangan</th>
                <th style="width: 10%;">Persentase</th>
                <th style="width: 15%;">Insentif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapNonStaff as $index => $r)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $r['nama'] }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center text-danger">{{ $r['telat'] }}</td>
                <td class="text-center">{{ $r['ketidakhadiran'] }}</td>
                <td class="text-center">{{ $r['keterangan'] }}</td>
                <td class="text-center">{{ $r['persen'] }}%</td>
                <td class="text-right nowrap">Rp {{ number_format($r['insentif'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Tidak ada data Non Staff pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>