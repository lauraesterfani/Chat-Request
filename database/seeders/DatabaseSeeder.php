<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Adicione esta linha para usar o Hash

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        $this->call(RolesAndPermissionsSeeder::class);

        // Cria o usuário 'admin' com um CPF padrão.
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Define uma senha padrão
            'cpf' => '12345678900', // Valor padrão para CPF
            'user_type' => 'staff', // Exemplo de tipo de usuário
        ]);

        // Atribui o papel 'admin' ao usuário criado.
        $adminUser->assignRole('admin');

        
    }
}
