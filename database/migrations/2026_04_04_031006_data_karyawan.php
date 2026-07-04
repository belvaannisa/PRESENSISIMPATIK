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

    $table->string('pin')->nullable()->unique();

    $table->string('nama');

    $table->string('nama_mesin')->nullable();

    $table->string('jabatan')->nullable();

    $table->string('no_hp')->nullable();
    $table->text('alamat')->nullable();
    $table->string('email')->nullable()->unique();

    $table->date('tanggal_masuk')->nullable();

    $table->time('jam_masuk')
          ->default('08:00:00');

    $table->time('jam_keluar')
          ->nullable()
          ->default('17:00:00');
          

    $table->time('batas_terlambatan')
          ->default('08:15:00');

    $table->enum('tipe_jam_keluar', [
        'Terbatas',
        'Tidak Terbatas'
    ])->default('Terbatas');

    $table->boolean('status_aktif')
          ->default(true);

    $table->boolean('sinkron_fingerprint')
          ->default(true);

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