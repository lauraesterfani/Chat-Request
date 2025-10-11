<?php

namespace Database\Seeders;

use App\Models\User;
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
        // 1. ADMIN MASTER
        User::create([
            'id' => (string) Str::uuid(), 
            'name' => 'Admin Master',
            'email' => 'admin@chat.com',
            'password' => Hash::make('password'),
            'cpf' => '00000000000',
            'phone' => '11900000000',
            'role' => User::ROLE_ADMIN, 
            'birthday' => '1990-01-01', // Corrigido
            'email_verified_at' => now(),
        ]);

        // 2. FUNCIONÁRIO (STAFF)
        User::create([
            'id' => (string) Str::uuid(), 
            'name' => 'Funcionario Padrao',
            'email' => 'staff@chat.com',
            'password' => Hash::make('password'),
            'cpf' => '11111111111',
            'phone' => '11911111111',
            'role' => User::ROLE_STAFF, 
            'birthday' => '1995-05-15', // Corrigido
            'email_verified_at' => now(),
        ]);

        // 3. ESTUDANTE (STUDENT)
        User::create([
            'id' => (string) Str::uuid(), 
            'name' => 'Estudante Padrao',
            'email' => 'student@chat.com',
            'password' => Hash::make('password'),
            'cpf' => '22222222222',
            'phone' => '11922222222',
            'role' => User::ROLE_STUDENT, 
            'birthday' => '2000-10-20', // Corrigido
            'email_verified_at' => now(),
        ]);
        
        // A chamada User::factory()->create() que estava falhando foi removida.
    }
}