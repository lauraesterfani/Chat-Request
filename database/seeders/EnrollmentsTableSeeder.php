<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('enrollments')->insert([
            'user_id' => 1,
            'course_id' => 1,
            'enrollment' => '20232TSIIG0304',
            'status' => 'matriculado',
            'campus_name' => 'Campus Igarassu',
            'period' => '1',
            'turn' => 'matutino',
            'modality' => 'presencial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}