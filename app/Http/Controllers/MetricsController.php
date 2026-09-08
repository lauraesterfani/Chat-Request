<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'course_id' => ['nullable', 'uuid', 'exists:courses,id']]);
        $query = RequestModel::query();
        if (! empty($data['from'])) {
            $query->where('created_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $query->where('created_at', '<=', $data['to']);
        }
        if (! empty($data['course_id'])) {
            $query->whereHas('user', fn ($users) => $users->where('course_id', $data['course_id']));
        }
        $byStatus = (clone $query)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $total = (clone $query)->count();

        return response()->json(['total' => $total, 'open' => (clone $query)->whereNotIn('status', ['completed', 'canceled'])->count(), 'completed' => (int) ($byStatus['completed'] ?? 0), 'canceled' => (int) ($byStatus['canceled'] ?? 0), 'by_status' => $byStatus, 'filters' => ['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null, 'course_id' => $data['course_id'] ?? null], 'limitations' => ['SLA em dias úteis e pausas não estão contabilizados nesta métrica.']]);
    }
}
