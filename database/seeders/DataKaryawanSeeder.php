<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Karyawan;

class DataKaryawanSeeder extends Seeder
{
    private function getJamKerja($jabatan)
    {
        $tidakTerbatas = in_array(
            $jabatan,
            config('jabatan.jabatan_tidak_terbatas')
        );

        return [
            'tipe_jam_keluar' => $tidakTerbatas
                ? config('jabatan.tidak_terbatas')
                : config('jabatan.terbatas'),

            'jam_keluar' => $tidakTerbatas
                ? null
                : config('jabatan.jam_keluar_default'),
        ];
    }

    public function run(): void
    {
        $data = [
            [
                'pin' => 295,
                'nama' => 'ABDULLAH',
                'jabatan' => 'OFFICE BOY CABANG',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 289,
                'nama' => 'AFRIZA NUR CHANDRA',
                'jabatan' => 'HELPER',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 581,
                'nama' => 'ELSA APRILIANI',
                'jabatan' => 'KASIR',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 304,
                'nama' => 'FAUZI EFFENDI',
                'jabatan' => 'KANVAS DRIVER',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],
            
            [
                'pin' => 593,
                'nama' => 'GUSTI IRHAMSYAH',
                'jabatan' => 'COLLECTOR',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 300,
                'nama' => 'HELIANA',
                'jabatan' => 'SPV SF BERLIAN',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 272,
                'nama' => 'INDRA KUSUMA W',
                'jabatan' => 'SPV SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 298,
                'nama' => 'ISTRI UTAMI',
                'jabatan' => 'KEPALA GUDANG & KEPALA PERSONALIA',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 297,
                'nama' => 'JUNIASTUTI',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 327,
                'nama' => 'M.ATHAILLAH',
                'jabatan' => 'SF BERLIAN',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 333,
                'nama' => 'MUHAMMAD AMIN',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 334,
                'nama' => 'NINA SUSANTI',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 337,
                'nama' => 'PANI',
                'jabatan' => 'DRIVER GUDANG',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 287,
                'nama' => 'RAFIQAL',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 271,
                'nama' => 'RAHIMAH',
                'jabatan' => 'HAF',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 411,
                'nama' => 'RIKA YANTI',
                'jabatan' => 'SF BERLIAN',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 305,
                'nama' => 'ROSITA',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 285,
                'nama' => 'RUSMIKA',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 311,
                'nama' => 'SCOR YAN CHANDRA',
                'jabatan' => 'KORWIL BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 313,
                'nama' => 'SRI HAIRIYATI',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 328,
                'nama' => 'SRI MULIA',
                'jabatan' => 'SF BERLIAN',
                'no_hp' => null,

                'tanggal_masuk' => null,
            ],

            [
                'pin' => 275,
                'nama' => 'WAWAN SETIAWAN',
                'jabatan' => 'KEPALA CABANG',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 351,
                'nama' => 'SITI KHADIJAH',
                'jabatan' => 'ADM AR',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],

            [
                'pin' => 344,
                'nama' => 'NURHAYATI',
                'jabatan' => 'SR BJB',
                'no_hp' => null,
                'tanggal_masuk' => null,
            ],
        ];

       foreach ($data as $item) {

            $tidakTerbatas = in_array(
                $item['jabatan'],
                config('jabatan.jabatan_tidak_terbatas')
            );

            Karyawan::updateOrCreate(
                ['pin' => $item['pin']],
                [

                    'nama'             => $item['nama'],
                    'jabatan'          => $item['jabatan'],
                    'no_hp'            => $item['no_hp'],
                    'tanggal_masuk'    => $item['tanggal_masuk'],

                    'status_aktif'     => true,

                    'tipe_jam_keluar'  => $tidakTerbatas
                        ? config('jabatan.tidak_terbatas')
                        : config('jabatan.terbatas'),

                    'jam_keluar' => config('jabatan.jam_keluar_default'),

                ]
            );
        }
    }
}