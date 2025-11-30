<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * O seeder principal não cria dados diretamente, mas sim chama outros seeders.
     * A ordem de chamada é crucial para satisfazer as restrições de chaves estrangeiras.
     */
    public function run(): void
    {
        // Ao usar $this->call(), garantimos a ordem de execução:
        // 1. CourseSeeder (Cria o registro PAI 'courses' - essencial para a FK)
        // 2. UserEnrollmentSeeder (Cria o registro FILHO 'users' e 'enrollments' que referenciam o curso)
        
        // 🛑 A criação manual de Admin, Staff e Student FOI REMOVIDA desta versão. 🛑
        
        $this->call([
            CourseSeeder::class,
            UserEnrollmentSeeder::class,
            // Adicione outros seeders que você criou aqui, seguindo a ordem de dependência:
            // TypeRequestSeeder::class,
            // TypeDocumentSeeder::class,
        ]);
    }
}