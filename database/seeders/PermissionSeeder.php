<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = ["Master Access", "User Access", "Ticket Access", "Sewa Access", "Member Access", "Transaction Access", "Penyewaan Access", 'Topup Access', "Report Access", "Report Transaction Access", "Report Penyewaan Access", "Transaction Delete", "Penyewaan Delete", "Topup Delete", "Management Access"];

        $perm = [];

        foreach ($permissions as $permission) {
            $permi[] = Permission::create([
                'name' => Str::slug($permission),
                'guard_name' => 'web'
            ])->id;
        }

        $role = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web'
        ]);

        $role->syncPermissions($permi);
    }
}
