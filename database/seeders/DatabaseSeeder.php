<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chama os seeders na ordem correta
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}