@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ONLY ================= */
@media (max-width: 768px) {
    .container {
        padding: 10px !important;
    }
    /* SEMBUNYIKAN TABLE */
    .table-responsive {
        display: none;
    }
    /* CARD MOBILE */
    .karyawan-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .karyawan-card h6 {
        margin-bottom: 5px;
        font-weight: 600;
    }
    .karyawan-card small {
        color: #888;
    }
    .karyawan-card .row {
        font-size: 14px;
    }
    .karyawan-card .btn {
        width: 48%;
    }
}

/* DESKTOP ONLY (card disembunyikan) */
@media (min-width: 769px) {
    .karyawan-mobile {
        display: none;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">

        {{-- HEADER DENGAN TOMBOL MODAL --}}
        <div class="card-header text-white d-flex justify-content-between align-items-center"
             style="background-color: #FA713F;">
            <h5 class="mb-0">Data Karyawan</h5>

            <div>
                @if(auth()->user()->role != 'pimpinan')
                {{-- Tombol Trigger Modal Pengaturan --}}
                <button type="button" class="btn btn-light btn-sm fw-bold me-2" data-bs-toggle="modal" data-bs-target="#modalPengaturanJabatan">
                    ⚙️ Atur Jam Keluar
                </button>

                <a href="{{ route('karyawan.tambah') }}"
                   class="btn text-dark btn-sm"
                   style="background-color: #FEECC8;">
                        ➕ Tambah
                </a>
                @endif
            </div>

        </div>

        <div class="card-body">

            {{-- SEARCH --}}
            <div class="d-flex justify-content-end mb-3">
                <form action="{{ route('karyawan.index') }}" method="GET" class="d-flex">
                    <input type="text"
                           name="search"
                           class="form-control me-2"
                           placeholder="Cari karyawan..."
                           value="{{ request('search') }}">

                    <button class="btn btn-outline-secondary">
                        Cari
                    </button>
                </form>
            </div>

            {{-- ================= STAFF ================= --}}
            <h5 class="fw-bold mb-3">Staff</h5>

            {{-- ================= DESKTOP TABLE STAFF ================= --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center text-white" style="background-color:#FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>No. HP</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $k)
                        <tr>
                            <td class="text-center">{{ $noStaff++ }}</td>
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->jabatan }}</td>
                            <td>{{ $k->no_hp }}</td>
                            <td class="text-center">
                                {{ $k->tanggal_masuk ? \Carbon\Carbon::parse($k->tanggal_masuk)->format('d-m-Y') : '' }}
                            </td>
                            <td class="text-center">
                                @if($k->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('karyawan.detail',$k->id) }}" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                @if(auth()->user()->role != 'pimpinan')
                                    <a href="{{ route('karyawan.edit',$k->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('karyawan.destroy',$k->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data karyawan ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Data Staff belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION STAFF --}}
            <div class="d-flex justify-content-center mb-5">
                {{ $staff->appends(request()->except('staff_page'))->links() }}
            </div>

            {{-- ================= NON STAFF ================= --}}
            <h5 class="fw-bold mb-3 mt-5">Non Staff</h5>

            {{-- ================= DESKTOP TABLE NON STAFF ================= --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center text-white" style="background-color:#FA713F;">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>No. HP</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nonStaff as $k)
                        <tr>
                            <td class="text-center">{{ $noNonStaff++ }}</td>
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->jabatan }}</td>
                            <td>{{ $k->no_hp }}</td>
                            <td class="text-center">
                                {{ $k->tanggal_masuk ? \Carbon\Carbon::parse($k->tanggal_masuk)->format('d-m-Y') : '' }}
                            </td>
                            <td class="text-center">
                                @if($k->status_aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('karyawan.detail', $k->id) }}" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                @if(auth()->user()->role != 'pimpinan')
                                    <a href="{{ route('karyawan.edit', $k->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data karyawan ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Data Non Staff belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION NON STAFF --}}
            <div class="d-flex justify-content-center mb-4">
                {{ $nonStaff->appends(request()->except('nonstaff_page'))->links() }}
            </div>

            {{-- ================= MOBILE ================= --}}
            <div class="karyawan-mobile">

                {{-- STAFF --}}
                <h5 class="fw-bold mb-3">Staff</h5>
                @forelse ($staff as $k)
                <div class="karyawan-card">
                    <h6>{{ $k->nama }}</h6>
                    <small>{{ $k->jabatan }}</small>
                    <div class="mt-2">📞 {{ $k->no_hp }}</div>
                    <div class="mt-2">
                        <strong>Tanggal Masuk :</strong><br>
                        {{ $k->tanggal_masuk ? \Carbon\Carbon::parse($k->tanggal_masuk)->format('d-m-Y') : '' }}
                    </div>
                    <div class="mt-2">
                        <strong>Status :</strong><br>
                        @if($k->status_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('karyawan.detail',$k->id) }}" class="btn btn-info btn-sm">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                        @if(auth()->user()->role != 'pimpinan')
                            <a href="{{ route('karyawan.edit',$k->id) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('karyawan.destroy',$k->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data karyawan ini?')">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center text-muted mb-4">Data Staff belum tersedia.</div>
                @endforelse

                <div class="d-flex justify-content-center mb-5">
                    {{ $staff->appends(request()->except('staff_page'))->links() }}
                </div>

                {{-- NON STAFF --}}
                <h5 class="fw-bold mb-3">Non Staff</h5>
                @forelse ($nonStaff as $k)
                <div class="karyawan-card">
                    <h6>{{ $k->nama }}</h6>
                    <small>{{ $k->jabatan }}</small>
                    <div class="mt-2">📞 {{ $k->no_hp }}</div>
                    <div class="mt-2">
                        <strong>Tanggal Masuk :</strong><br>
                        {{ $k->tanggal_masuk ? \Carbon\Carbon::parse($k->tanggal_masuk)->format('d-m-Y') : '' }}
                    </div>
                    <div class="mt-2">
                        <strong>Status :</strong><br>
                        @if($k->status_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('karyawan.detail',$k->id) }}" class="btn btn-info btn-sm">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                        @if(auth()->user()->role != 'pimpinan')
                            <a href="{{ route('karyawan.edit',$k->id) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('karyawan.destroy',$k->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data karyawan ini?')">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center text-muted">Data Non Staff belum tersedia.</div>
                @endforelse

                <div class="d-flex justify-content-center">
                    {{ $nonStaff->appends(request()->except('nonstaff_page'))->links() }}
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL PENGATURAN JAM KELUAR ================= -->
<div class="modal fade" id="modalPengaturanJabatan" tabindex="-1" aria-labelledby="modalPengaturanJabatanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #FA713F; color: white;">
                <h5 class="modal-title" id="modalPengaturanJabatanLabel">⚙️ Pengaturan Tipe Jam Keluar per Jabatan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Form akan mengarah ke route baru (nantinya kita buat di web.php dan KaryawanController) --}}
            <form action="{{ route('karyawan.update_pengaturan_jam') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size: 14px;">
                        <strong>Instruksi:</strong> Centang jabatan di bawah ini untuk mengatur tipe jam keluarnya menjadi <b>"Tidak Terbatas"</b>. Jika dibiarkan kosong, maka secara default akan menggunakan jam keluar <b>"Terbatas"</b>.
                    </div>

                    <div class="row px-2">
                        @php
                            // Mengambil list jabatan langsung dari Model
                            $jabatanList = \App\Models\Karyawan::$jabatanList ?? [];
                            // Sementara ini kita masih tarik data centang dari config sampai tabelnya di-updat
                        @endphp

                        @foreach($jabatanList as $jabatan)
                            <div class="col-md-6 mb-2">
                                <div class="form-check border rounded p-2" style="background-color: #f8f9fa;">
                                    <input class="form-check-input ms-1" type="checkbox" name="jabatan_tidak_terbatas[]" value="{{ $jabatan }}" id="check_{{ Str::slug($jabatan) }}" {{ in_array($jabatan, $jabatanTidakTerbatas) ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="check_{{ Str::slug($jabatan) }}" style="font-size: 14px; cursor:pointer; width:100%;">
                                        {{ $jabatan }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection