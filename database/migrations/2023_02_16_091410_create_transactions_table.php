<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->default(0);
            $table->foreignId('user_id')->constrained('users');
            $table->integer('no_trx');
            $table->string('ticket_code');
            $table->enum('transaction_type', ['renewal', 'ticket', 'registration', 'rental'])->default('ticket');            $table->enum('tipe', ['group', 'individual'])->default('group');
            $table->integer('amount')->default(0);
            $table->integer('amount_scanned')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->integer('gate')->nullable();
            $table->integer('is_active')->default(0);
        $table->decimal('ppn', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
