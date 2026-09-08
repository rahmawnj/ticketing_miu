<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('nama_kartu')->nullable()->after('metode');
            $table->string('no_kartu')->nullable()->after('nama_kartu');
            $table->string('bank')->nullable()->after('no_kartu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['nama_kartu', 'no_kartu', 'bank']);
        });
    }
};
