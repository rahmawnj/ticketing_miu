<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah enum lama (Tap/Cash) menjadi string agar mendukung metode pembayaran baru.
        DB::statement("ALTER TABLE penyewaans MODIFY metode VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE penyewaans MODIFY metode ENUM('Tap','Cash') NOT NULL");
    }
};

