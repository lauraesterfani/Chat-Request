<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('requests')->insert([
            'enrollment_id' => '1', // matrícula existente
            'protocol' => 'PRT001',
            'status' => 'pendente',
            'observations' => 'Solicitação de teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
