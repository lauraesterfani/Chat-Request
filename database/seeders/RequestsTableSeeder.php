<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('requests')->insert([
            'enrollment' => '20232TSIIG0304', // matrícula existente
            'protocol' => 'PRT001',
            'status' => 'pendente',
            'observations' => 'Solicitação de teste',
            'user_id' => 1, // Adicione o ID de um usuário existente
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}