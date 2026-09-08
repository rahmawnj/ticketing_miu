<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_admin_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('admin_type', 100);
            $table->unsignedBigInteger('admin_fee')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('apply_on_registration')->default(true);
            $table->boolean('apply_on_expired_member')->default(true);
            $table->boolean('apply_on_renewal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['membership_id', 'admin_type'], 'membership_admin_fees_membership_type_unique');
            $table->index(['membership_id', 'is_active'], 'membership_admin_fees_membership_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_admin_fees');
    }
};

