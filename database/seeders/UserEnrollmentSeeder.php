<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Enrollment; 
use App\Models\Course; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserEnrollmentSeeder extends Seeder
{
    /**
     * Executa o seeder para criar um usuário de teste com uma matrícula ativa.
     * @return void
     */
    public function run(): void
    {
        // Garante que o usuário é criado apenas uma vez (procura pelo CPF)
        $cpfTeste = '11111111111';
        
        // Obtenha um ID de Curso válido. Isso exige que o CourseSeeder tenha sido executado primeiro.
        $course = Course::first();

        if (!$course) {
             // Esta verificação é crucial, embora o DatabaseSeeder deva garantir a ordem.
             $this->command->error("ERRO: Nenhuma tabela 'courses' encontrada ou semeada. Verifique se o CourseSeeder rodou.");
             return;
        }

        $user = User::where('cpf', $cpfTeste)->first();
        $courseId = $course->id;
        $matriculaTeste = '2025ALN001';
        
        // Define a role usando uma string literal, se a constante não estiver disponível
        $roleStudent = 'student'; 

        if (!$user) {
            // 1. Criar o Usuário Estudante
            $userId = (string) Str::uuid();
            $user = User::create([
                'id' => $userId,
                'name' => 'Aluno Teste Matrícula',
                'email' => 'aluno@teste.com',
                'cpf' => $cpfTeste,
                'phone' => '999999999',
                'role' => $roleStudent,
                'birthday' => Carbon::parse('2000-01-01'),
                'password' => Hash::make('password'), 
                'matricula' => $matriculaTeste, // Adicionando campo obrigatório
                'course_id' => $courseId,       // Adicionando campo obrigatório
            ]);

            $this->command->info("Usuário de teste criado: CPF {$cpfTeste} / Senha: password");
        } else {
            $userId = $user->id;
            $this->command->info("Usuário de teste já existe. Usando ID: {$userId}");
        }

        // 2. Criar uma Matrícula para o Usuário (Enrollment)
        if (!Enrollment::where('user_id', $userId)->exists()) {
            
            $enrollmentId = (string) Str::uuid();

            Enrollment::create([
                'id' => $enrollmentId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'enrollment' => $matriculaTeste, // Código de enrollment
                'status' => 'active', 
            ]);
            
            $this->command->info("Matrícula ativa criada para o usuário ID: {$userId}");
        } else {
            $this->command->info("Matrícula já existe para o usuário ID: {$userId}.");
        }
    }
}