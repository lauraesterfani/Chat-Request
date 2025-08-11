<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\Campus;
use App\Models\Matricula;

class MatriculaSeeder extends Seeder
{
    public function run(): void
    {
        $alunos = Aluno::all();
        $cursos = Curso::all();
        $campus = Campus::all();

        // Se não houver alunos, cursos ou campus, não faz nada.
        if ($alunos->isEmpty() || $cursos->isEmpty() || $campus->isEmpty()) {
            $this->command->warn('Não foi possível criar matrículas. Verifique se os seeders de Alunos, Cursos e Campus foram executados.');
            return;
        }

        foreach ($alunos as $index => $aluno) {
            Matricula::create([
                // Gera um número de matrícula único baseado no ano e ID do aluno
                'numero_matricula' => date('Y') . str_pad($aluno->id_aluno, 4, '0', STR_PAD_LEFT),
                'periodo_ingresso' => '2024.1',
                'turno' => ['Manhã', 'Tarde', 'Noite'][rand(0, 2)], // Escolhe um turno aleatório
                'status_matricula' => 'Matriculado',
                'id_aluno' => $aluno->id_aluno,
                'id_curso' => $cursos->random()->id_curso, // Pega um curso aleatório
                'id_campus' => $campus->random()->id_campus, // Pega um campus aleatório
            ]);
        }
    }
}