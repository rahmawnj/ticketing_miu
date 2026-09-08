<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now('Asia/Jakarta');

        $defaults = [
            'renewal_notice_club_name' => 'MIU',
            'renewal_notice_bank_account' => 'TRANSFER BANK: BCA 0289011155 A/N PT KARTUNINDO PERKASA ABADI',
            'renewal_notice_admin_phone' => '0821 2222 9358',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'renewal_notice_club_name',
                'renewal_notice_bank_account',
                'renewal_notice_admin_phone',
            ])
            ->delete();
    }
};


