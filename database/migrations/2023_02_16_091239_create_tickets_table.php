<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up()
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('jenis_ticket_id')->constrained('jenis_tickets');
        $table->string('name');
        $table->integer('harga');
        $table->integer('tripod');

        // PPN settings
        $table->boolean('use_ppn')->default(false);
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
        Schema::dropIfExists('tickets');
    }
}
