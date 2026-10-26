<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MetricExportService
{
    public const MAX_SYNCHRONOUS_ROWS = 5000;

    public function __construct(private readonly RequestAccessService $access) {}

    /** @param array<string, mixed> $filters */
    public function build(StaffAdmin $actor, array $filters): array
    {
        if (! in_array('view_request', $this->access->capabilitiesFor($actor), true)) {
            abort(403, 'Seu acesso aos indicadores foi revogado ou não está configurado.');
        }

        $query = $this->filteredQuery($actor, $filters);
        $scopeFilters = array_diff_key($filters, ['from' => true, 'to' => true]);
        $scopeQuery = $this->filteredQuery($actor, $scopeFilters);
        $rowCount = (clone $query)->count();
        if ($rowCount > self::MAX_SYNCHRONOUS_ROWS) {
            throw ValidationException::withMessages([
                'filters' => 'O relatório ultrapassa o limite de '.self::MAX_SYNCHRONOUS_ROWS.' requerimentos para geração síncrona. Reduza o período ou os filtros.',
            ]);
        }

        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $closed = (clone $query)->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value]);
        $satisfaction = SatisfactionResponse::query()->whereIn('request_id', (clone $closed)->select('id'));
        $satisfactionByRating = (clone $satisfaction)
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $total = $rowCount;
        $closedCount = (clone $closed)->count();
        $satisfactionCount = (clone $satisfaction)->count();
        $currentByStatus = (clone $scopeQuery)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $resolvedQuery = (clone $scopeQuery)
            ->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
            ->whereNotNull('resolved_at');
        if (isset($filters['from'])) {
            $resolvedQuery->where('resolved_at', '>=', CarbonImmutable::parse($filters['from'], 'UTC')->startOfDay());
        }
        if (isset($filters['to'])) {
            $resolvedQuery->where('resolved_at', '<=', CarbonImmutable::parse($filters['to'], 'UTC')->endOfDay());
        }
        $resolvedByStatus = (clone $resolvedQuery)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        $summary = [
            'total' => $total,
            'open' => (clone $query)->whereNotIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])->count(),
            'completed' => (int) ($byStatus[RequestStatus::COMPLETED->value] ?? 0),
            'canceled' => (int) ($byStatus[RequestStatus::CANCELED->value] ?? 0),
            'by_status' => collect(RequestStatus::values())->mapWithKeys(fn (string $status): array => [$status => (int) ($byStatus[$status] ?? 0)])->all(),
            'distribution' => [
                'cohort' => 'requests_opened_in_selected_period',
                'by_service' => $this->groupedCounts($query, 'type_id', TypeRequest::class, 'Serviço legado'),
                'by_campus' => $this->groupedCounts($query, 'campus_id', Campus::class, 'Campus legado'),
                'by_course' => $this->groupedCounts($query, 'course_id', Course::class, 'Curso legado'),
                'by_sector' => (clone $query)->select('responsible_sector', DB::raw('count(*) as total'))
                    ->groupBy('responsible_sector')->get()->map(fn ($row): array => [
                        'value' => $row->responsible_sector,
                        'label' => match ($row->responsible_sector) {
                            'CRADT' => 'CRADT', 'COORDENACAO' => 'Coordenação', null => 'Não informado', default => (string) $row->responsible_sector,
                        },
                        'count' => (int) $row->total,
                    ])->values()->all(),
            ],
            'cohorts' => [
                'opened_in_period' => ['count' => $total, 'period_field' => 'created_at'],
                'resolved_in_period' => [
                    'count' => (int) $resolvedByStatus->sum(),
                    'period_field' => 'resolved_at',
                    'by_status' => collect([RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
                        ->mapWithKeys(fn (string $status): array => [$status => (int) ($resolvedByStatus[$status] ?? 0)])->all(),
                    'legacy_terminal_records_without_timestamp' => (clone $scopeQuery)
                        ->whereIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
                        ->whereNull('resolved_at')->count(),
                ],
                'current_stock' => [
                    'count' => (int) $currentByStatus->sum(),
                    'by_status' => collect(RequestStatus::values())->mapWithKeys(fn (string $status): array => [$status => (int) ($currentByStatus[$status] ?? 0)])->all(),
                ],
            ],
            'requests_with_first_human_response' => (clone $query)->whereNotNull('first_response_at')->count(),
            'eligible_closed_requests' => $closedCount,
            'satisfaction_responses' => $satisfactionCount,
            'satisfaction_response_rate_percent' => $closedCount > 0 ? round(($satisfactionCount / $closedCount) * 100, 2) : null,
            'average_satisfaction_rating' => $satisfactionCount > 0 ? round((float) (clone $satisfaction)->avg('rating'), 2) : null,
            'satisfaction_by_rating' => collect(range(1, 5))->mapWithKeys(fn (int $rating): array => [$rating => (int) ($satisfactionByRating[$rating] ?? 0)])->all(),
        ];

        return [
            'summary' => $summary,
            'filters' => $this->displayFilters($filters),
            'generated_at' => now()->utc()->toIso8601String(),
            'definitions' => [
                'O período considera a data de abertura do requerimento e inclui os dias selecionados por inteiro em UTC.',
                'A coorte de encerramentos usa resolved_at no mesmo intervalo; o estoque atual ignora o intervalo de datas e considera o escopo organizacional selecionado.',
                'Distribuições por serviço, campus, curso e setor usam a coorte de abertura; contexto histórico ausente é exibido como não informado.',
                'A taxa de satisfação usa como elegíveis os requerimentos concluídos ou cancelados; essa regra ainda requer homologação institucional.',
                'Cobertura de primeira resposta conta registros com first_response_at; não calcula duração nem substitui apuração de SLA.',
                'A taxa de satisfação pode refletir viés de participação voluntária.',
            ],
            'limitations' => [
                'Não inclui nomes, matrículas, protocolos, descrições, comentários ou conteúdo de requerimentos.',
                'Não calcula dias úteis, pausas de SLA, percentis ou coortes de conclusão.',
                'Distribuições por serviço, campus, curso e setor contam pedidos abertos no recorte; registros sem contexto aparecem como não informados.',
                'Dados agregados não devem ser usados para decisões individuais sobre estudantes.',
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(StaffAdmin $actor, array $filters): Builder
    {
        $query = $this->access->constrainVisibleRequests(RequestModel::query(), $actor);
        if (isset($filters['from'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], 'UTC')->startOfDay());
        }
        if (isset($filters['to'])) {
            $query->where('created_at', '<=', CarbonImmutable::parse($filters['to'], 'UTC')->endOfDay());
        }
        if (isset($filters['course_id'])) {
            $this->access->constrainCourse($query, $filters['course_id']);
        }
        if (isset($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }
        if (isset($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }
        if (isset($filters['sector'])) {
            $query->where('responsible_sector', $filters['sector']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /** @return array<int, array{id:string|null,label:string,count:int}> */
    private function groupedCounts(Builder $query, string $column, string $model, string $missingLabel): array
    {
        $counts = (clone $query)->select($column, DB::raw('count(*) as total'))->groupBy($column)->get();
        $ids = $counts->pluck($column)->filter()->unique()->values();
        $labels = $ids->isEmpty() ? collect() : $model::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $counts->map(fn ($row): array => [
            'id' => $row->{$column},
            'label' => $row->{$column} ? ($labels[$row->{$column}] ?? $missingLabel) : 'Não informado',
            'count' => (int) $row->total,
        ])->values()->all();
    }

    /** @param array<string, mixed> $filters */
    private function displayFilters(array $filters): array
    {
        $period = match (true) {
            isset($filters['from'], $filters['to']) => $filters['from'].' até '.$filters['to'],
            isset($filters['from']) => 'A partir de '.$filters['from'],
            isset($filters['to']) => 'Até '.$filters['to'],
            default => 'Todo o histórico',
        };

        return [
            'Período (UTC)' => $period,
            'Curso' => isset($filters['course_id']) ? (Course::find($filters['course_id'])?->name ?? 'Curso selecionado') : 'Todos os cursos autorizados',
            'Campus' => isset($filters['campus_id']) ? (Campus::find($filters['campus_id'])?->name ?? 'Campus selecionado') : 'Todos os campi autorizados',
            'Serviço' => isset($filters['type_id']) ? (TypeRequest::find($filters['type_id'])?->name ?? 'Serviço selecionado') : 'Todos os serviços',
            'Setor' => $filters['sector'] ?? 'Todos os setores autorizados',
            'Etapa' => $filters['status'] ?? 'Todas as etapas',
        ];
    }
}
