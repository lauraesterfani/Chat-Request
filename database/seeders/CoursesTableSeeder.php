<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('courses')->insert([
            [
                'name' => 'Curso de PHP',
                'description' => 'Aprenda PHP do básico ao avançado.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
