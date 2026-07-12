@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <div class="card shadow-sm border-0">

        <div class="card-header text-white"
             style="background-color:#FA713F;">
            <h5 class="mb-0">Edit Presensi</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('presensi.update', $presensi->id) }}"
                  method="POST">

                @csrf
                @method('PUT')

                {{-- Jam Masuk --}}
                <div class="mb-3">
                    <label class="form-label">
                        Jam Masuk
                    </label>

                    <input type="time"
                           name="jam_masuk"
                           class="form-control"
                           value="{{ old('jam_masuk', $presensi->jam_masuk) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Jam Keluar
                    </label>

                    <input
                        type="time"
                        name="jam_keluar"
                        class="form-control"
                        value="{{ old('jam_keluar', $presensi->jam_keluar) }}">
                </div>

                {{-- STATUS --}}
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text"
                            class="form-control"
                            value="{{ $presensi->status }}"
                            readonly>
                    </div>

               
                <div class="d-flex justify-content-between">

                    <a href="{{ route('presensi.index') }}"
                       class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit"
                            class="btn btn-success">
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@endsection