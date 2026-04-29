<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaffAdmin;
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
            ['email' => 'staff@cradt.edu.br'],
            [
                'name' => 'Suporte Acadêmico',
                'cpf' => '11111111111',
                'phone' => '81888888888',
                'role' => 'staff',
                'password' => Hash::make('staff123'),
            ]
        );

        // Usuário inicial ADMIN
        StaffAdmin::firstOrCreate(
            ['email' => 'admin@cradt.edu.br'],
            [
                'name' => 'Administrador CRADT',
                'cpf' => '00000000000',
                'phone' => '81999999999',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
