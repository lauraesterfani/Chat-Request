<?php
namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run()
    {
        Course::create([
          'name' => 'TSI',
          'description' => 'Tecnologia de Sistemas para Internet'
        ]);

        Course::create([
          'name' => 'ADM',
          'description' => 'Adminstração'
        ]);

        Course::create([
          'name' => 'LGT',
          'description' => 'Logística'
        ]);

        Course::create([
          'name' => 'IPI',
          'description' => 'Inrodução para Internet'
        ]);

        Course::create([
          'name' => 'GTQ',
          'description' => 'Gestão e Qualidade'
        ]);
    }
}
