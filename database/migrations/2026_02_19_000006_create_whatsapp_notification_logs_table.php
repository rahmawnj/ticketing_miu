<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappNotificationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // renewal_reminder, invoice
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('recipient_phone', 25);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->text('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('member_id');
            $table->index('transaction_id');
            $table->index('recipient_phone');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_notification_logs');
    }
}

