<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWhatsappEnabledToSettingsTable extends Migration
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
            $table->boolean('whatsapp_enabled')
                ->default(false)
                ->after('dashboard_metric_mode');
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
            $table->dropColumn('whatsapp_enabled');
        });
    }
}
