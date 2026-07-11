<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
    'pin',
    'nama',
    'jabatan',
    'no_hp',
    'tanggal_masuk',
    'jam_masuk',
    'jam_keluar',
    'tipe_jam_keluar',
    'status_aktif',
    'sinkron_fingerprint'
];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'status_aktif' => 'boolean',
        'jam_keluar' => 'datetime:H:i',
    ];

    public static $jabatanList = [
        'KEPALA CABANG',
        'HAF',
        'KEPALA GUDANG & KEPALA PERSONALIA', 
        'KASIR',
        'KOORD AR',
        'ADM AR',
        'KORWIL BJB',
        'COLLECTOR',
        'KANVAS DRIVER',
        'DRIVER GUDANG',
        'HELPER',
        'OFFICE BOY CABANG',
        'SPV SR BJB',
        'SPV SF BERLIAN',
        'SR BJB',
        'SF BERLIAN'
    ];

    // RELASI KE PRESENSI
public function presensis()
{
    return $this->hasMany(Presensi::class);
}

public function presensiLogs()
{
    return $this->hasMany(PresensiLog::class);
}
}