<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBayarToPenyewaansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penyewaans', function (Blueprint $table) {
            $table->double('bayar');
            $table->double('kembali');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penyewaans', function (Blueprint $table) {
            $table->dropColumn('bayar');
            $table->dropColumn('kembali');
        });
    }
}
