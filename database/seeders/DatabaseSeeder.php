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
    $this->call([
        CourseSeeder::class,      // <--- ADICIONE ISTO (Cursos)
        TypeRequestSeeder::class, // <--- Este já estava (Tipos de Requerimento)
    ]);
}
}