<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // 💡 Necessário para usar DB::statement() e DB::getDriverName()
use Illuminate\Support\Facades\Schema; // 💡 Necessário para usar Schema::disableForeignKeyConstraints()
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Executa o seeder para criar os cursos iniciais usando inserção em massa (DB::table).
     * @return void
     */
    public function run(): void
    {
        // 1. DEFINIÇÃO DE VARIÁVEIS AUSENTES (CORREÇÃO DO ERRO 'Undefined variable')
        $fixedTsiId = 'f76d9070-179f-432d-905c-d232a52f9b2d'; // ID UUID fixo
        $timestamp = now(); // Define o timestamp atual para os campos created_at/updated_at
        
        // 2. CORREÇÃO DE CHAVES ESTRANGEIRAS (Melhor prática Laravel)
        // Desativa as restrições de chave estrangeira
        Schema::disableForeignKeyConstraints();

        // 3. LIMPEZA DA TABELA (Importante para rodar o seeder mais de uma vez)
        DB::table('courses')->truncate();

        // 📚 Todos os cursos a serem inseridos
        $coursesData = [
            // 1. O curso com ID FIXO (CRÍTICO)
            [
                'id' => $fixedTsiId, // A variável agora está definida!
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
        
        // 4. ATIVAÇÃO DE CHAVES ESTRANGEIRAS
        // Ativa novamente as restrições de chave estrangeira
        Schema::enableForeignKeyConstraints();

        $this->command->info("✅ Foram inseridos " . count($dataToInsert) . " cursos (incluindo o ID fixo $fixedTsiId) através da inserção em massa.");
    }
}