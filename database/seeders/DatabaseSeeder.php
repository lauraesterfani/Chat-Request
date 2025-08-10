<?php

namespace Database\Seeders;

// Nenhuma declaração 'use' para os seus seeders é necessária aqui.
// Apenas para classes de fora do namespace, como 'Seeder'.
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Esta chamada agora funcionará corretamente.
        $this->call([
            CoursesTableSeeder::class,
            UsersTableSeeder::class,
            EnrollmentsTableSeeder::class,
            RequestsTableSeeder::class,
        ]);
    }
}