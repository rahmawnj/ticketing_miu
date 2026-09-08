<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrintModeToSettingsTable extends Migration
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
            $table->string('print_mode', 20)->default('per_qty');
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
            $table->dropColumn('print_mode');
        });
    }
}
