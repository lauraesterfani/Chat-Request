<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Enrollment; // Assumimos que existe este modelo
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserEnrollmentSeeder extends Seeder
{
    /**
     * Executa o seeder para criar um usuário de teste com uma matrícula ativa.
     * * @return void
     */
    public function run(): void
    {
        // Garante que o usuário é criado apenas uma vez (procura pelo CPF)
        $cpfTeste = '11111111111';
        
        $user = User::where('cpf', $cpfTeste)->first();

        if (!$user) {
            // 1. Criar o Usuário Estudante
            $userId = (string) Str::uuid();
            $user = User::create([
                'id' => $userId,
                'name' => 'Aluno Teste Matrícula',
                'email' => 'aluno@teste.com',
                'cpf' => $cpfTeste,
                'phone' => '999999999',
                'role' => User::ROLE_STUDENT,
                'birthday' => Carbon::parse('2000-01-01'),
                'password' => Hash::make('password'), // Senha: password
            ]);

            $this->command->info("Usuário de teste criado: CPF {$cpfTeste} / Senha: password");
        } else {
            $userId = $user->id;
            $this->command->info("Usuário de teste já existe. Usando ID: {$userId}");
        }

        // 2. Criar uma Matrícula para o Usuário (Enrollment)
        // Se a matrícula já existir, não cria novamente.
        if (!Enrollment::where('user_id', $userId)->exists()) {
            
            // Assumimos que o modelo Enrollment também usa UUIDs
            $enrollmentId = (string) Str::uuid();

            Enrollment::create([
                'id' => $enrollmentId,
                'user_id' => $userId,
                'course_id' => (string) Str::uuid(), // ID temporário para Course, ajuste se necessário
                'enrollment_date' => now(),
                'status' => 'active', // Estado que permite o acesso
                'expires_at' => now()->addYears(1),
                // Adicione outros campos necessários aqui (e.g., 'class_id')
            ]);
            
            $this->command->info("Matrícula ativa criada para o usuário ID: {$userId}");
        } else {
            $this->command->info("Matrícula já existe para o usuário ID: {$userId}.");
        }
    }
}