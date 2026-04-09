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
         Schema::create('presensis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('karyawan_id')
                  ->constrained('karyawans')
                  ->cascadeOnDelete();

            $table->date('tanggal');

            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();

            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])
                  ->default('hadir');

            $table->string('keterangan')->nullable();

            $table->timestamps();

            // tidak boleh absen 2x dalam 1 hari
            $table->unique(['karyawan_id', 'tanggal']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensis');
    }
};
