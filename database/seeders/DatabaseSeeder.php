<?php

namespace Database\Seeders;

use App\Models\karyawan;
use Database\Seeders\DataKaryawanSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        DataKaryawanSeeder::class,
    ]);
    }

}
