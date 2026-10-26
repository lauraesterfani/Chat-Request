<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Course;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffAccessScopeController extends Controller
{
    private const ABILITIES = ['view_request', 'reply_request', 'review_document', 'forward_request', 'decide_request', 'use_templates', 'manage_templates'];

    private const SECTORS = ['CRADT', 'COORDENACAO'];

    public function options(Request $request)
    {
        if (! $this->mayManageScopes($request)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        return response()->json([
            'staff' => StaffAdmin::query()->where('is_active', true)->whereIn('role', ['cradt', 'coordenacao'])->orderBy('name')->get(['id', 'name', 'role']),
            'campuses' => Campus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'courses' => Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'campus_id']),
            'sectors' => self::SECTORS,
        ]);
    }

    public function index(Request $request)
    {
        if (! $this->mayManageScopes($request)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $perPage = min(max((int) $request->input('per_page', 30), 1), 100);

        return response()->json(StaffAccessScope::with([
            'staffAdmin:id,name,role',
            'campus:id,name,code',
            'course:id,name,code,campus_id',
        ])->latest()->paginate($perPage));
    }

    public function store(Request $request)
    {
        if (! $this->mayManageScopes($request)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $data = $request->validate([
            'staff_admin_id' => ['required', 'integer', 'exists:staff_admins,id'],
            'campus_id' => ['nullable', 'uuid', 'exists:campuses,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'sector' => ['nullable', 'string', Rule::in(self::SECTORS)],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(self::ABILITIES)],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'expires_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $target = StaffAdmin::findOrFail($data['staff_admin_id']);
        if (! $target->isActive()) {
            throw ValidationException::withMessages(['staff_admin_id' => 'Não é possível conceder acesso a uma conta desativada.']);
        }
        if ($target->role === 'admin' || $target->role === 'staff') {
            throw ValidationException::withMessages(['staff_admin_id' => 'Este perfil não pode receber concessões de acesso acadêmico.']);
        }

        $allowedForRole = $target->role === 'coordenacao'
            ? ['view_request', 'reply_request', 'review_document', 'use_templates']
            : self::ABILITIES;
        if (array_diff($data['abilities'], $allowedForRole)) {
            throw ValidationException::withMessages(['abilities' => 'Uma ou mais capacidades não podem ser concedidas a este perfil.']);
        }

        if (empty($data['campus_id']) && empty($data['course_id']) && empty($data['sector'])) {
            throw ValidationException::withMessages(['campus_id' => 'Informe ao menos um limite de campus, curso ou setor.']);
        }
        if (array_intersect($data['abilities'], ['use_templates', 'manage_templates']) && empty($data['sector'])) {
            throw ValidationException::withMessages(['sector' => 'O uso e o gerenciamento de respostas pré-configuradas exigem um setor definido.']);
        }

        $campus = ! empty($data['campus_id']) ? Campus::findOrFail($data['campus_id']) : null;
        $course = ! empty($data['course_id']) ? Course::findOrFail($data['course_id']) : null;
        if (($campus && ! $campus->is_active) || ($course && ! $course->is_active)) {
            throw ValidationException::withMessages(['campus_id' => 'Não é possível conceder acesso a um campus ou curso inativo.']);
        }
        if ($campus && $course && $course->campus_id !== $campus->id) {
            throw ValidationException::withMessages(['course_id' => 'O curso selecionado não pertence ao campus informado.']);
        }

        $startsAt = $data['starts_at'];
        if (! empty($data['expires_at']) && Carbon::parse($data['expires_at'])->lessThanOrEqualTo(Carbon::parse($startsAt))) {
            throw ValidationException::withMessages(['expires_at' => 'A expiração deve ocorrer depois do início da concessão.']);
        }

        $scope = DB::transaction(function () use ($data, $request, $startsAt) {
            $scope = StaffAccessScope::create([
                ...$data,
                'starts_at' => $startsAt,
                'granted_by' => auth('staff_admins')->user()->getKey(),
            ]);

            app(AuditService::class)->record($request, 'scope_granted', 'staff_access_scope', $scope->id, [
                'staff_admin_id' => $scope->staff_admin_id,
                'campus_id' => $scope->campus_id,
                'course_id' => $scope->course_id,
                'sector' => $scope->sector,
                'abilities' => $scope->abilities,
                'starts_at' => $scope->starts_at?->toIso8601String(),
                'expires_at' => $scope->expires_at?->toIso8601String(),
            ]);

            return $scope;
        });

        return response()->json($scope->load(['staffAdmin:id,name,role', 'campus:id,name,code', 'course:id,name,code,campus_id']), 201);
    }

    public function destroy(Request $request, StaffAccessScope $scope)
    {
        if (! $this->mayManageScopes($request)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        if (! $scope->revoked_at) {
            DB::transaction(function () use ($request, $scope): void {
                $scope->update(['revoked_at' => now(), 'revoked_by' => auth('staff_admins')->user()->getKey()]);
                app(AuditService::class)->record($request, 'scope_revoked', 'staff_access_scope', $scope->id, [
                    'staff_admin_id' => $scope->staff_admin_id,
                    'campus_id' => $scope->campus_id,
                    'course_id' => $scope->course_id,
                    'sector' => $scope->sector,
                    'abilities' => $scope->abilities,
                ]);
            });
        }

        return response()->json(['message' => 'Concessão revogada.', 'revoked_at' => $scope->fresh()->revoked_at]);
    }

    private function mayManageScopes(Request $request): bool
    {
        $actor = auth('staff_admins')->user();

        // No campus-admin role exists yet. Only an unrestricted institutional
        // administrator can delegate; an explicitly scoped admin cannot widen
        // its own grant or delegate abilities it does not hold.
        return $actor instanceof StaffAdmin
            && $actor->role === 'admin'
            && ! $actor->accessScopes()->exists();
    }
}
