<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Desativa a verificação de chaves estrangeiras para poder truncar a tabela
        Schema::disableForeignKeyConstraints();

        // Limpa a tabela de forma eficiente e reseta o auto-incremento do ID
        DB::table('users')->truncate();

        // Reativa a verificação de chaves estrangeiras
        Schema::enableForeignKeyConstraints();

        // Insere os dados
        DB::table('users')->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
            'cpf' => '12345678900',
            'phone' => '11999999999',
            'birthday' => '2000-01-01',
            'user_type' => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}