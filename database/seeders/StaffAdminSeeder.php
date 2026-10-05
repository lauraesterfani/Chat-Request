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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Usuário inicial STAFF
        StaffAdmin::updateOrCreate(
            ['email' => 'qa.staff@example.test'],
            [
                'name' => 'Suporte Acadêmico',
                'cpf' => '90000009992',
                'phone' => '81900009992',
                'role' => 'staff',
                'password' => Hash::make('ChatRequest-Teste2026!'),
                'must_change_password' => false,
            ]
        );

        // Usuário inicial COORDENAÇÃO
        StaffAdmin::updateOrCreate(
            ['email' => 'qa.coordenacao@example.test'],
            [
                'name' => 'Coordenação TSI',
                'cpf' => '90000009993',
                'phone' => '81900009993',
                'role' => 'coordenacao',
                'course_id' => DB::table('courses')->where('code', 'TSI-2025')->value('id'),
                'password' => Hash::make('ChatRequest-Teste2026!'),
                'must_change_password' => false,
            ]
        );

        // Usuário inicial ADMIN
        StaffAdmin::updateOrCreate(
            ['email' => 'qa.admin@example.test'],
            [
                'name' => 'Administrador CRADT',
                'cpf' => '90000009994',
                'phone' => '81900009994',
                'role' => 'admin',
                'password' => Hash::make('ChatRequest-Teste2026!'),
                'must_change_password' => false,
            ]
        );

        StaffAdmin::updateOrCreate(['email' => 'qa.cradt@example.test'], [
            'name' => 'Atendimento CRADT de Teste',
            'cpf' => '90000009996',
            'phone' => '81900009996',
            'role' => 'cradt',
            'password' => Hash::make('ChatRequest-Teste2026!'),
            'must_change_password' => false,
        ]);
    }
}
