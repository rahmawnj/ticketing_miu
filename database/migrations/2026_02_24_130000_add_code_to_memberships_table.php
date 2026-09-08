<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('name');
        });

        $usedCodes = [];
        $memberships = DB::table('memberships')->select('id', 'name')->orderBy('id')->get();

        foreach ($memberships as $membership) {
            $base = strtoupper(Str::of((string) $membership->name)->replaceMatches('/[^A-Za-z0-9]+/', '')->limit(8, '')->value());
            if ($base === '') {
                $base = 'MSHIP';
            }

            $candidate = $base;
            $seq = 1;
            while (in_array($candidate, $usedCodes, true) || DB::table('memberships')->where('code', $candidate)->exists()) {
                $candidate = $base . $seq;
                $seq++;
            }

            DB::table('memberships')->where('id', $membership->id)->update(['code' => $candidate]);
            $usedCodes[] = $candidate;
        }
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};

