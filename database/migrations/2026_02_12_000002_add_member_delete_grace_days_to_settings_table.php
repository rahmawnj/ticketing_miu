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
        if (Schema::hasColumn('settings', 'key')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->integer('member_delete_grace_days')->default(0)->after('member_reminder_days');
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
            $table->dropColumn('member_delete_grace_days');
        });
    }
};
