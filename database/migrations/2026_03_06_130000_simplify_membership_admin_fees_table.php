<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_admin_fees')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `membership_admin_fees` DROP FOREIGN KEY `membership_admin_fees_membership_id_foreign`');
        } catch (\Throwable $th) {
            // ignore if foreign key does not exist
        }

        try {
            DB::statement('ALTER TABLE `membership_admin_fees` DROP INDEX `membership_admin_fees_membership_type_unique`');
        } catch (\Throwable $th) {
            // ignore if index does not exist
        }

        try {
            DB::statement('ALTER TABLE `membership_admin_fees` DROP INDEX `membership_admin_fees_membership_active_index`');
        } catch (\Throwable $th) {
            // ignore if index does not exist
        }

        Schema::table('membership_admin_fees', function (Blueprint $table) {
            $drops = [];
            foreach ([
                'membership_id',
                'is_required',
                'apply_on_registration',
                'apply_on_expired_member',
                'apply_on_renewal',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('membership_admin_fees', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('membership_admin_fees')) {
            return;
        }

        Schema::table('membership_admin_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_admin_fees', 'membership_id')) {
                $table->unsignedBigInteger('membership_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('membership_admin_fees', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('admin_fee');
            }
            if (!Schema::hasColumn('membership_admin_fees', 'apply_on_registration')) {
                $table->boolean('apply_on_registration')->default(true)->after('is_required');
            }
            if (!Schema::hasColumn('membership_admin_fees', 'apply_on_expired_member')) {
                $table->boolean('apply_on_expired_member')->default(true)->after('apply_on_registration');
            }
            if (!Schema::hasColumn('membership_admin_fees', 'apply_on_renewal')) {
                $table->boolean('apply_on_renewal')->default(false)->after('apply_on_expired_member');
            }
            if (!Schema::hasColumn('membership_admin_fees', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('apply_on_renewal');
            }
        });
    }
};

