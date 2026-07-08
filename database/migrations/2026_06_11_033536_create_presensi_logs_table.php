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

      $table->foreignId('karyawan_id')
      ->nullable()
      ->constrained('karyawans')
      ->nullOnDelete();

    $table->string('pin')->nullable();

    $table->string('nama')->nullable();

    $table->date('tanggal');

    $table->time('jam');

    $table->string('verify_code')->nullable();
   $table->string('record_hash', 64)
          ->unique();

    $table->enum('status_sinkron', [
        'pending',
        'matched',
        'unmatched'
    ])->default('pending');

    $table->enum(
                'status_server',
                [
                    'pending',
                    'success',
                    'failed'
                ]
    )
            ->default('pending');

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
