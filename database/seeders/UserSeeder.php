<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Curso padrão
        $course = Course::first();
        if (!$course) {
            $course = Course::create([
                'id'   => Str::uuid(),
                'code' => 'ADM',
                'name' => 'Curso Administrativo',
            ]);
        }

        // Aqui você pode criar apenas ALUNOS
        User::create([
            'name' => 'Aluno Teste',
            'email' => 'aluno@discente.edu.br',
            'cpf' => '22222222222',
            'phone' => '81977777777',
            'matricula' => 'ALN001',
            'course_id' => $course->id,
            'birthday' => '2000-01-01',
            'password' => Hash::make('aluno123'),
            'role' => 'student',
        ]);
    }
}
