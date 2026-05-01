<div style="text-align:center; margin-bottom:20px;">

    {{-- LOGO --}}
    <img src="{{ asset('images/logo.jpeg') }}" 
         style="width:70px; border:1px solid rgba(0,0,0,0.3); border-radius:8px;">

    {{-- NAMA PT --}}
    <h3 style="margin:10px 0 0 0;">
        PT. Simpatik Borneo Utama
    </h3>

    {{-- JUDUL --}}
    <h4 style="margin:5px 0 0 0;">
        Laporan Presensi
    </h4>

</div>

<table border="1" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">

    <tr style="background:#FA713F; color:white; text-align:center;">
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Status</th>
    </tr>

    @foreach($data as $d)
    <tr>
        <td>{{ $d->karyawan->nama }}</td>
        <td>{{ $d->tanggal }}</td>
        <td>{{ $d->jam_masuk }}</td>
        <td style="text-align:center;">
            @if($d->status == 'Terlambat')
                <span style="color:red; font-weight:bold;">Terlambat</span>
            @else
                <span style="color:green; font-weight:bold;">Tepat Waktu</span>
            @endif
        </td>
    </tr>
    @endforeach

</table>

<br><br>

{{-- TANDA TANGAN --}}
<div style="width:100%; text-align:right;">
    <p>Banjarbaru, {{ date('d-m-Y') }}</p>
    <br><br><br>
    <p><b>Admin</b></p>
</div>