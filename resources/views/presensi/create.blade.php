@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Tambah Presensi</h2>

    <form action="{{ route('presensi.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>ID Karyawan</label>
            <input type="text" name="karyawan_id" class="form-control">
        </div>

        <div class="mb-3">
            <label>Tanggal</label>
            <input type="text" name="tanggal" class="form-control">
        </div>

        <div class="mb-3">
            <label>Jam Masuk</label>
            <input type="text" name="jam_masuk" class="form-control">
        </div>

         <div class="mb-3">
            <label>Jam Keluar</label>
            <input type="text" name="jam_keluar" class="form-control">
        </div>

         <div class="mb-3">
            <label>Status</label>
            <input type="text" name="status" class="form-control">
        </div>

        <div class="mb-3">
            <label>Keterangan</label>
            <input type="text" name="keterangan" class="form-control">
        </div>


        <button class="btn btn-success">Simpan</button>
    </form>
</div>
@endsection