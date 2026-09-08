<?php

namespace App\Http\Controllers;

use App\Models\StaffAccessScope;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffAccessScopeController extends Controller
{
    private const ABILITIES = ['view_request', 'reply_request', 'review_document', 'forward_request', 'decide_request', 'manage_templates'];

    public function index(Request $request)
    {
        return response()->json(StaffAccessScope::with('staffAdmin:id,name,email')->latest()->paginate(min((int) $request->input('per_page', 30), 100)));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['staff_admin_id' => ['required', 'integer', 'exists:staff_admins,id'], 'course_id' => ['nullable', 'uuid', 'exists:courses,id'], 'sector' => ['nullable', 'string', 'max:80'], 'abilities' => ['required', 'array', 'min:1'], 'abilities.*' => ['string', Rule::in(self::ABILITIES)], 'expires_at' => ['nullable', 'date', 'after:now'], 'reason' => ['required', 'string', 'max:1000']]);
        $data['granted_by'] = $request->user()->getKey();

        $scope = StaffAccessScope::create($data);
        app(AuditService::class)->record($request, 'scope_granted', 'staff_access_scope', $scope->id, ['staff_admin_id' => $scope->staff_admin_id, 'abilities' => $scope->abilities, 'expires_at' => $scope->expires_at?->toIso8601String()]);

        return response()->json($scope, 201);
    }

    public function destroy(Request $request, StaffAccessScope $scope)
    {
        app(AuditService::class)->record($request, 'scope_revoked', 'staff_access_scope', $scope->id, ['staff_admin_id' => $scope->staff_admin_id, 'abilities' => $scope->abilities]);
        $scope->delete();

        return response()->json(['message' => 'Escopo revogado.']);
    }
}
