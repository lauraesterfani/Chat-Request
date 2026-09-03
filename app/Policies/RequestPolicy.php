<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Determine whether the user can view any models (for the index method).
     */
    public function viewAny(User $user): Response
    {
        if ($user->isAdmin() || $user->isStudent()) {
            return Response::allow();
        }

        return Response::deny('Você não tem permissão para visualizar a lista de requerimentos.');
    }

    /**
     * Determine whether the user can view the model (for the show method).
     */
    public function view(User $user, RequestModel $requestModel): Response
    {
        // Admin e Staff podem ver qualquer requerimento
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Student só pode ver o seu próprio requerimento
        if ($user->isStudent() && $user->id === $requestModel->user_id) {
            return Response::allow();
        }

        return Response::deny('Você não tem permissão para visualizar este requerimento.');
    }

    /**
     * Determine whether the user can create models (for the store method).
     */
    public function create(User $user): Response
    {
        // Apenas Students podem criar novos requerimentos.
        return $user->isStudent()
            ? Response::allow()
            : Response::deny('Apenas alunos podem criar requerimentos.');
    }

    /**
     * Determine whether the user can update the model (for the update method).
     */
    public function update(User $user, RequestModel $requestModel): Response
    {
        // Apenas Admin e Staff podem atualizar o status de um requerimento.
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Apenas Admin e Staff podem alterar requerimentos.');
    }

    /**
     * Determine whether the user can delete the model (for the destroy method).
     */
    public function delete(User $user, RequestModel $requestModel): Response
    {
        // Apenas Admin pode deletar requerimentos.
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Apenas o Administrador pode deletar requerimentos.');
    }
}
