@extends('layouts.app')

@section('content')

<style>
/* ================= MOBILE ONLY ================= */
@media (max-width: 768px) {

    .container {
        padding: 10px !important;
    }

    .card-body {
        padding: 15px !important;
    }

    .form-label {
        font-size: 14px;
    }

    .form-control {
        font-size: 14px;
        padding: 8px;
    }

    .btn {
        font-size: 14px;
        padding: 8px;
    }
}
</style>

<div class="container mt-4">

    <div class="card shadow-sm border-0">
        
        {{-- HEADER --}}
        <div class="card-header text-white" style="background-color: #FA713F;">
            <h5 class="mb-0">Tambah Karyawan</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('karyawan.store') }}" method="POST">
                @csrf

                {{-- NAMA --}}
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" 
                           name="nama" 
                           class="form-control @error('nama') is-invalid @enderror"
                           value="{{ old('nama') }}">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- JABATAN --}}
                <div class="mb-3">
                    <label class="form-label">Jabatan</label>
                    <select name="jabatan" 
                            class="form-control @error('jabatan') is-invalid @enderror">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jabatanList as $jabatan)
                            <option value="{{ $jabatan }}" 
                                {{ old('jabatan') == $jabatan ? 'selected' : '' }}>
                                {{ $jabatan }}
                            </option>
                        @endforeach
                    </select>
                    @error('jabatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- NO HP --}}
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" 
                           name="no_hp" 
                           class="form-control @error('no_hp') is-invalid @enderror"
                           value="{{ old('no_hp') }}">
                    @error('no_hp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ALAMAT --}}
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <input type="text" 
                           name="alamat" 
                           class="form-control @error('alamat') is-invalid @enderror"
                           value="{{ old('alamat') }}">
                    @error('alamat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- EMAIL --}}
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- TANGGAL MASUK --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Masuk</label>
                    <input type="date" 
                           name="tanggal_masuk" 
                           class="form-control @error('tanggal_masuk') is-invalid @enderror"
                           value="{{ old('tanggal_masuk') }}">
                    @error('tanggal_masuk')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- STATUS --}}
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status_aktif" class="form-control">
                        <option value="1" {{ old('status_aktif', 1) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('status_aktif') == 0 ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- TIPE JAM KELUAR --}}
                <div class="mb-3">
                    <label class="form-label">Tipe Jam Keluar</label>
                    <select name="tipe_jam_keluar" 
                            id="tipe_jam_keluar"
                            class="form-control @error('tipe_jam_keluar') is-invalid @enderror">
                        <option value="terbatas" {{ old('tipe_jam_keluar') == 'terbatas' ? 'selected' : '' }}>
                            Terbatas
                        </option>
                        <option value="tidak terbatas" {{ old('tipe_jam_keluar') == 'tidak terbatas' ? 'selected' : '' }}>
                            Tidak Terbatas
                        </option>
                    </select>
                    @error('tipe_jam_keluar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- JAM KELUAR --}}
                <div class="mb-4" id="jamKeluarField">
                    <label class="form-label">Jam Keluar</label>
                    <input type="time" 
                           name="jam_keluar" 
                           class="form-control @error('jam_keluar') is-invalid @enderror"
                           value="{{ old('jam_keluar') }}">
                    @error('jam_keluar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- BUTTON --}}
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit" class="btn btn-success">
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

{{-- JS --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    const tipe = document.getElementById('tipe_jam_keluar');
    const field = document.getElementById('jamKeluarField');

    function toggleJamKeluar() {
        field.style.display = (tipe.value === 'tidak terbatas') ? 'none' : 'block';
    }

    toggleJamKeluar();
    tipe.addEventListener('change', toggleJamKeluar);

});
</script>

@endsection