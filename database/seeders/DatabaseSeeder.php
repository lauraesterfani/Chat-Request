<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpeza Centralizada
        Schema::disableForeignKeyConstraints();
        DB::table('requerimentos')->truncate();
        DB::table('requerimento_anexos_exigidos')->truncate();
        DB::table('matriculas')->truncate();
        DB::table('alunos')->truncate();
        DB::table('tipos_requerimento')->truncate();
        DB::table('tipos_anexo')->truncate();
        DB::table('campus')->truncate();
        DB::table('cursos')->truncate();
        DB::table('admins')->truncate();
        
        Schema::enableForeignKeyConstraints();

        // 2. Ordem de Execução dos Seeders
        $this->call([
            // Tabelas de domínio primárias
            CursoSeeder::class,
            CampusSeeder::class,
            TipoAnexoSeeder::class,
            TipoRequerimentoSeeder::class,
            
            // Tabela de ligação (pivô)
            RequerimentoAnexosExigidosSeeder::class,

            // Tabelas com dados de exemplo (em ordem de dependência)
            AlunoSeeder::class,
            MatriculaSeeder::class,      // <<< NOVO
            RequerimentoSeeder::class,   // <<< NOVO
            AdminSeeder::class,
        ]);
    }
}