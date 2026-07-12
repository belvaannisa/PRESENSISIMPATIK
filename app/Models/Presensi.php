<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'presensis';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
        'sumber',
    ];

    /**
     * Relasi ke karyawan (banyak presensi milik 1 karyawan)
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * Relasi ke user yang melakukan edit terakhir
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diedit_oleh');
    }
}
