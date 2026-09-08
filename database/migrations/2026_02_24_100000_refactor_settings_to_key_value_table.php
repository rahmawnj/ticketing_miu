<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        if (Schema::hasColumn('settings', 'key') && Schema::hasColumn('settings', 'value')) {
            return;
        }

        $legacy = DB::table('settings')->orderBy('id')->first();
        $now = now();

        if (Schema::hasTable('settings_legacy')) {
            Schema::drop('settings_legacy');
        }

        Schema::rename('settings', 'settings_legacy');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $pairs = [
            'name' => $legacy->name ?? null,
            'logo' => $legacy->logo ?? null,
            'ucapan' => $legacy->ucapan ?? null,
            'deskripsi' => $legacy->deskripsi ?? null,
            'ppn' => (string) ((int) ($legacy->ppn ?? 0)),
            'member_suspend_before_days' => (string) max((int) ($legacy->member_reminder_days ?? 7), 1),
            'member_suspend_after_days' => (string) max((int) ($legacy->member_delete_grace_days ?? 30), 1),
            'print_mode' => $legacy->print_mode ?? 'per_qty',
            'dashboard_metric_mode' => $legacy->dashboard_metric_mode ?? 'amount',
            'whatsapp_enabled' => (string) ((int) ($legacy->whatsapp_enabled ?? 0)),
            'use_logo' => (string) ((int) ($legacy->use_logo ?? 0)),
        ];

        $rows = [];
        foreach ($pairs as $key => $value) {
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('settings')->insert($rows);
    }

    public function down(): void
    {
        // Irreversible safely, because key-value rows cannot be mapped back reliably to old columns.
    }
};
