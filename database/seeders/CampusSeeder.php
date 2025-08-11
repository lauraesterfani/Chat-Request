<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campus;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        Campus::insert([
            ['nome_campus' => 'Campus Igarassu', 'cidade' => 'Recife'],
            ['nome_campus' => 'Campus Olinda', 'cidade' => 'Olinda'],
            ['nome_campus' => 'Campus EAD Brasil', 'cidade' => null],
        ]);
    }
}