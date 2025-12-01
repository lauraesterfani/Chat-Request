<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // 💡 Necessário para usar DB::table()
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Executa o seeder para criar os cursos iniciais usando inserção em massa (DB::table).
     * @return void
     */
    public function run(): void
    {
        // 1. 🛑 NOVO: Desativa a verificação de chaves estrangeiras para permitir o TRUNCATE
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 🚨 CRÍTICO: Limpa a tabela para evitar o erro UniqueConstraintViolationException
        // Isto só é possível porque a verificação foi desativada acima.
        DB::table('courses')->truncate(); 

        // 2. 🟢 NOVO: Reativa a verificação de chaves estrangeiras após a limpeza
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🎯 ID FIXO CRÍTICO - Usado no frontend (SignupPage.jsx) para simulação/validação
        $fixedTsiId = '9b1c5e0d-9b1c-4c2a-8b3d-7f4c5e0d9b1a'; 
        $timestamp = now();

        // 📚 Todos os cursos a serem inseridos
        $coursesData = [
            // 1. O curso com ID FIXO (CRÍTICO)
            [
                'id' => $fixedTsiId,
                'name' => 'Tecnologia em Sistemas para Internet (TSI)', 
                'code' => 'TSI-2025',
                'is_active' => true,
            ],
            // 2. Os demais cursos com IDs gerados automaticamente
            [
                'id' => (string) Str::uuid(),
                'name' => 'Logística', 
                'code' => 'LOG',
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Informática para Internet (IPI)', 
                'code' => 'IPI',
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Administração', 
                'code' => 'ADM',
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Gestão da Qualidade', 
                'code' => 'GQ',
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Análise e Desenvolvimento de Sistemas', 
                'code' => 'ADS-2025',
                'is_active' => true,
            ],
        ];
        
        // 🔄 Formata os dados para inserção em massa com DB::table, adicionando timestamps
        $dataToInsert = array_map(function($course) use ($timestamp) {
            return array_merge($course, [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }, $coursesData);


        // 💾 Insere todos os dados de uma vez
        DB::table('courses')->insert($dataToInsert);

        $this->command->info("✅ Foram inseridos " . count($dataToInsert) . " cursos (incluindo o ID fixo $fixedTsiId) através da inserção em massa.");
    }
}