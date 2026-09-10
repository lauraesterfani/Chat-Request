<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use App\Services\RequestAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function index(Request $request, RequestAccessService $access)
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'course_id' => ['nullable', 'uuid', 'exists:courses,id']]);
        $query = $access->constrainVisibleRequests(RequestModel::query(), auth('staff_admins')->user());
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
        $satisfaction = SatisfactionResponse::query()->whereIn('request_id', (clone $query)->select('id'));
        $satisfactionByRating = (clone $satisfaction)->select('rating', DB::raw('count(*) as total'))->groupBy('rating')->pluck('total', 'rating');
        $satisfactionCount = (clone $satisfaction)->count();

        return response()->json(['total' => $total, 'open' => (clone $query)->whereNotIn('status', ['completed', 'canceled'])->count(), 'completed' => (int) ($byStatus['completed'] ?? 0), 'canceled' => (int) ($byStatus['canceled'] ?? 0), 'by_status' => $byStatus, 'satisfaction' => ['responses' => $satisfactionCount, 'average_rating' => $satisfactionCount ? round((float) (clone $satisfaction)->avg('rating'), 2) : null, 'by_rating' => $satisfactionByRating], 'filters' => ['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null, 'course_id' => $data['course_id'] ?? null], 'limitations' => ['SLA em dias úteis e pausas não estão contabilizados nesta métrica.', 'Satisfação é voluntária e não representa todos os atendimentos.']]);
    }
}
