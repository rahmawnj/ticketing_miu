<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGateAccessMembershipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gate_access_membership', function (Blueprint $table) {
            $table->foreignId('gate_access_id')->constrained('gate_accesses')->onDelete('cascade');
            $table->foreignId('membership_id')->constrained('memberships')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gate_access_membership');
    }
}
