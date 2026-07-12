<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('presensis', function (Blueprint $table) {

            $table->unsignedBigInteger('diedit_oleh')->nullable();

            $table->timestamp('waktu_edit')->nullable();

        });
    }

    public function down()
    {
        Schema::table('presensis', function (Blueprint $table) {

            $table->dropColumn([
                'diedit_oleh',
                'waktu_edit'
            ]);

        });
    }
};
