<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Permite que o Admin tenha acesso total, ignorando os outros métodos da Policy.
     * Isso resolve o problema de autorização (403) para o Admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return null; // Deixa os métodos viewAny, view, etc., decidirem o resto
    }

    /**
     * Determina se o usuário pode visualizar qualquer requisição.
     * Permitido para Staff e Estudantes (que verão apenas as suas).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['staff', 'student']);
    }

    /**
     * Determina se o usuário pode visualizar uma requisição específica.
     * Staff pode ver qualquer requisição. Estudante só pode ver a sua.
     */
    public function view(User $user, RequestModel $request): bool
    {
        if ($user->role === 'staff') {
            return true;
        }

        // Se for estudante, checa se ele é o criador da requisição
        return $user->id === $request->user_id;
    }

    /**
     * Determina se o usuário pode criar requisições (apenas Estudantes).
     */
    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    /**
     * Determina se o usuário pode atualizar a requisição.
     * Permissão baseada na lógica de negócio: Staff pode atualizar.
     * Estudante só pode atualizar se o status for rascunho (pending) ou se a requisição ainda não foi processada.
     */
    public function update(User $user, RequestModel $request): bool
    {
        if ($user->role === 'staff') {
            return true;
        }
        
        // Estudante só pode atualizar se for o dono E o status não for finalizado.
        return $user->id === $request->user_id && $request->status === 'pending';
    }

    /**
     * Determina se o usuário pode deletar a requisição (apenas Staff/Admin em rascunho).
     * Como o Admin já tem acesso total via before(), focamos no Staff.
     */
    public function delete(User $user, RequestModel $request): bool
    {
        return $user->role === 'staff' && $request->status === 'pending';
    }

    /**
     * Determina se o usuário pode restaurar requisições.
     */
    public function restore(User $user, RequestModel $request): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determina se o usuário pode forçar a exclusão de requisições.
     */
    public function forceDelete(User $user, RequestModel $request): bool
    {
        return $user->role === 'admin';
    }
}
