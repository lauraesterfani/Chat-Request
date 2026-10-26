<?php

namespace App\Http\Controllers;

use App\Models\StaffAdmin;
use App\Services\AuditService;
use App\Services\RetentionSimulationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RetentionSimulationController extends Controller
{
    public function __invoke(Request $request, RetentionSimulationService $simulation, AuditService $audit)
    {
        $actor = Auth::guard('staff_admins')->user();
        if (! $actor instanceof StaffAdmin || ! $actor->isActive() || $actor->role !== 'admin' || $actor->accessScopes()->exists()) {
            if ($actor instanceof StaffAdmin) {
                $audit->record($request, 'retention_simulation_access_denied', 'retention_simulation', null, [
                    'reason' => ! $actor->isActive() ? 'inactive_account' : ($actor->role !== 'admin' ? 'insufficient_role' : 'scoped_account'),
                ], 'denied');
            }

            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(RetentionSimulationService::categories()))],
            'before' => ['required', 'date', 'before:now'],
        ]);

        $result = $simulation->simulate(
            $data['category'],
            CarbonImmutable::parse($data['before']),
        );
        $audit->record($request, 'retention_simulation_run', 'retention_simulation', null, [
            'category' => $result['category'],
            'cutoff' => $result['cutoff'],
            'candidates_count' => $result['candidates_count'],
            'deletion_executed' => false,
        ]);

        return response()->json($result);
    }
}
