<?php

namespace Database\Seeders;

use App\Models\StaffAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuário inicial STAFF
        StaffAdmin::firstOrCreate(
            ['email' => 'staff@example.test'],
            [
                'name' => 'Suporte Acadêmico',
                'cpf' => '90000000002',
                'phone' => '81900000002',
                'role' => 'staff',
                'password' => Hash::make('staff123'),
                'must_change_password' => false,
            ]
        );

        // Usuário inicial COORDENAÇÃO
        StaffAdmin::firstOrCreate(
            ['email' => 'coordenacao@example.test'],
            [
                'name' => 'Coordenação TSI',
                'cpf' => '90000000003',
                'phone' => '81900000003',
                'role' => 'coordenacao',
                'course_id' => DB::table('courses')->where('code', 'TSI-2025')->value('id'),
                'password' => Hash::make('coord123'),
                'must_change_password' => false,
            ]
        );

        // Usuário inicial ADMIN
        StaffAdmin::firstOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Administrador CRADT',
                'cpf' => '90000000004',
                'phone' => '81900000004',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
            ]
        );
    }
}
