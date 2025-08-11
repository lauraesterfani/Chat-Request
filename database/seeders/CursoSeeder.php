<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curso;
use Illuminate\Support\Facades\Schema; // Importe o Schema

class CursoSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilita a verificação de chaves estrangeiras
        Schema::disableForeignKeyConstraints();

        // Limpa a tabela
        Curso::truncate();

        // Reabilita a verificação
        Schema::enableForeignKeyConstraints();

        // Insere os novos dados
        Curso::insert([
            ['nome_curso' => 'Informática para internet', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Logística', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Almoxarife', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Operador de Computador', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Gestão da Qualidade', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Tecnologia em Sistemas para Internet', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Administração', 'modalidade' => 'Presencial'],
        ]);
    }
}