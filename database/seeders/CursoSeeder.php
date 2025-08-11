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
            ['nome_curso' => 'Análise e Desenvolvimento de Sistemas', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Engenharia de Software', 'modalidade' => 'Presencial'],
            ['nome_curso' => 'Gestão de Recursos Humanos', 'modalidade' => 'EAD'],
        ]);
    }
}