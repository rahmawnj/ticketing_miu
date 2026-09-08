<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketCodeToDetailTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_transactions', function (Blueprint $table) {
            $table->string('ticket_code')->nullable();
            $table->enum('status', ['open', 'close'])->default('open');
            $table->integer('scanned')->default(0);
            $table->integer('gate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_transactions', function (Blueprint $table) {
            $table->dropColumn('ticket_code');
            $table->dropColumn('status');
            $table->dropColumn('scanned');
            $table->dropColumn('gate');
        });
    }
}
