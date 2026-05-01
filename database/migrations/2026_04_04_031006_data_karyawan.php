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
    Schema::create('karyawans', function (Blueprint $table) {
    $table->id();

    $table->string('nama');

    $table->enum('jabatan', [
        'Kepala Cabang',
        'Kepala Keuangan',
        'Kepala Gudang',
        'Kepala Personalia',
        'Kepala Account Receivable',
        'Kepala Marketing',
        'Admin Collection',
        'Kasir dan Fakturisasi',
        'Supervisor ',
        'Surveyor',
        'Sales',
        'Pengiriman',
        'Helper Pengiriman',
        'Driver',
        'Office Boy'
    ]);

    $table->string('no_hp')->nullable();
    $table->text('alamat')->nullable();
    $table->string('email')->unique()->nullable();

    $table->date('tanggal_masuk')->nullable();
    $table->boolean('status_aktif')->default(true);
    $table->enum('tipe_jam_keluar' , ['Terbatas', 'tidak Terbatas'])->default ('terbatas');   
    $table->time('jam keluar')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('karyawans');
    }
};
