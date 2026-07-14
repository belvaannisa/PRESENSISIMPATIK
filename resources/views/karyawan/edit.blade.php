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
            <h5 class="mb-0">Edit Karyawan</h5>
        </div>

        {{-- BODY --}}
        <div class="card-body">

            <form action="{{ route('karyawan.update', $karyawan->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">PIN Fingerprint</label>
                    <input type="text"
                        name="pin"
                        class="form-control"
                        value="{{ old('pin', $karyawan->pin ?? '') }}" readonly>
                </div>

                {{-- NAMA --}}
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" 
                           name="nama" 
                           class="form-control" 
                           value="{{ old('nama', $karyawan->nama) }}">
                </div>

                {{-- NO HP --}}
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" 
                           name="no_hp" 
                           class="form-control" 
                           value="{{ old('no_hp', $karyawan->no_hp) }}">
                </div>

                {{-- JABATAN --}}
                <div class="mb-4">
                    <label class="form-label">Jabatan</label>
                    <select name="jabatan" class="form-control">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jabatanList as $jabatan)
                            <option value="{{ $jabatan }}" 
                                {{ old('jabatan', $karyawan->jabatan) == $jabatan ? 'selected' : '' }}>
                                {{ $jabatan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- TANGGAL MASUK --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Masuk</label>
                    <input type="date"
                        name="tanggal_masuk"
                        class="form-control"
                        value="{{ old('tanggal_masuk', $karyawan->tanggal_masuk?->format('Y-m-d')) }}">
                </div>

                {{-- STATUS AKTIF --}}
                <div class="mb-4">
                    <label class="form-label">Status Aktif</label>

                    <select name="status_aktif" class="form-control">

                        <option value="1"
                            {{ old('status_aktif', $karyawan->status_aktif) == 1 ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="0"
                            {{ old('status_aktif', $karyawan->status_aktif) == 0 ? 'selected' : '' }}>
                            Tidak Aktif
                        </option>

                    </select>
                </div>

                {{-- BUTTON --}}
                <div class="d-flex justify-content-between">
                    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection