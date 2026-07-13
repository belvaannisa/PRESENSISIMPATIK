@extends('layouts.app')

@section('content')
<style>

.table-warning{

    background-color:#FFF3CD !important;

}

.table-warning:hover{

    background-color:#FFE69C !important;

}

</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">

        {{-- HEADER --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Presensi</h5>

            <form action="{{ route('presensi.import') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    ⬆ Auto Import
                </button>
            </form>
        </div>

        <div class="card-body">

            {{-- MANUAL UPLOAD --}}
            <form action="{{ route('presensi.upload') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="mb-3 d-flex flex-column flex-lg-row gap-2">
                @csrf
                <input type="file" name="file" class="form-control" required>
                <button class="btn btn-primary">Upload CSV</button>
            </form>

            {{-- SEARCH --}}
            <div class="d-flex justify-content-end mb-3">
                <form action="{{ route('presensi.index') }}" method="GET" class="d-flex">
                    <input type="text"
                           name="search"
                           class="form-control me-2"
                           placeholder="Cari nama / tanggal..."
                           value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary">Cari</button>
                </form>
            </div>

            <div class="alert alert-warning py-2 mb-3">
                <strong>Keterangan :</strong>
                Baris Yang Berwarna Kuning Menandakan Data Presensi Pernah Diedit.
            </div>

            {{-- ================= DESKTOP TABLE ================= --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="text-center text-white" style="background-color: #FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Log Edit</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($presensis as $p)
                        <tr class="{{ $p->waktu_edit ? 'table-warning' : '' }}">

                            <td class="text-center">{{ $no++ }}</td>

                            <td>{{ $p->karyawan->nama ?? '-' }}</td>

                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}
                            </td>

                            <td class="text-center">{{ $p->jam_masuk ?? '-' }}</td>

                            <td class="text-center">{{ $p->jam_keluar ?? '-' }}</td>

                            <td class="text-center">

                                @if($p->status == 'Terlambat')
        <span class="badge bg-danger">Terlambat</span>
    @elseif($p->status == 'Tepat Waktu')
        <span class="badge bg-success">Tepat Waktu</span>
    @else
        <span class="badge bg-warning text-dark">{{ $p->status }}</span>
    @endif

                                @if($p->waktu_edit)

                                <br>

                                <small class="text-warning fw-bold">

                                ✏ Pernah Diedit

                                </small>

                                @endif

                            </td>

                            <td class="text-center">
                                <span class="badge bg-info">
                                    {{ $p->keterangan ?? '-' }}
                                </span>
                            </td>


                            <td class="text-center">

                                @if($p->editor)

                                <strong>

                                {{ $p->editor->name }}

                                </strong>

                                <br>

                                <small class="text-muted">

                                {{ \Carbon\Carbon::parse($p->waktu_edit)->format('d-m-Y H:i') }}

                                </small>

                                @else

                                -

                                @endif

                                </td>
                            <td class="text-center">

                                <a href="{{ route('presensi.edit', $p->id) }}"
                                   class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('presensi.destroy', $p->id) }}"
                                      method="POST"
                                      class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Yakin ingin menghapus data presensi ini?')">
                                                <i class="bi bi-trash-fill"></i>
                                    </button>

                                </form>

                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                Data Presensi Belum Tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= MOBILE CARD ================= --}}
            <div class="d-lg-none">

                @forelse ($presensis as $p)

                <div class="card mb-3 shadow-sm border-0">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">
                            <strong>{{ $p->karyawan->nama ?? '-' }}</strong>

                            <span class="text-muted small">
                                {{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}
                            </span>
                        </div>

                        <hr class="my-2">

                        <div class="row small">

                            <div class="col-6">
                                <strong>Masuk:</strong><br>
                                {{ $p->jam_masuk ?? '-' }}
                            </div>

                            <div class="col-6">
                                <strong>Keluar:</strong><br>
                                {{ $p->jam_keluar ?? '-' }}
                            </div>

                        </div>

                        <div class="mt-2">
                            <strong>Status:</strong><br>

                            @if($p->status == 'Terlambat')
                                <span class="badge bg-danger">
                                    Terlambat
                                </span>
                            @else
                                <span class="badge bg-success">
                                    Tepat Waktu
                                </span>
                            @endif

                            @if($p->waktu_edit)

                            <div class="mt-2">

                            <span class="badge bg-warning text-dark">

                            ✏ Pernah Diedit

                            </span>

                            </div>

                            @endif
                        </div>

                        <div class="mt-2">
                            <strong>Keterangan:</strong><br>

                            <span class="badge bg-info">
                                {{ $p->keterangan ?? '-' }}
                            </span>
                        </div>

                        <div class="mt-3 d-flex gap-2">

                            <a href="{{ route('presensi.edit', $p->id) }}"
                               class="btn btn-warning btn-sm w-50">
                                Edit
                            </a>

                            <form action="{{ route('presensi.destroy', $p->id) }}"
                                  method="POST"
                                  class="w-50">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="btn btn-danger btn-sm w-100"
                                        onclick="return confirm('Yakin ingin menghapus data presensi ini?')">
                                    Hapus
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

                @empty

                <div class="text-center text-muted">
                    Data presensi belum tersedia
                </div>

                @endforelse

            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $presensis->links() }}
            </div>

        </div>
    </div>

</div>
@endsection