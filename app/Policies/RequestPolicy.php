<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class RequestPolicy
{
    /**
     * Determine whether the user can view any models (for the index method).
     */
    public function viewAny(User $user): Response
    {
        // --- DIAGNÓSTICO CRÍTICO: VERIFICA QUAL ROLE ESTÁ CHEGANDO NA POLICY ---
        // Se este dd() for acionado, o acesso negado não é de rota, mas sim de papel.
        dd('DIAGNÓSTICO DA POLICY (viewAny):', [
            'ID do Usuário' => $user->id,
            'Role Lida do Token' => $user->role,
            'isStudent()' => $user->isStudent(),
            'isAdmin()' => $user->isAdmin(),
            'isStaff()' => $user->isStaff(),
        ]);
        // ---------------------------------------------------------------------

        // Lógica de autorização (só será alcançada se o dd() for removido):
        if ($user->isAdmin() || $user->isStaff() || $user->isStudent()) {
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
        if ($user->isAdmin() || $user->isStaff()) {
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
        return ($user->isAdmin() || $user->isStaff())
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
