<?php

// database/seeders/RolesAndPermissionsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Limpa o cache de permissões para evitar problemas
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Permissões para o recurso 'request' ---
        Permission::create(['name' => 'listar requisicoes']);
        Permission::create(['name' => 'criar requisicao']);
        Permission::create(['name' => 'ver requisicao']);
        Permission::create(['name' => 'editar requisicao']);
        Permission::create(['name' => 'deletar requisicao']);

        // --- Permissão para o recurso 'enrollment' ---
        Permission::create(['name' => 'gerenciar matriculas']);

        // --- Permissões para o recurso 'user' ---
        Permission::create(['name' => 'gerenciar usuarios']);


        // Cria um papel de exemplo (ex: 'admin') com todas as permissões
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        // Cria um papel de exemplo (ex: 'aluno') com permissões específicas
        $roleAluno = Role::create(['name' => 'aluno']);
        $roleAluno->givePermissionTo(['listar requisicoes', 'criar requisicao', 'ver requisicao']);
    }
}
