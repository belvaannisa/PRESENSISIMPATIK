@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <div class="card shadow-sm">

        <div class="card-header text-white"
             style="background-color:#FA713F;">
            <h5 class="mb-0">
                Monitoring Presensi Log
            </h5>
        </div>

        <div class="card-body">

            <form method="GET" class="mb-3">
                <div class="row">

                    <div class="col-md-4">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari Nama / PIN"
                               value="{{ $search }}">
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-dark">
                            Cari
                        </button>
                    </div>

                </div>
            </form>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="table-dark">

                        <tr>
                            <th>ID</th>
                            <th>PIN</th>
                            <th>Nama Mesin</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Karyawan</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($logs as $log)

                        <tr>

                            <td>{{ $log->id }}</td>

                            <td>{{ $log->pin }}</td>

                            <td>{{ $log->nama }}</td>

                            <td>{{ $log->tanggal }}</td>

                            <td>{{ $log->jam }}</td>

                            <td>
                                {{ $log->karyawan->nama ?? '-' }}
                            </td>

                            <td>

                                @if($log->status_sinkron == 'matched')

                                    <span class="badge bg-success">
                                        Matched
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Unmatched
                                    </span>

                                @endif

                            </td>

                            <td>{{ $log->catatan }}</td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="8" class="text-center">
                                Tidak Ada Data
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>

        </div>

    </div>

</div>

@endsection
