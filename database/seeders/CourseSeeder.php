<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course; 
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Executa o seeder para criar um curso padrão necessário para as chaves estrangeiras.
     * @return void
     */
    public function run(): void
    {
        // ID fixo exigido pela tabela 'users' para a chave estrangeira 'course_id'.
        $courseId = '8e6a2b9f-d0e5-4c1a-8b3d-7f4c5e0d9b1a'; 

        // Cria o curso se ele não existir
        if (!Course::where('id', $courseId)->exists()) {
            Course::create([
                'id' => $courseId,
                'name' => 'Análise e Desenvolvimento de Sistemas',
                'code' => 'ADS-2025',
                // 'description' foi removido daqui porque a coluna não existe na tabela 'courses'.
            ]);

            $this->command->info('Curso padrão semeado com sucesso.');
        } else {
            $this->command->info('Curso padrão já existe.');
        }
    }
}