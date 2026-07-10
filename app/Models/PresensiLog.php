<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PresensiLog extends Model
{
    
   protected $fillable = [
    'record_hash',
    'pin',
    'nama',
    'tanggal',
    'jam',
    'verify_code',
    'karyawan_id',
    'status_sinkron',
    'status_server',
    'catatan'
];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

}
