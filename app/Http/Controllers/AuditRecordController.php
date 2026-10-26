<?php

namespace App\Http\Controllers;

use App\Models\AuditRecord;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AuditRecordController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth('staff_admins')->user();
        if (! $actor instanceof StaffAdmin || ! $actor->isActive() || $actor->role !== 'admin' || $actor->accessScopes()->exists()) {
            if ($actor instanceof StaffAdmin) {
                app(AuditService::class)->record($request, 'audit_records_access_denied', 'audit_records', null, [
                    'reason' => ! $actor->isActive() ? 'inactive_account' : ($actor->role !== 'admin' ? 'insufficient_role' : 'scoped_account'),
                ], 'denied');
            }

            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $data = $request->validate(['action' => ['nullable', 'string', 'max:80'], 'resource_type' => ['nullable', 'string', 'max:80'], 'resource_id' => ['nullable', 'string', 'max:80'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = AuditRecord::query()->latest('created_at');
        foreach (['action', 'resource_type', 'resource_id'] as $field) {
            if (! empty($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        if (! empty($data['from'])) {
            $query->where('created_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $query->where('created_at', '<=', $data['to']);
        }

        $results = $query->paginate(min(max((int) $request->input('per_page', 30), 1), 100));
        app(AuditService::class)->record($request, 'audit_records_viewed', 'audit_records', null, [
            'filters' => array_intersect_key($data, array_flip(['action', 'resource_type', 'resource_id', 'from', 'to'])),
            'page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'result_count' => $results->count(),
        ]);

        return response()->json($results);
    }
}
