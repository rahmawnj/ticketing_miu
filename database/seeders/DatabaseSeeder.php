<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        // \App\Models\User::factory(500)->create();

        $this->call([
            TicketSeeder::class,
            SewaSeeder::class,
            PermissionSeeder::class
        ]);

        $admin = User::create([
            'username' => 'developer',
            'password' => bcrypt('secret'),
            'name' => 'Developer'
        ]);

        $admin->assignRole(1);
    }
}
