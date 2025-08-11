<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campus;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        Campus::insert([
            ['nome_campus' => 'Campus Igarassu', 'cidade' => 'Igarassu'],
            ['nome_campus' => 'Campus Recife', 'cidade' => 'Recife'],
            ['nome_campus' => 'Campus Paulista', 'cidade' => 'Paulista'],
            ['nome_campus' => 'Campus Jaboatão', 'cidade' => 'Jaboatão dos Guararapes'],
            ['nome_campus' => 'Campus Caruaru', 'cidade' => 'Caruaru'],
            ['nome_campus' => 'Campus Petrolina', 'cidade' => 'Petrolina'],
            ['nome_campus' => 'Campus Garanhuns', 'cidade' => 'Garanhuns'],
            ['nome_campus' => 'Campus Vitória', 'cidade' => 'Vitória de Santo Antão'],
            ['nome_campus' => 'Campus Olinda', 'cidade' => 'Olinda'],
            ['nome_campus' => 'Campus Cabo de Santo Agostinho', 'cidade' => 'Cabo de Santo Agostinho'],
            ['nome_campus' => 'Campus São Lourenço da Mata', 'cidade' => 'São Lourenço da Mata'],
        ]);
    }
}