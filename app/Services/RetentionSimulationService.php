<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RetentionSimulationService
{
    private const SOURCES = [
        'requests' => ['table' => 'requests', 'label' => 'Requerimentos', 'timestamp' => 'created_at'],
        'documents' => ['table' => 'documents', 'label' => 'Anexos', 'timestamp' => 'created_at'],
        'notifications' => ['table' => 'notification_records', 'label' => 'Notificações', 'timestamp' => 'created_at'],
        'audit_records' => ['table' => 'audit_records', 'label' => 'Registros de auditoria', 'timestamp' => 'created_at'],
        'drafts' => ['table' => 'request_drafts', 'label' => 'Rascunhos', 'timestamp' => 'created_at'],
    ];

    /**
     * Produce a bounded, read-only estimate. The cutoff is supplied by an
     * operator for planning only; it is not a retention policy or deletion rule.
     *
     * @return array<string, mixed>
     */
    public function simulate(string $category, CarbonImmutable $cutoff): array
    {
        $source = self::SOURCES[$category];
        $query = DB::table($source['table'])
            ->where($source['timestamp'], '<', $cutoff)
            ->orderBy($source['timestamp'])
            ->orderBy('id');
        $count = (clone $query)->count();
        $references = $query->limit(100)->get(['id', $source['timestamp']])->map(fn (object $row): array => [
            'reference' => hash_hmac('sha256', (string) $row->id, (string) config('app.key')),
            'created_at' => CarbonImmutable::parse($row->{$source['timestamp']})->utc()->toIso8601String(),
        ])->values();

        return [
            'category' => $category,
            'label' => $source['label'],
            'cutoff' => $cutoff->utc()->toIso8601String(),
            'policy_status' => 'unapproved',
            'holds_status' => 'not_configured',
            'holds_applied' => null,
            'coverage_status' => 'relational_records_only',
            'uncovered_dependencies' => ['exports', 'cache', 'backups', 'external_queue', 'external_mail'],
            'candidates_count' => $count,
            'references' => $references,
            'references_limited' => $count > $references->count(),
            'deletion_executed' => false,
        ];
    }

    /** @return array<string, array{table:string,label:string,timestamp:string}> */
    public static function categories(): array
    {
        return self::SOURCES;
    }
}
