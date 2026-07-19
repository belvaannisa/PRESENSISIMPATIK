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
 <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="background-color: #FA713F;"> 
 <h5 class="mb-0">Data Presensi Bulanan</h5> 
 <form action="{{ route('presensi.import') }}" method="POST"> 
 @csrf 
 <button type="submit" class="btn btn-success btn-sm">⭐ Auto Import</button> 
 </form> 
 </div> 
 <div class="card-body"> 
 <form action="{{ route('presensi.upload') }}" method="POST" enctype="multipart/form-data" class="mb-3 d-flex flex-column flex-lg-row gap-2"> 
 @csrf 
 <input type="file" name="file" class="form-control" required> 
 <button class="btn btn-primary">Upload CSV</button> 
 </form> 
 <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"> 
 <div class="text-muted small">
 Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</strong>
 </div>
 <form action="" method="GET" class="d-flex"> 
 <input type="month" name="bulan" class="form-control me-2" value="{{ request('bulan', $bulan) }}"> 
 <button class="btn btn-outline-secondary">Filter</button> 
 </form> 
 </div> 
 <div class="alert alert-warning py-2 mb-3"> 
 <strong>Keterangan :</strong> Baris Berwarna Kuning = Data Diedit. Status Terlambat & Tidak Absen Pagi = Pemotongan Insentif. 
 </div> 

 {{-- ================= DESKTOP TABLE ================= --}} 
 <div class="table-responsive d-none d-lg-block"> 
 <table class="table table-bordered table-striped table-hover align-middle"> 
 <thead class="text-center text-white" style="background-color: #FA713F;"> 
 <tr> 
 <th>No</th> 
 <th>Nama</th> 
 <th>Tanggal / Periode</th> 
 <th>Masuk (Hadir)</th> 
 <th>Keluar (Mangkir)</th> 
 <th>Status</th> 
 <th>Keterangan</th> 
 <th>Log Edit / Insentif</th> 
 <th width="180">Aksi</th> 
 </tr> 
 </thead> 
 <tbody> 
 {{-- ------------------ KELOMPOK STAFF ------------------ --}}
 <tr class="table-dark text-white fw-bold"><td colspan="9">KARYAWAN STAFF</td></tr>
 @php $noStaff = 1; @endphp
 @forelse ($rekapStaff as $p) 
 <tr> 
 <td class="text-center">{{ $noStaff++ }}</td> 
 <td>{{ $p['nama'] }}</td> 
 <td class="text-center">{{ \Carbon\Carbon::parse($startDate)->format('d/m') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m') }}</td> 
 <td class="text-center">{{ $p['hadir'] }} Hari</td> 
 <td class="text-center">{{ $p['ketidakhadiran'] }} Hari</td> 
 <td class="text-center"> 
 @if($p['keterangan'] == 'Disiplin') 
 <span class="badge bg-success">Disiplin</span> 
 @else 
 <span class="badge bg-warning text-dark">Kurang Disiplin</span> 
 @endif 
 </td> 
 <td class="text-center"><span class="badge bg-info">{{ $p['persen'] }}%</span></td> 
 <td class="text-center"> 
 <strong>Rp {{ number_format($p['insentif'], 0, ',', '.') }}</strong>
 </td> 
 <td class="text-center"> 
 <span class="text-muted small">Mode Bulanan</span>
 </td> 
 </tr> 
 @empty 
 <tr><td colspan="9" class="text-center text-muted">Data Staff Belum Tersedia</td></tr> 
 @endforelse 

 {{-- ------------------ KELOMPOK NON-STAFF ------------------ --}}
 <tr class="table-dark text-white fw-bold"><td colspan="9">KARYAWAN NON-STAFF</td></tr>
 @php $noNonStaff = 1; @endphp
 @forelse ($rekapNonStaff as $p) 
 <tr> 
 <td class="text-center">{{ $noNonStaff++ }}</td> 
 <td>{{ $p['nama'] }}</td> 
 <td class="text-center">{{ \Carbon\Carbon::parse($startDate)->format('d/m') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m') }}</td> 
 <td class="text-center">{{ $p['hadir'] }} Hari</td> 
 <td class="text-center">{{ $p['ketidakhadiran'] }} Hari</td> 
 <td class="text-center"> 
 @if($p['keterangan'] == 'Disiplin') 
 <span class="badge bg-success">Disiplin</span> 
 @else 
 <span class="badge bg-warning text-dark">Kurang Disiplin</span> 
 @endif 
 </td> 
 <td class="text-center"><span class="badge bg-info">{{ $p['persen'] }}%</span></td> 
 <td class="text-center"> 
 <strong>Rp {{ number_format($p['insentif'], 0, ',', '.') }}</strong>
 </td> 
 <td class="text-center"> 
 <span class="text-muted small">Mode Bulanan</span>
 </td> 
 </tr> 
 @empty 
 <tr><td colspan="9" class="text-center text-muted">Data Non-Staff Belum Tersedia</td></tr> 
 @endforelse 
 </tbody> 
 </table> </div> 

 {{-- ================= MOBILE CARD ================= --}} 
 <div class="d-lg-none"> 
 {{-- MOBILE STAFF --}}
 <div class="alert alert-secondary py-1 fw-bold mb-2 small shadow-sm">KARYAWAN STAFF</div>
 @forelse ($rekapStaff as $p) 
 <div class="card mb-3 shadow-sm border-0"> 
 <div class="card-body"> 
 <div class="d-flex justify-content-between"> 
 <strong>{{ $p['nama'] }}</strong> 
 <span class="text-muted small">{{ \Carbon\Carbon::parse($startDate)->format('d/m') }}-{{ \Carbon\Carbon::parse($endDate)->format('d/m') }}</span> 
 </div> 
 <hr class="my-2"> 
 <div class="row small"> 
 <div class="col-6"><strong>Masuk:</strong><br>{{ $p['hadir'] }} Hari</div> 
 <div class="col-6"><strong>Keluar:</strong><br>{{ $p['ketidakhadiran'] }} Hari</div> 
 </div> 
 <div class="mt-2"> 
 <strong>Status:</strong><br> 
 @if($p['keterangan'] == 'Disiplin') 
 <span class="badge bg-success">Disiplin</span> 
 @else 
 <span class="badge bg-warning text-dark">Kurang Disiplin</span> 
 @endif 
 </div> 
 <div class="mt-3 d-flex justify-content-between align-items-center small border-top pt-2"> 
 <strong>Insentif:</strong>
 <span class="text-success fw-bold">Rp {{ number_format($p['insentif'], 0, ',', '.') }}</span>
 </div> 
 </div> </div> 
 @empty 
 <div class="text-center text-muted small mb-3">Data Staff Belum Tersedia</div> 
 @endforelse 

 {{-- MOBILE NON-STAFF --}}
 <div class="alert alert-secondary py-1 fw-bold mb-2 small shadow-sm">KARYAWAN NON-STAFF</div>
 @forelse ($rekapNonStaff as $p) 
 <div class="card mb-3 shadow-sm border-0"> 
 <div class="card-body"> 
 <div class="d-flex justify-content-between"> 
 <strong>{{ $p['nama'] }}</strong> 
 <span class="text-muted small">{{ \Carbon\Carbon::parse($startDate)->format('d/m') }}-{{ \Carbon\Carbon::parse($endDate)->format('d/m') }}</span> 
 </div> 
 <hr class="my-2"> 
 <div class="row small"> 
 <div class="col-6"><strong>Masuk:</strong><br>{{ $p['hadir'] }} Hari</div> 
 <div class="col-6"><strong>Keluar:</strong><br>{{ $p['ketidakhadiran'] }} Hari</div> 
 </div> 
 <div class="mt-2"> 
 <strong>Status:</strong><br> 
 @if($p['keterangan'] == 'Disiplin') 
 <span class="badge bg-success">Disiplin</span> 
 @else 
 <span class="badge bg-warning text-dark">Kurang Disiplin</span> 
 @endif 
 </div> 
 <div class="mt-3 d-flex justify-content-between align-items-center small border-top pt-2"> 
 <strong>Insentif:</strong>
 <span class="text-success fw-bold">Rp {{ number_format($p['insentif'], 0, ',', '.') }}</span>
 </div> 
 </div> </div> 
 @empty 
 <div class="text-center text-muted small">Data Non-Staff Belum Tersedia</div> 
 @endforelse 
 </div> 
 
 {{-- Tombol links() dihilangkan sepenuhnya di sini karena tidak menggunakan pagination --}}

 </div> </div> </div> 
@endsection
