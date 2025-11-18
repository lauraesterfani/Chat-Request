<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- 1. Definir IDs Comuns e Cursos ---

        $adminId = (string) Str::uuid();
        $staffId = (string) Str::uuid();
        $studentId = (string) Str::uuid();
        
        // ID de curso (usaremos o primeiro curso fixo do Enrollment Model)
        $courseId = '8e6a2b9f-d0e5-4c1a-8b3d-7f4c5e0d9b1a'; 

        // --- 2. Criar Usuários Padrão ---
        $defaultBirthday = '1990-01-01'; // Data de nascimento padrão para preencher o campo obrigatório

        // 2.1 Admin Padrão
        User::create([
            'id' => $adminId,
            'name' => 'Admin Padrão',
            'email' => 'admin@email.com',
            'cpf' => '00000000000',
            'phone' => '11900000000',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'birthday' => $defaultBirthday, // CAMPO ADICIONADO
        ]);

        // 2.2 Staff Padrão
        User::create([
            'id' => $staffId,
            'name' => 'Staff Padrão',
            'email' => 'staff@email.com',
            'cpf' => '11111111111',
            'phone' => '11911111111',
            'role' => 'staff',
            'password' => Hash::make('password'),
            'birthday' => $defaultBirthday, // CAMPO ADICIONADO
        ]);

        // 2.3 Estudante Padrão
        User::create([
            'id' => $studentId,
            'name' => 'Estudante Padrão',
            'email' => 'student@email.com',
            'cpf' => '22222222222',
            'phone' => '11922222222',
            'role' => 'student',
            'password' => Hash::make('password'),
            'birthday' => $defaultBirthday, // CAMPO ADICIONADO
        ]);


        // --- 3. Criar Matrícula para o Estudante (Apenas com campos existentes) ---

        $enrollmentCode = strtoupper(Str::random(10)); 

        Enrollment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $studentId,
            'course_id' => $courseId,
            'enrollment' => $enrollmentCode, 
            'status' => 'active',
        ]);
    }
}