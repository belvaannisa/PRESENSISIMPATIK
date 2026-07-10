@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <div class="card shadow">

        <div class="card-header text-white"
             style="background:#FA713F;">

            <h4 class="mb-0">
                {{ $karyawan->nama }}
            </h4>

            <small>Riwayat Presensi Karyawan</small>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="text-center text-white"
                           style="background:#FA713F;">

                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($presensis as $presensi)

                        <tr>

                            <td class="text-center">
                                {{ $loop->iteration }}
                            </td>

                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}
                            </td>

                            <td class="text-center">
                                {{ $presensi->jam_masuk }}
                            </td>

                            <td class="text-center">

                                @if($presensi->status == 'Tepat Waktu')

                                    <span class="badge bg-success">
                                        Tepat Waktu
                                    </span>

                                @elseif($presensi->status == 'Terlambat')

                                    <span class="badge bg-danger">
                                        Terlambat
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Tidak Hadir
                                    </span>

                                @endif

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="4" class="text-center text-muted">

                                Belum ada data presensi.

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <a href="{{ route('karyawan.index') }}"
               class="btn btn-secondary">

                Kembali

            </a>

        </div>

    </div>

</div>

@endsection