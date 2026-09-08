<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTimeFieldsFromSewaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sewa', function (Blueprint $table) {
            if (Schema::hasColumn('sewa', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('sewa', 'end_time')) {
                $table->dropColumn('end_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sewa', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('device');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }
}
