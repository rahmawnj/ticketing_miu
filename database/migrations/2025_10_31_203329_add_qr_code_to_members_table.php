<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrCodeToMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->bigInteger('membership_id')->after('id')->default(0)->nullable();
            $table->string("qr_code")->nullable()->after("saldo");
            $table->string("image_profile")->nullable()->after("saldo");
            $table->string("jenis_kelamin")->nullable()->after("saldo");
            $table->integer("is_active")->default(0)->after("saldo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('membership_id');
            $table->dropColumn("qr_code");
            $table->dropColumn("image_profile");
            $table->dropColumn("jenis_kelamin");
            $table->dropColumn("is_active");
        });
    }
}
