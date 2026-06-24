<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('karyawan_id')
                  ->constrained('karyawans')
                  ->onDelete('cascade');

            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();

            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->unique(['karyawan_id', 'tanggal']);

            $table->enum('sumber', [
                'fingerprint',
                'manual'
            ])->default('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensis');
    }
};