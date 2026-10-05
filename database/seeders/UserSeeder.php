<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach ([
            ['email' => 'qa.student.tsi@example.test', 'name' => 'Aluno TSI de Teste', 'cpf' => '90000009991', 'matricula' => '20241TSIIG001', 'course' => 'TSI-2025'],
            ['email' => 'qa.student.adm@example.test', 'name' => 'Aluno ADM de Teste', 'cpf' => '90000009995', 'matricula' => '20241ADMIG002', 'course' => 'ADM'],
        ] as $account) {
            User::updateOrCreate(['email' => $account['email']], [
                'name' => $account['name'],
                'cpf' => $account['cpf'],
                'phone' => '81900009991',
                'matricula' => $account['matricula'],
                'course_id' => Course::where('code', $account['course'])->value('id'),
                'birthday' => '2000-01-01',
                'password' => Hash::make('ChatRequest-Teste2026!'),
                'role' => User::ROLE_STUDENT,
                'must_change_password' => false,
            ]);
        }
    }
}
