<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * ============================
         * CURSO PADRÃO (SE NÃO EXISTIR)
         * ============================
         */
        $course = Course::first();

        if (!$course) {
            $course = Course::create([
                'id'   => Str::uuid(),
                'code' => 'ADM',
                'name' => 'Curso Administrativo',
            ]);
        }

        /**
         * ============================
         * ADMINISTRADOR
         * ============================
         */
        User::create([
            'name' => 'Administrador CRADT',
            'email' => 'admin@cradt.edu.br',
            'cpf' => '00000000000',
            'phone' => '81999999999',
            'matricula' => 'ADM001',
            'course_id' => $course->id,
            'birthday' => '1990-01-01',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        /**
         * ============================
         * STAFF
         * ============================
         */
        User::create([
            'name' => 'Suporte Acadêmico',
            'email' => 'suporte@cradt.edu.br',
            'cpf' => '11111111111',
            'phone' => '81888888888',
            'matricula' => 'STF001',
            'course_id' => $course->id,
            'birthday' => '1995-05-10',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
        ]);
    }
}
