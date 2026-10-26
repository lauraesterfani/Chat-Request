<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Services\RequestAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MetricsController extends Controller
{
    public function filters(Request $request, RequestAccessService $access)
    {
        $actor = auth('staff_admins')->user();
        abort_unless($actor instanceof StaffAdmin && $actor->isActive(), 403);
        abort_unless(in_array('view_request', $access->capabilitiesFor($actor), true), 403);

        // Build filter choices from the exact same request scope as the metrics.
        // This avoids exposing unrelated campus/course/service names to scoped staff.
        $visible = $access->constrainVisibleRequests(RequestModel::query(), $actor);
        $campusIds = (clone $visible)->whereNotNull('campus_id')->distinct()->pluck('campus_id');
        $courseIds = (clone $visible)->whereNotNull('course_id')->distinct()->pluck('course_id');
        $typeIds = (clone $visible)->whereNotNull('type_id')->distinct()->pluck('type_id');
        $sectors = (clone $visible)->whereNotNull('responsible_sector')->distinct()->pluck('responsible_sector');

        return response()->json([
            'campuses' => Campus::query()->whereIn('id', $campusIds)->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->whereIn('id', $courseIds)->orderBy('name')->get(['id', 'name']),
            'services' => TypeRequest::query()->whereIn('id', $typeIds)->orderBy('name')->get(['id', 'name']),
            'sectors' => $sectors->map(fn (string $sector): array => [
                'value' => $sector,
                'label' => $sector === 'COORDENACAO' ? 'Coordenação' : ($sector === 'CRADT' ? 'CRADT' : $sector),
            ])->values(),
        ]);
    }

    public function index(Request $request, RequestAccessService $access)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'campus_id' => ['nullable', 'uuid', 'exists:campuses,id'],
            'type_id' => ['nullable', 'uuid', 'exists:type_requests,id'],
            'sector' => ['nullable', Rule::in(['CRADT', 'COORDENACAO'])],
            'status' => ['nullable', Rule::in(RequestStatus::values())],
        ]);

        // Authorization is applied before any user-selected filters or counts.
        $scopeQuery = $access->constrainVisibleRequests(RequestModel::query(), auth('staff_admins')->user());
        if (isset($data['course_id'])) {
            $access->constrainCourse($scopeQuery, $data['course_id']);
        }
        if (isset($data['campus_id'])) {
            $scopeQuery->where('campus_id', $data['campus_id']);
        }
        if (isset($data['type_id'])) {
            $scopeQuery->where('type_id', $data['type_id']);
        }
        if (isset($data['sector'])) {
            $scopeQuery->where('responsible_sector', $data['sector']);
        }
        if (isset($data['status'])) {
            $scopeQuery->where('status', $data['status']);
        }
        $query = clone $scopeQuery;
        if (isset($data['from'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($data['from'], 'UTC')->startOfDay());
        }
        if (isset($data['to'])) {
            // Date-only filters include the whole selected UTC calendar day.
            $query->where('created_at', '<=', CarbonImmutable::parse($data['to'], 'UTC')->endOfDay());
        }

        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $byService = $this->groupedCounts($query, 'type_id', TypeRequest::class, 'Serviço legado');
        $byCourse = $this->groupedCounts($query, 'course_id', Course::class, 'Curso legado');
        $byCampus = $this->groupedCounts($query, 'campus_id', Campus::class, 'Campus legado');
        $bySector = (clone $query)->select('responsible_sector', DB::raw('count(*) as total'))
            ->groupBy('responsible_sector')->get()->map(fn ($row): array => [
                'value' => $row->responsible_sector,
                'label' => match ($row->responsible_sector) {
                    'CRADT' => 'CRADT',
                    'COORDENACAO' => 'Coordenação',
                    null => 'Não informado',
                    default => (string) $row->responsible_sector,
                },
                'count' => (int) $row->total,
            ])->values();
        $total = (clone $query)->count();
        $stockByStatus = (clone $scopeQuery)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $resolvedQuery = (clone $scopeQuery)
            ->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
            ->whereNotNull('resolved_at');
        if (isset($data['from'])) {
            $resolvedQuery->where('resolved_at', '>=', CarbonImmutable::parse($data['from'], 'UTC')->startOfDay());
        }
        if (isset($data['to'])) {
            $resolvedQuery->where('resolved_at', '<=', CarbonImmutable::parse($data['to'], 'UTC')->endOfDay());
        }
        $resolvedByStatus = (clone $resolvedQuery)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $closedQuery = (clone $query)->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value]);
        $closedCount = (clone $closedQuery)->count();
        $satisfaction = SatisfactionResponse::query()->whereIn('request_id', (clone $closedQuery)->select('id'));
        $satisfactionByRating = (clone $satisfaction)
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $satisfactionCount = (clone $satisfaction)->count();
        $firstResponseCount = (clone $query)->whereNotNull('first_response_at')->count();

        $ratings = collect(range(1, 5))->mapWithKeys(fn (int $rating): array => [
            $rating => (int) ($satisfactionByRating[$rating] ?? 0),
        ]);
        $responseRate = $closedCount > 0
            ? round(($satisfactionCount / $closedCount) * 100, 2)
            : null;
        $canceledCount = (int) ($byStatus[RequestStatus::CANCELED->value] ?? 0);

        return response()->json([
            'total' => $total,
            'open' => (clone $query)->whereNotIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])->count(),
            'completed' => (int) ($byStatus[RequestStatus::COMPLETED->value] ?? 0),
            'canceled' => $canceledCount,
            // Compatibility alias for the existing dashboard contract.
            'cancelled' => $canceledCount,
            'by_status' => collect(RequestStatus::values())->mapWithKeys(fn (string $status): array => [
                $status => (int) ($byStatus[$status] ?? 0),
            ]),
            'distribution' => [
                'cohort' => 'requests_opened_in_selected_period',
                'by_service' => $byService,
                'by_campus' => $byCampus,
                'by_course' => $byCourse,
                'by_sector' => $bySector,
            ],
            'cohorts' => [
                'opened_in_period' => ['count' => $total, 'period_field' => 'created_at'],
                'resolved_in_period' => [
                    'count' => (int) $resolvedByStatus->sum(),
                    'period_field' => 'resolved_at',
                    'by_status' => collect([RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
                        ->mapWithKeys(fn (string $status): array => [$status => (int) ($resolvedByStatus[$status] ?? 0)]),
                    'legacy_terminal_records_without_timestamp' => (clone $scopeQuery)
                        ->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
                        ->whereNull('resolved_at')->count(),
                ],
                'current_stock' => [
                    'count' => (int) $stockByStatus->sum(),
                    'by_status' => collect(RequestStatus::values())->mapWithKeys(fn (string $status): array => [$status => (int) ($stockByStatus[$status] ?? 0)]),
                ],
            ],
            'first_response_coverage' => [
                'requests_with_first_human_response' => $firstResponseCount,
                'requests_without_first_human_response' => max(0, $total - $firstResponseCount),
                'total_requests' => $total,
            ],
            'satisfaction' => [
                'responses' => $satisfactionCount,
                // Compatibility alias for the existing dashboard contract.
                'total_responses' => $satisfactionCount,
                'eligible_closed_requests' => $closedCount,
                'response_rate_percent' => $responseRate,
                'average_rating' => $satisfactionCount > 0 ? round((float) (clone $satisfaction)->avg('rating'), 2) : null,
                'by_rating' => $ratings,
            ],
            'filters' => [
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'campus_id' => $data['campus_id'] ?? null,
                'type_id' => $data['type_id'] ?? null,
                'sector' => $data['sector'] ?? null,
                'status' => $data['status'] ?? null,
            ],
            'generated_at' => now()->utc()->toIso8601String(),
            'timezone' => 'UTC',
            'limitations' => [
                'O intervalo usa a data de abertura do requerimento e inclui os dias selecionados por inteiro em UTC.',
                'A taxa de resposta considera como elegíveis os requerimentos concluídos ou cancelados, conforme o endpoint atual de satisfação; essa regra requer homologação institucional.',
                'A cobertura de primeira resposta conta registros com first_response_at; não calcula duração nem substitui a apuração de SLA.',
                'Duração, percentis, SLA ajustado por expediente/pausas, métricas de disponibilização/entrega e drill-down não estão disponíveis. Encerramentos usam resolved_at e omitem legados sem esse timestamp.',
                'As distribuições usam a coorte de abertura (created_at); estoque atual é uma consulta separada e ignora o intervalo de datas.',
                'Satisfação é voluntária e os resultados podem refletir viés de participação.',
            ],
        ]);
    }

    private function groupedCounts($query, string $column, string $model, string $missingLabel)
    {
        $counts = (clone $query)->select($column, DB::raw('count(*) as total'))->groupBy($column)->get();
        $ids = $counts->pluck($column)->filter()->unique()->values();
        $labels = $ids->isEmpty() ? collect() : $model::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $counts->map(fn ($row): array => [
            'id' => $row->{$column},
            'label' => $row->{$column} ? ($labels[$row->{$column}] ?? $missingLabel) : 'Não informado',
            'count' => (int) $row->total,
        ])->values();
    }
}
