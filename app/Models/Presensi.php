<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use HasFactory;

    protected $table = 'presensis';

    protected $fillable = [

        'karyawan_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
        'sumber',

        'diedit_oleh',
        'waktu_edit',
    ];


    /**
     * Relasi ke karyawan (banyak presensi milik 1 karyawan)
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class,'diedit_oleh');
    }
}