<?php

namespace App\Http\Controllers;

use App\Models\AuditRecord;
use Illuminate\Http\Request;

class AuditRecordController extends Controller
{
    public function index(Request $request)
    {
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

        return response()->json($query->paginate(min(max((int) $request->input('per_page', 30), 1), 100)));
    }
}
