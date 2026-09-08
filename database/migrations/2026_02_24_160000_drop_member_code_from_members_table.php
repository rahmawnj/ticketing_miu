<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('members', 'member_code')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('member_code');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('members', 'member_code')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('member_code')->nullable()->after('membership_id');
            });
        }
    }
};
