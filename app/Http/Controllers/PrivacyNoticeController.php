<?php

namespace App\Http\Controllers;

use App\Models\PrivacyNotice;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrivacyNoticeController extends Controller
{
    public function current()
    {
        $notice = PrivacyNotice::query()
            ->where('status', PrivacyNotice::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->whereNotNull('reviewer_id')
            ->whereNotNull('reviewed_at')
            ->whereNotNull('controller_name')
            ->whereNotNull('contact_channel')
            ->whereNotNull('legal_basis_summary')
            ->whereNotNull('retention_summary')
            ->whereNotNull('source_reference')
            ->whereNotNull('institutional_approval_reference')
            ->whereNotNull('effective_date')
            ->orderByDesc('version')
            ->first([
                'id', 'version', 'title', 'content', 'controller_name', 'contact_channel',
                'legal_basis_summary', 'retention_summary', 'source_reference',
                'institutional_approval_reference', 'effective_date', 'reviewed_at', 'published_at',
            ]);

        if (! $notice) {
            return response()->json(['status' => 'pending_approval', 'notice' => null]);
        }

        return response()->json(['status' => 'published', 'notice' => $notice]);
    }

    public function adminIndex(Request $request)
    {
        $this->globalAdmin();
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,review,published'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notices = PrivacyNotice::query()
            ->with(['author:id,name', 'reviewer:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('version')
            ->paginate((int) ($filters['per_page'] ?? 25));

        return response()->json($notices);
    }

    public function store(Request $request)
    {
        $admin = $this->globalAdmin();
        $data = $this->draftData($request);

        $notice = DB::transaction(function () use ($request, $admin, $data): PrivacyNotice {
            $record = PrivacyNotice::create($data + [
                'version' => $this->nextVersion(),
                'status' => PrivacyNotice::STATUS_DRAFT,
                'author_id' => $admin->id,
            ]);

            $this->audit($request, 'privacy_notice_created', $record, ['status' => $record->status]);

            return $record->load(['author:id,name', 'reviewer:id,name']);
        });

        return response()->json($notice, 201);
    }

    public function update(Request $request, PrivacyNotice $privacyNotice)
    {
        $this->globalAdmin();
        $data = $this->draftData($request);
        $record = DB::transaction(function () use ($request, $privacyNotice, $data): PrivacyNotice {
            $record = PrivacyNotice::query()->whereKey($privacyNotice->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === PrivacyNotice::STATUS_DRAFT, 409, 'Somente rascunhos podem ser editados. Crie uma nova versão para alterar um aviso publicado.');
            $record->fill($data)->save();
            $this->audit($request, 'privacy_notice_draft_updated', $record, ['status' => $record->status]);

            return $record;
        });

        return response()->json($record->fresh(['author:id,name', 'reviewer:id,name']));
    }

    public function submitForReview(Request $request, PrivacyNotice $privacyNotice)
    {
        $this->globalAdmin();
        $record = DB::transaction(function () use ($request, $privacyNotice): PrivacyNotice {
            $record = PrivacyNotice::query()->whereKey($privacyNotice->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === PrivacyNotice::STATUS_DRAFT, 409, 'Somente um rascunho pode ser enviado para revisão.');
            abort_if(trim($record->title) === '' || trim($record->content) === '', 422, 'Preencha o título e o conteúdo antes da revisão.');
            $record->forceFill([
                'status' => PrivacyNotice::STATUS_REVIEW,
                'review_submitted_at' => now(),
            ])->save();
            $this->audit($request, 'privacy_notice_submitted_for_review', $record, ['status' => $record->status]);

            return $record;
        });

        return response()->json($record->fresh(['author:id,name', 'reviewer:id,name']));
    }

    public function returnToDraft(Request $request, PrivacyNotice $privacyNotice)
    {
        $this->globalAdmin();
        $record = DB::transaction(function () use ($request, $privacyNotice): PrivacyNotice {
            $record = PrivacyNotice::query()->whereKey($privacyNotice->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === PrivacyNotice::STATUS_REVIEW, 409, 'Somente um aviso em revisão pode voltar para rascunho.');
            $record->forceFill([
                'status' => PrivacyNotice::STATUS_DRAFT,
                'review_submitted_at' => null,
            ])->save();
            $this->audit($request, 'privacy_notice_returned_to_draft', $record, ['status' => $record->status]);

            return $record;
        });

        return response()->json($record->fresh(['author:id,name', 'reviewer:id,name']));
    }

    public function publish(Request $request, PrivacyNotice $privacyNotice)
    {
        $admin = $this->globalAdmin();
        abort_unless($privacyNotice->status === PrivacyNotice::STATUS_REVIEW, 409, 'Somente uma versão em revisão pode ser publicada.');

        $data = $request->validate([
            'controller_name' => ['required', 'string', 'max:255'],
            'contact_channel' => ['required', 'string', 'max:255'],
            'legal_basis_summary' => ['required', 'string', 'min:20', 'max:4000'],
            'retention_summary' => ['required', 'string', 'min:20', 'max:4000'],
            'source_reference' => ['required', 'string', 'min:3', 'max:500'],
            'institutional_approval_reference' => ['required', 'string', 'min:3', 'max:500'],
            'effective_date' => ['required', 'date'],
            'reviewer_id' => ['prohibited'],
            'reviewed_at' => ['prohibited'],
            'published_at' => ['prohibited'],
            'status' => ['prohibited'],
            'version' => ['prohibited'],
            'author_id' => ['prohibited'],
        ]);

        $notice = DB::transaction(function () use ($request, $privacyNotice, $admin, $data): PrivacyNotice {
            $record = PrivacyNotice::query()->whereKey($privacyNotice->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === PrivacyNotice::STATUS_REVIEW, 409, 'A versão já mudou de etapa. Atualize a lista e tente novamente.');
            abort_if(trim($record->title) === '' || trim($record->content) === '', 422, 'Preencha o título e o conteúdo antes de publicar.');

            $record->fill($data);
            $record->forceFill([
                'status' => PrivacyNotice::STATUS_PUBLISHED,
                'reviewer_id' => $admin->id,
                'reviewed_at' => now(),
                'published_at' => now(),
            ])->save();

            $this->audit($request, 'privacy_notice_published', $record, [
                'status' => $record->status,
                'version' => $record->version,
                'institutional_approval_reference_recorded' => true,
            ]);

            return $record->load(['author:id,name', 'reviewer:id,name']);
        });

        return response()->json($notice);
    }

    public function newVersion(Request $request, PrivacyNotice $privacyNotice)
    {
        $admin = $this->globalAdmin();
        abort_unless($privacyNotice->status === PrivacyNotice::STATUS_PUBLISHED, 409, 'Uma nova versão deve partir de uma versão publicada.');

        $notice = DB::transaction(function () use ($request, $privacyNotice, $admin): PrivacyNotice {
            $record = PrivacyNotice::create([
                'version' => $this->nextVersion(),
                'title' => $privacyNotice->title,
                'content' => $privacyNotice->content,
                'controller_name' => $privacyNotice->controller_name,
                'contact_channel' => $privacyNotice->contact_channel,
                'legal_basis_summary' => $privacyNotice->legal_basis_summary,
                'retention_summary' => $privacyNotice->retention_summary,
                // Source, approval and dates must be supplied again for every new version.
                'status' => PrivacyNotice::STATUS_DRAFT,
                'author_id' => $admin->id,
            ]);

            $this->audit($request, 'privacy_notice_new_version_created', $record, [
                'version' => $record->version,
                'source_version' => $privacyNotice->version,
                'status' => $record->status,
            ]);

            return $record->load(['author:id,name', 'reviewer:id,name']);
        });

        return response()->json($notice, 201);
    }

    private function draftData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'min:20', 'max:20000'],
            'controller_name' => ['nullable', 'string', 'max:255'],
            'contact_channel' => ['nullable', 'string', 'max:255'],
            'legal_basis_summary' => ['nullable', 'string', 'max:4000'],
            'retention_summary' => ['nullable', 'string', 'max:4000'],
            'source_reference' => ['nullable', 'string', 'max:500'],
            'institutional_approval_reference' => ['nullable', 'string', 'max:500'],
            'effective_date' => ['nullable', 'date'],
            'version' => ['prohibited'],
            'status' => ['prohibited'],
            'author_id' => ['prohibited'],
            'reviewer_id' => ['prohibited'],
            'review_submitted_at' => ['prohibited'],
            'reviewed_at' => ['prohibited'],
            'published_at' => ['prohibited'],
        ]);
    }

    private function globalAdmin(): StaffAdmin
    {
        $actor = Auth::guard('staff_admins')->user();
        $authorized =
            $actor instanceof StaffAdmin
            && $actor->isActive()
            && $actor->role === 'admin'
            && ! $actor->accessScopes()->exists();
        if (! $authorized) {
            if ($actor instanceof StaffAdmin) {
                app(AuditService::class)->record(request(), 'privacy_notice_access_denied', 'privacy_notice', null, [
                    'reason' => ! $actor->isActive() ? 'inactive_account' : ($actor->role !== 'admin' ? 'insufficient_role' : 'scoped_account'),
                ], 'denied');
            }

            abort(403);
        }

        return $actor;
    }

    private function nextVersion(): int
    {
        return ((int) PrivacyNotice::query()->lockForUpdate()->max('version')) + 1;
    }

    private function audit(Request $request, string $action, PrivacyNotice $notice, array $metadata): void
    {
        app(AuditService::class)->record($request, $action, 'privacy_notice', $notice->id, $metadata);
    }
}
