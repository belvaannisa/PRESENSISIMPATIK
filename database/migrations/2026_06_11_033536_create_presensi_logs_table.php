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
      Schema::create('presensi_logs', function (Blueprint $table) {

    $table->id();

    $table->string('pin')->nullable();

    $table->string('nama')->nullable();

    $table->date('tanggal');

    $table->time('jam');

    $table->string('verify_code')->nullable();

    $table->unsignedBigInteger('karyawan_id')->nullable();

    $table->enum('status_sinkron', [
        'pending',
        'matched',
        'unmatched'
    ])->default('pending');

    $table->text('catatan')->nullable();

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_logs');
    }
};
