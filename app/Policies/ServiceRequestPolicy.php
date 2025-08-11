<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    /**
     * Determina se o usuário pode ver qualquer requisição.
     * Esta regra será usada no método 'index' do controlador.
     */
    public function viewAny(User $user): bool
    {
        // Todos os usuários autenticados (admin, staff, student) podem ver a lista.
        // A filtragem das requisições (por usuário) será feita no controlador.
        return true;
    }

    /**
     * Determina se o usuário pode ver uma requisição específica.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        // Admins e Staffs podem ver todas as requisições.
        if ($user->user_type === 'admin' || $user->user_type === 'staff') {
            return true;
        }

        // Estudantes só podem ver suas próprias requisições.
        return $user->id === $serviceRequest->user_id;
    }

    /**
     * Determina se o usuário pode criar uma nova requisição.
     */
    public function create(User $user): bool
    {
        // Todos os usuários autenticados (admin, staff, student) podem criar.
        return true;
    }

    /**
     * Determina se o usuário pode atualizar uma requisição.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        // Admins e Staffs podem atualizar qualquer requisição.
        if ($user->user_type === 'admin' || $user->user_type === 'staff') {
            return true;
        }

        // Estudantes só podem atualizar suas próprias requisições.
        return $user->id === $serviceRequest->user_id;
    }

    /**
     * Determina se o usuário pode deletar uma requisição.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        // Apenas admins podem deletar requisições.
        return $user->user_type === 'admin';
    }
}
