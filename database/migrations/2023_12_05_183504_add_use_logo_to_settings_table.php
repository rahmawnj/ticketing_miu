<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseLogoToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('settings', 'key')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->integer('use_logo')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('settings', 'key')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('use_logo');
        });
    }
}
