<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Temporariamente desabilita a verificação de chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpa a tabela de roles para evitar duplicatas
        DB::table('roles')->truncate();

        // Insere os papéis com os IDs e nomes corretos
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'staff', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Habilita a verificação de chaves estrangeiras novamente
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
