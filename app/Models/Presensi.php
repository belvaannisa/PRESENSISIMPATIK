<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use HasFactory;

    protected $table = 'presensis';

    protected $fillable = [
<<<<<<< HEAD
        'karyawan_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',

        'diedit_oleh',
        'waktu_edit',
    ];

=======
    'karyawan_id',
    'tanggal',
    'jam_masuk',
    'jam_keluar',
    'status',
    'keterangan',
    'sumber',
];
>>>>>>> 71de79433b8e2935c82992c92ca5494fc34238db
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