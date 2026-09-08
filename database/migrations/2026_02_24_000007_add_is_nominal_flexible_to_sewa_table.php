<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsNominalFlexibleToSewaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sewa', function (Blueprint $table) {
            if (!Schema::hasColumn('sewa', 'is_nominal_flexible')) {
                // 0 = pakai nominal pokok dari master, 1 = nominal diinput saat transaksi lainnya
                $table->boolean('is_nominal_flexible')->default(false)->after('harga');
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
            if (Schema::hasColumn('sewa', 'is_nominal_flexible')) {
                $table->dropColumn('is_nominal_flexible');
            }
        });
    }
}

