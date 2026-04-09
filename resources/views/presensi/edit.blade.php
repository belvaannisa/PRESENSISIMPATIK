@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Presensi</h2>

    <form action="{{ route('presensi.update', $presensi->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>ID Karyawan</label>
            <input type="text" name="karyawan_id" class="form-control" value="{{ $presensi->karyawan_id }}">
        </div>

        <div class="mb-3">
            <label>Tanggal</label>
            <input type="text" name="tanggal" class="form-control" value="{{ $presensi->tanggal }}">
        </div>

        <div class="mb-3">
            <label>Jam Masuk</label>
            <input type="time" 
                name="jam_masuk" 
                class="form-control"
                value="{{ old('jam_masuk', isset($presensi->jam_masuk) ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i') : '') }}">
        </div>

        <div class="mb-3">
            <label>Jam Keluar</label>
            <input type="time" 
                name="jam_keluar" 
                class="form-control"
                value="{{ old('jam_keluar', isset($presensi->jam_keluar) ? \Carbon\Carbon::parse($presensi->jam_keluar)->format('H:i') : '') }}">
        </div>

        <div class="mb-3">
            <label>Status</label>
            <input type="text" name="status" class="form-control" value="{{ $presensi->status }}">
        </div>

        <div class="mb-3">
            <label>Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="{{ $presensi->keterangan }}">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection