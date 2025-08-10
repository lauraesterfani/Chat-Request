<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServiceRequest;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Um admin ou staff pode fazer qualquer ação.
     */
    public function before(User $user)
    {
        // Se o usuário for admin ou staff, ele pode passar todas as verificações.
        if ($user->role_id === 1 || $user->role_id === 2) {
            return true;
        }
    }

    /**
     * Determine se o usuário pode ver a lista de requisições.
     */
    public function viewAny(User $user): bool
    {
        // Se o before() não retornou true (ou seja, não é admin ou staff), um estudante não pode ver todas as requisições.
        return false;
    }

    /**
     * Determine se o usuário pode ver uma requisição específica.
     */
    public function view(User $user, ServiceRequest $request): Response|bool
    {
        // Um estudante só pode ver a sua própria requisição.
        return $user->id === $request->user_id
               ? Response::allow()
               : Response::deny('Você não tem permissão para visualizar esta requisição.');
    }

    /**
     * Determine se o usuário pode criar uma requisição.
     */
    public function create(User $user): bool
    {
        // Qualquer usuário logado pode criar uma requisição.
        return true;
    }

    /**
     * Determine se o usuário pode atualizar uma requisição.
     */
    public function update(User $user, ServiceRequest $request): Response|bool
    {
        // Um estudante só pode atualizar a sua própria requisição.
        return $user->id === $request->user_id
               ? Response::allow()
               : Response::deny('Você não tem permissão para editar esta requisição.');
    }

    /**
     * Determine se o usuário pode excluir uma requisição.
     */
    public function delete(User $user): bool
    {
        // Um estudante não pode excluir requisições.
        return false;
    }
}
