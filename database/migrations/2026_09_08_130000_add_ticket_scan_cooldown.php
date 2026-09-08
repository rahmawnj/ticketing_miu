<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('detail_transactions', 'last_scanned_at')) {
            Schema::table('detail_transactions', function (Blueprint $table) {
                $table->timestamp('last_scanned_at')->nullable()->after('scanned_at');
            });
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'ticket_scan_cooldown_seconds'],
            ['value' => '120']
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'ticket_scan_cooldown_seconds')
            ->delete();

        if (Schema::hasColumn('detail_transactions', 'last_scanned_at')) {
            Schema::table('detail_transactions', function (Blueprint $table) {
                $table->dropColumn('last_scanned_at');
            });
        }
    }
};
