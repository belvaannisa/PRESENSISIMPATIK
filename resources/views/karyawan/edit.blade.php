@extends('layouts.app')

@section('content')
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
                <div class="mb-3">
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