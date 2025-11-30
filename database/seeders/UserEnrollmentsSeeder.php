<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Este seeder principal chama outros seeders para popular o banco de dados.
     */
    public function run(): void
    {
        // Chama os seeders na ordem correta, garantindo que as tabelas 
        // referenciadas existam antes de serem usadas.
        $this->call([
            // 1. Cria a tabela de Cursos para que o Course_id possa ser referenciado
            CourseSeeder::class,

            // 2. Cria os tipos de dados básicos (se existirem)
            // TypeRequestSeeder::class,
            // TypeDocumentSeeder::class,

            // 3. Cria um usuário de teste (Aluno, Staff, ou Admin, com Matrícula/Enrollment)
            UserEnrollmentSeeder::class,

            // 4. Cria outros dados relacionados (se existirem)
            // EnrollmentSeeder::class,
            // RequestSeeder::class,
            // DocumentSeeder::class,
        ]);
    }
}