<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah enum menjadi string agar metode pembayaran fleksibel mengikuti konfigurasi aplikasi.
        DB::statement("ALTER TABLE transactions MODIFY metode VARCHAR(30) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY metode ENUM('cash','debit','kredit','qris','transfer') NULL");
    }
};

