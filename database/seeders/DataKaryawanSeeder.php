<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Karyawan;

class DataKaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            ['pin' => 295, 'nama' => 'ABDULLAH'],
            ['pin' => 289, 'nama' => 'CHANDRA'],
            ['pin' => 581, 'nama' => 'ELSA APRILIANI'],
            ['pin' => 304, 'nama' => 'FAUZI EFFENDI'],
            ['pin' => 593, 'nama' => 'GUSTI IRHAMSYAH'],
            ['pin' => 300, 'nama' => 'HELIANA'],
            ['pin' => 272, 'nama' => 'INDRA KUSUMA'],
            ['pin' => 298, 'nama' => 'ISTRI UTAMI'],
            ['pin' => 297, 'nama' => 'JUNIASTUTI'],
            ['pin' => 327, 'nama' => 'M.ATHAILLAH'],
            ['pin' => 333, 'nama' => 'MUHAMMAD AMIN'],
            ['pin' => 334, 'nama' => 'NINA SUSANTI'],
            ['pin' => 337, 'nama' => 'PANI'],
            ['pin' => 287, 'nama' => 'RAFIQAL'],
            ['pin' => 271, 'nama' => 'RAHIMAH'],
            ['pin' => 411, 'nama' => 'RIKA YANTI'],
            ['pin' => 305, 'nama' => 'ROSITA'],
            ['pin' => 285, 'nama' => 'RUSMIKA'],
            ['pin' => 311, 'nama' => 'SCOR YAN CHANDRA'],
            ['pin' => 313, 'nama' => 'SRI HB'],
            ['pin' => 328, 'nama' => 'SRI MULIA'],
            ['pin' => 275, 'nama' => 'WAWAN SETIAWAN'],
            ['pin' => 276, 'nama' => 'YULIANA'],

        ];

        foreach ($data as $item) {

            Karyawan::create([
                'pin' => $item['pin'],
                'nama' => $item['nama'],
                'jabatan' => 'Belum Ditentukan',
                'no_hp' => null,
                'alamat' => null,
                'email' => null,
                'status_aktif' => true,
                'tipe_jam_keluar' => 'terbatas',
                'jam_keluar' => '17:00:00',
            ]);

        }
    }
}