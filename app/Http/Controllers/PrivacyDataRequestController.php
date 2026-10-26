<?php

namespace App\Http\Controllers;

use App\Models\PrivacyDataRequest;
use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivacyDataRequestController extends Controller
{
    private const REQUEST_TYPES = ['access', 'correction', 'erasure'];

    public function index(Request $request)
    {
        $student = $this->student();

        return response()->json(
            PrivacyDataRequest::query()->where('user_id', $student->id)->latest()->paginate(20)
        );
    }

    public function store(Request $request)
    {
        $student = $this->student();
        $data = $request->validate([
            'request_type' => ['required', 'string', 'in:'.implode(',', self::REQUEST_TYPES)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'protocol' => ['prohibited'],
            'user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'response_summary' => ['prohibited'],
            'rejection_reason' => ['prohibited'],
            'responded_by' => ['prohibited'],
            'responded_at' => ['prohibited'],
            'attachments' => ['prohibited'],
            'documents' => ['prohibited'],
            'document_ids' => ['prohibited'],
        ]);

        $created = DB::transaction(function () use ($request, $student, $data): PrivacyDataRequest {
            $record = PrivacyDataRequest::create([
                'protocol' => $this->newProtocol(),
                'user_id' => $student->id,
                'request_type' => $data['request_type'],
                'description' => trim($data['description']),
                'status' => PrivacyDataRequest::STATUS_SUBMITTED,
            ]);

            app(AuditService::class)->record($request, 'privacy_data_request_created', 'privacy_data_request', $record->id, [
                'request_type' => $record->request_type,
                'status' => $record->status,
            ]);

            return $record;
        });

        return response()->json($created, 201);
    }

    public function showMine(Request $request, PrivacyDataRequest $privacyDataRequest)
    {
        $student = $this->student();
        abort_unless($privacyDataRequest->user_id === $student->id, 404);

        return response()->json($privacyDataRequest);
    }

    public function adminIndex(Request $request)
    {
        $this->globalAdmin();
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:submitted,in_review,completed,rejected'],
            'request_type' => ['nullable', 'string', 'in:'.implode(',', self::REQUEST_TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = PrivacyDataRequest::query()
            ->with('user:id,name,matricula')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['request_type'] ?? null, fn (Builder $query, string $type) => $query->where('request_type', $type))
            ->latest();

        $results = $query->paginate((int) ($filters['per_page'] ?? 20));
        app(AuditService::class)->record($request, 'privacy_data_requests_viewed', 'privacy_data_request', null, [
            'filters' => $filters,
            'page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'result_count' => $results->count(),
        ]);

        return response()->json($results);
    }

    public function adminShow(Request $request, PrivacyDataRequest $privacyDataRequest)
    {
        $this->globalAdmin();

        app(AuditService::class)->record($request, 'privacy_data_request_viewed', 'privacy_data_request', $privacyDataRequest->id);

        return response()->json($privacyDataRequest->load([
            'user:id,name,matricula',
            'responder:id,name,role',
        ]));
    }

    public function transition(Request $request, PrivacyDataRequest $privacyDataRequest)
    {
        $admin = $this->globalAdmin();
        $data = $request->validate([
            'status' => ['required', 'string', 'in:in_review,completed,rejected'],
            'response_summary' => ['nullable', 'string', 'max:1200'],
            'rejection_reason' => ['nullable', 'string', 'max:800'],
            'responded_by' => ['prohibited'],
            'responded_at' => ['prohibited'],
            'user_id' => ['prohibited'],
            'protocol' => ['prohibited'],
        ]);

        $targetStatus = $data['status'];
        $responseSummary = trim((string) ($data['response_summary'] ?? ''));
        $rejectionReason = trim((string) ($data['rejection_reason'] ?? ''));

        if (in_array($targetStatus, [PrivacyDataRequest::STATUS_COMPLETED, PrivacyDataRequest::STATUS_REJECTED], true) && $responseSummary === '') {
            throw ValidationException::withMessages(['response_summary' => 'Informe um resumo da resposta antes de concluir a análise.']);
        }
        if ($targetStatus === PrivacyDataRequest::STATUS_REJECTED && $rejectionReason === '') {
            throw ValidationException::withMessages(['rejection_reason' => 'Informe o motivo da rejeição.']);
        }
        if ($targetStatus !== PrivacyDataRequest::STATUS_REJECTED && $rejectionReason !== '') {
            throw ValidationException::withMessages(['rejection_reason' => 'O motivo de rejeição só pode ser informado ao rejeitar a solicitação.']);
        }
        if ($targetStatus === PrivacyDataRequest::STATUS_IN_REVIEW && $responseSummary !== '') {
            throw ValidationException::withMessages(['response_summary' => 'A resposta só pode ser registrada ao concluir ou rejeitar a solicitação.']);
        }

        $updated = DB::transaction(function () use ($request, $privacyDataRequest, $admin, $targetStatus, $responseSummary, $rejectionReason): PrivacyDataRequest {
            $record = PrivacyDataRequest::query()->whereKey($privacyDataRequest->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $record->status;
            $allowed = [
                PrivacyDataRequest::STATUS_SUBMITTED => [PrivacyDataRequest::STATUS_IN_REVIEW],
                PrivacyDataRequest::STATUS_IN_REVIEW => [PrivacyDataRequest::STATUS_COMPLETED, PrivacyDataRequest::STATUS_REJECTED],
                PrivacyDataRequest::STATUS_COMPLETED => [],
                PrivacyDataRequest::STATUS_REJECTED => [],
            ];

            if (! in_array($targetStatus, $allowed[$previousStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Esta mudança de etapa não é permitida. Atualize a solicitação e tente novamente.']);
            }

            $record->status = $targetStatus;
            if (in_array($targetStatus, [PrivacyDataRequest::STATUS_COMPLETED, PrivacyDataRequest::STATUS_REJECTED], true)) {
                $record->response_summary = $responseSummary;
                $record->rejection_reason = $targetStatus === PrivacyDataRequest::STATUS_REJECTED ? $rejectionReason : null;
                $record->responded_by = $admin->id;
                $record->responded_at = now();
            }
            $record->save();

            app(AuditService::class)->record($request, 'privacy_data_request_transitioned', 'privacy_data_request', $record->id, [
                'previous_status' => $previousStatus,
                'new_status' => $targetStatus,
                'response_summary_recorded' => $record->response_summary !== null,
                'rejection_reason_recorded' => $record->rejection_reason !== null,
            ]);

            return $record->load(['user:id,name,matricula', 'responder:id,name,role']);
        });

        return response()->json($updated);
    }

    private function student(): User
    {
        $actor = Auth::guard('api')->user();
        abort_unless($actor instanceof User && $actor->isStudent(), 403);

        return $actor;
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
                app(AuditService::class)->record(request(), 'privacy_data_request_access_denied', 'privacy_data_request', null, [
                    'reason' => ! $actor->isActive() ? 'inactive_account' : ($actor->role !== 'admin' ? 'insufficient_role' : 'scoped_account'),
                ], 'denied');
            }

            abort(403);
        }

        return $actor;
    }

    private function newProtocol(): string
    {
        return 'PRV-'.now()->utc()->format('Ymd').'-'.Str::upper(Str::random(10));
    }
}
