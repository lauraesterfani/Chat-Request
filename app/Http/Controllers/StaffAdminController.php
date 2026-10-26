<?php

namespace App\Http\Controllers;

use App\Models\StaffAdmin;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

class StaffAdminController extends Controller
{
    public function index()
    {
        if (! $this->mayManageAccounts()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        return response()->json(StaffAdmin::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'phone', 'course_id', 'is_active']));
    }

    public function admins()
    {
        if (! $this->mayManageAccounts()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        return StaffAdmin::where('role', 'admin')->where('is_active', true)->get(['id', 'name', 'email']);
    }

    public function destroy(Request $request, $id)
    {
        return $this->setActive($request, $id, false);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        return $this->setActive($request, $id, $data['is_active']);
    }

    public function store(Request $request)
    {
        try {
            if (! $this->mayManageAccounts()) {
                return response()->json(['message' => 'Acesso não autorizado.'], 403);
            }

            $data = $request->validate([
                'name'  => 'required|string',
                'email' => 'required|email|unique:staff_admins,email',
                'role'  => 'required|string|in:admin,cradt,coordenacao',
                'cpf'   => 'nullable|string|unique:staff_admins,cpf',
                'course_id' => 'nullable|exists:courses,id'
            ]);

            // gera senha aleatória
            $tempPassword = \Illuminate\Support\Str::random(10);
            $data['password'] = Hash::make($tempPassword);
            $data['must_change_password'] = true; // flag para redefinição obrigatória

            $staff = StaffAdmin::create($data);
            app(AuditService::class)->record($request, 'staff_account_created', 'staff_admin', $staff->id, ['role' => $staff->role]);

            // retorna a senha temporária junto com os dados
            return response()->json([
                'message' => 'Criado com sucesso!',
                'user' => $staff,
                'temp_password' => $tempPassword
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to create staff administrator account.', ['exception' => $e]);

            return response()->json(['message' => 'Não foi possível criar a conta neste momento.'], 500);
        }
    }

    private function setActive(Request $request, int|string $id, bool $isActive)
    {
        $actor = auth('staff_admins')->user();
        if (! $this->mayManageAccounts()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $target = StaffAdmin::findOrFail($id);
        if ($target->role === 'admin' && ! $isActive) {
            if ((int) $target->id === (int) $actor->id) {
                return response()->json(['message' => 'Não é permitido desativar a própria conta administrativa.'], 422);
            }
            if (StaffAdmin::where('role', 'admin')->where('is_active', true)->count() <= 1) {
                return response()->json(['message' => 'A última conta administrativa ativa não pode ser desativada.'], 422);
            }
        }

        $target->update(['is_active' => $isActive]);
        app(AuditService::class)->record($request, $isActive ? 'staff_account_activated' : 'staff_account_deactivated', 'staff_admin', $target->id, [
            'role' => $target->role,
            'is_active' => $isActive,
        ]);

        return response()->json(['message' => $isActive ? 'Conta ativada.' : 'Conta desativada.', 'is_active' => $target->is_active]);
    }

    private function mayManageAccounts(): bool
    {
        $actor = auth('staff_admins')->user();

        return $actor instanceof StaffAdmin && $actor->isActive() && $actor->role === 'admin' && ! $actor->accessScopes()->exists();
    }
}
