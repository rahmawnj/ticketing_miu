<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
