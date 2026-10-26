<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use App\Services\MetricExportFileRenderer;
use App\Services\MetricExportService;
use App\Services\RequestAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MetricExportController extends Controller
{
    private const DIRECTORY = 'metrics-exports';

    private const EXPIRATION_MINUTES = 20;

    public function store(
        Request $request,
        string $format,
        MetricExportService $reports,
        MetricExportFileRenderer $renderer,
        RequestAccessService $access,
        AuditService $audit
    ) {
        $actor = $this->authorizedActor($access);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $filters = $this->validatedFilters($request);
        $report = $reports->build($actor, $filters);
        $contents = $renderer->render($format, $report);
        $id = (string) Str::uuid();
        $disk = Storage::disk('local');
        $filePath = self::DIRECTORY.'/'.$id.'.'.$format;
        $metadataPath = self::DIRECTORY.'/'.$id.'.json';
        $generatedAt = CarbonImmutable::parse($report['generated_at'])->utc();

        $this->purgeExpired($disk);
        if (! $disk->put($filePath, $contents) || ! $disk->put($metadataPath, json_encode([
            'actor_id' => (string) $actor->getKey(),
            'format' => $format,
            'filters' => $filters,
            'generated_at' => $generatedAt->toIso8601String(),
        ], JSON_THROW_ON_ERROR))) {
            $disk->delete([$filePath, $metadataPath]);
            abort(500, 'Não foi possível armazenar o relatório com segurança.');
        }

        try {
            $audit->record($request, 'metrics.export.requested', 'metrics_export', $id, [
                'format' => $format,
                'filter_keys' => array_values(array_keys($filters)),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete([$filePath, $metadataPath]);
            throw $exception;
        }

        return response()->json([
            'id' => $id,
            'format' => $format,
            'generated_at' => $generatedAt->toIso8601String(),
            'expires_at' => $generatedAt->addMinutes(self::EXPIRATION_MINUTES)->toIso8601String(),
            'download_path' => '/api/metrics/exports/'.$id.'/download',
        ], 201);
    }

    public function download(
        Request $request,
        string $id,
        MetricExportService $reports,
        MetricExportFileRenderer $renderer,
        RequestAccessService $access,
        AuditService $audit
    ) {
        abort_unless(Str::isUuid($id), 404);
        $actor = $this->authorizedActor($access);
        $disk = Storage::disk('local');
        $metadataPath = self::DIRECTORY.'/'.$id.'.json';
        abort_unless($disk->exists($metadataPath), 404, 'Exportação inexistente ou expirada.');
        $metadata = json_decode($disk->get($metadataPath), true);
        abort_unless(is_array($metadata) && ($metadata['actor_id'] ?? null) === (string) $actor->getKey(), 403);
        abort_unless(in_array($metadata['format'] ?? null, ['xlsx', 'pdf'], true), 404);

        $expiresAt = CarbonImmutable::parse($metadata['generated_at'] ?? '1970-01-01')->utc()->addMinutes(self::EXPIRATION_MINUTES);
        if ($expiresAt->isPast()) {
            $disk->delete([$metadataPath, self::DIRECTORY.'/'.$id.'.xlsx', self::DIRECTORY.'/'.$id.'.pdf']);
            abort(404, 'Exportação inexistente ou expirada.');
        }

        // Rebuild from the currently effective scope: revocation or a narrower
        // grant cannot keep serving a file generated under an older permission.
        $report = $reports->build($actor, $metadata['filters'] ?? []);
        $format = $metadata['format'];
        $filePath = self::DIRECTORY.'/'.$id.'.'.$format;
        abort_unless($disk->put($filePath, $renderer->render($format, $report)), 500, 'Não foi possível preparar novamente o relatório com as permissões vigentes.');
        $contents = $disk->get($filePath);
        $audit->record($request, 'metrics.export.downloaded', 'metrics_export', $id, ['format' => $format]);
        $disk->delete([$metadataPath, self::DIRECTORY.'/'.$id.'.xlsx', self::DIRECTORY.'/'.$id.'.pdf']);

        $filename = 'indicadores-'.now()->utc()->format('Ymd-His').'.'.$format;
        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf';

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizedActor(RequestAccessService $access): StaffAdmin
    {
        $actor = auth('staff_admins')->user();
        abort_unless($actor instanceof StaffAdmin && $actor->isActive() && in_array($actor->role, ['admin', 'cradt'], true), 403);
        abort_unless(in_array('view_request', $access->capabilitiesFor($actor), true), 403, 'Seu acesso aos indicadores foi revogado ou não está configurado.');

        return $actor;
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'campus_id' => ['nullable', 'uuid', 'exists:campuses,id'],
            'type_id' => ['nullable', 'uuid', 'exists:type_requests,id'],
            'sector' => ['nullable', Rule::in(['CRADT', 'COORDENACAO'])],
            'status' => ['nullable', Rule::in(RequestStatus::values())],
        ]);
    }

    private function purgeExpired($disk): void
    {
        foreach ($disk->allFiles(self::DIRECTORY) as $path) {
            if ($disk->lastModified($path) < now()->subMinutes(self::EXPIRATION_MINUTES)->timestamp) {
                $disk->delete($path);
            }
        }
    }
}
