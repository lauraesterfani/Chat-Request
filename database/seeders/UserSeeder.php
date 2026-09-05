<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Curso padrão
        $course = Course::first();
        if (! $course) {
            $course = Course::create([
                'id' => Str::uuid(),
                'code' => 'ADM',
                'name' => 'Curso Administrativo',
            ]);
        }

        // Aqui você pode criar apenas ALUNOS
        User::updateOrCreate(['email' => 'qa.student@example.test'], [
            'name' => 'Aluno QA Fictício',
            'cpf' => '90000009991',
            'phone' => '81900009991',
            'matricula' => 'QA-ALUNO-001',
            'course_id' => $course->id,
            'birthday' => '2000-01-01',
            'password' => Hash::make('aluno123'),
            'role' => User::ROLE_STUDENT,
        ]);
    }
}
