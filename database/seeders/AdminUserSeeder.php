<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            return;
        }

        User::create([
            'name' => 'Admin do Sistema',
            'email' => 'admin@chat.com', // Adicionado o campo 'email'
            'cpf' => '00011122233',
            'password' => Hash::make('senhaadmin123'),
            'role_id' => $adminRole->id,
        ]);
    }
}