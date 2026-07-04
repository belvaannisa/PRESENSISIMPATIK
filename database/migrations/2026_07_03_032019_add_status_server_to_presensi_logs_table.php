<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi_logs', function (Blueprint $table) {

            $table->enum(
                'status_server',
                [
                    'pending',
                    'success',
                    'failed'
                ]
            )
            ->default('pending')
            ->after('status_sinkron');

        });
    }

    public function down(): void
    {
        Schema::table('presensi_logs', function (Blueprint $table) {

            $table->dropColumn('status_server');

        });
    }
};