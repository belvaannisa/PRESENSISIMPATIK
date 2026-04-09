<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'jabatan',
        'no_hp',
        'alamat',
        'email',
        'tanggal_masuk',
        'status_aktif'
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'status_aktif' => 'boolean',
    ];

    public static $jabatanList = [
        'Kepala Cabang',
        'Kepala Keuangan',
        'Kepala Gudang',
        'Kepala Personalia',
        'Kepala Account Receivable',
        'Kepala Marketing',
        'Admin Collection',
        'Kasir dan Fakturisasi',
        'Surveyor',
        'Sales',
        'Pengiriman',
        'Helper Pengiriman',
        'Driver',
        'Office Boy'
    ];
}