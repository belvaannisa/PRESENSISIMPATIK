<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapPresensi extends Model
{
    use HasFactory;

    protected $table = 'rekap_presensis';

    protected $fillable = [
        'karyawan_id',
        'bulan',
        'tahun',
        'hadir',
        'izin',
        'sakit',
        'alpha'
    ];

    /**
     * Relasi ke karyawan
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}