<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardMetricModeToSettingsTable extends Migration
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
            $table->string('dashboard_metric_mode', 20)
                ->default('amount')
                ->after('print_mode');
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
            $table->dropColumn('dashboard_metric_mode');
        });
    }
}
