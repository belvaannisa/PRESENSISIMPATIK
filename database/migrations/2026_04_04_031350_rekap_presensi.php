<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_presensis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('karyawan_id')
                  ->constrained('karyawans')
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('bulan'); // 1 - 12
            $table->year('tahun');

            $table->unsignedInteger('hadir')->default(0);
            $table->unsignedInteger('izin')->default(0);
            $table->unsignedInteger('sakit')->default(0);
            $table->unsignedInteger('alpha')->default(0);

            $table->timestamps();

            // 1 karyawan hanya 1 rekap per bulan
            $table->unique(['karyawan_id', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
