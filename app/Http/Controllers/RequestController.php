<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Document;
use App\Models\FormSchemaVersion;
use App\Models\Message;
use App\Models\Request as RequestModel;
use App\Models\RequestDraft;
use App\Models\RequestEvent;
use App\Models\SlaPolicy;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\EnrollmentSnapshotService;
use App\Services\FormSchemaValidator;
use App\Services\NotificationService;
use App\Services\RequestAccessService;
use App\Services\ServiceAvailabilityService;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RequestController extends Controller
{
    /**
     * Lista os requerimentos com FILTROS
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();

        // Query base com relacionamentos
        $query = RequestModel::with(['user.course', 'type'])
            ->orderBy('created_at', 'desc');

        if ($user->role === 'staff') {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        // Se for aluno, vê apenas os seus (forçando string/UUID)
        if ($user->role === 'student') {
            $query->where('user_id', (string) $user->getJWTIdentifier());
        }

        // Se for coordenação, vê apenas os requerimentos do curso vinculado
        if ($user->role === 'coordenacao') {
            app(RequestAccessService::class)->constrainVisibleRequests($query, $user);
        }
        if ($user instanceof StaffAdmin && in_array($user->role, ['admin', 'cradt'], true)) {
            app(RequestAccessService::class)->constrainVisibleRequests($query, $user);
        }

        // Filtros
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->whereHas('user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        if ($request->filled('matricula')) {
            $matricula = $request->input('matricula');
            $query->whereHas('user', function ($q) use ($matricula) {
                $q->where('enrollment_number', 'like', "%{$matricula}%")
                    ->orWhere('matricula', 'like', "%{$matricula}%");
            });
        }

        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            app(RequestAccessService::class)->constrainCourse($query, $courseId);
        }

        $actorType = $user instanceof User ? 'student' : 'staff_admin';
        $actorId = $user->getKey();

        return $query->get()->each(function (RequestModel $item) use ($actorType, $actorId) {
            $item->unread_messages_count = Message::where('request_id', $item->id)
                ->where(fn ($q) => $q->where('sender_id', '!=', $actorId)->orWhere('sender_type', '!=', $actorType))
                ->whereDoesntHave('reads', fn ($q) => $q->where('reader_id', $actorId)->where('reader_type', $actorType))
                ->count();
        });
    }

    public function queue(Request $request)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'cradt', 'coordenacao'], true)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        $query = RequestModel::with(['user.course', 'type', 'assignedStaff', 'slaPolicy'])->whereNotIn('status', ['completed', 'canceled']);
        if ($user->role === 'coordenacao') {
            app(RequestAccessService::class)->constrainVisibleRequests($query, $user);
        } else {
            app(RequestAccessService::class)->constrainVisibleRequests($query, $user);
        }
        if ($request->filled('assigned')) {
            $request->input('assigned') === 'none' ? $query->whereNull('assigned_staff_id') : $query->where('assigned_staff_id', $request->input('assigned'));
        }
        if ($request->filled('sector')) {
            $query->where('responsible_sector', $request->input('sector'));
        }
        if ($request->input('filter') === 'waiting') {
            $query->where('status', 'waiting');
        }
        if ($request->input('filter') === 'overdue') {
            $query->whereNotNull('sla_resolution_due_at')->where('sla_resolution_due_at', '<', now());
        }
        $page = $query->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")->orderBy('created_at')->paginate(min((int) $request->input('per_page', 20), 100));
        $service = new SlaService;
        $page->getCollection()->transform(function (RequestModel $item) use ($service) {
            $item->sla_indicator = $service->indicator($item);

            return $item;
        });

        return response()->json($page);
    }

    public function assign(Request $request, string $id)
    {
        $actor = Auth::guard('staff_admins')->user() ?? $request->user();
        if (! $actor || ! in_array($actor->role, ['admin', 'cradt'], true)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        $target = RequestModel::with('assignedStaff')->findOrFail($id);
        if (! app(RequestAccessService::class)->canOperate($actor, $target)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (in_array($target->status?->value, ['completed', 'canceled'], true)) {
            return response()->json(['message' => 'Requerimento encerrado.'], 409);
        }
        $validated = $request->validate(['staff_id' => ['nullable', 'integer', 'exists:staff_admins,id'], 'reason' => ['nullable', 'string', 'max:1000']]);
        if ($validated['staff_id']) {
            $staff = StaffAdmin::findOrFail($validated['staff_id']);
            if (! in_array($staff->role, ['admin', 'cradt', 'coordenacao'], true)) {
                return response()->json(['message' => 'Atendente inelegível.'], 422);
            }
        }
        if ($validated['staff_id'] && $target->assigned_staff_id && (int) $target->assigned_staff_id !== (int) $validated['staff_id'] && trim((string) ($validated['reason'] ?? '')) === '') {
            return response()->json(['message' => 'Informe o motivo da redistribuição.'], 422);
        }
        DB::transaction(function () use ($target, $actor, $validated) {
            $from = $target->assigned_staff_id;
            $target->update(['assigned_staff_id' => $validated['staff_id'] ?? null]);
            RequestEvent::create(['request_id' => $target->id, 'event_type' => 'assignment_changed', 'actor_id' => $actor->getKey(), 'actor_type' => 'staff_admin', 'sector' => $target->responsible_sector, 'data' => ['from' => $from, 'to' => $validated['staff_id'] ?? null, 'reason' => $validated['reason'] ?? null], 'created_at' => now()]);
        });

        return response()->json(['message' => 'Atribuição atualizada.', 'assigned_staff_id' => $target->fresh()->assigned_staff_id]);
    }

    /**
     * Cria um novo requerimento
     */
    public function store(Request $request)
    {
        // Mantém type_id como contrato canônico, aceitando o nome legado
        // temporariamente para clientes antigos.
        if (! $request->filled('type_id') && $request->filled('type_request_id')) {
            $request->merge(['type_id' => $request->input('type_request_id')]);
        }
        if (! $request->filled('idempotency_key') && $request->header('Idempotency-Key')) {
            $request->merge(['idempotency_key' => $request->header('Idempotency-Key')]);
        }

        $validated = $request->validate([
            'type_id' => ['required', 'uuid', 'exists:type_requests,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'form_schema_version_id' => ['nullable', 'uuid', 'exists:form_schema_versions,id'],
            'form_responses' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'min:8', 'max:120'],
            'draft_id' => ['nullable', 'uuid', 'exists:request_drafts,id'],
            'enrollment_id' => ['nullable', 'uuid'],
            'document_ids' => ['sometimes', 'array'],
            'document_ids.*' => ['uuid', 'distinct', 'exists:documents,id'],
        ]);

        $documentIds = $validated['document_ids'] ?? [];
        $idempotencyKey = $validated['idempotency_key'] ?? null;
        $legacyPayloadHash = hash('sha256', json_encode([$validated['type_id'], $validated['subject'], $validated['description'], $validated['form_responses'] ?? [], $documentIds]));
        $payloadHash = hash('sha256', json_encode([$validated['type_id'], $validated['subject'], $validated['description'], $validated['form_responses'] ?? [], $documentIds, $validated['draft_id'] ?? null, $validated['enrollment_id'] ?? null]));
        $student = Auth::guard('api')->user();
        if (! app(ServiceAvailabilityService::class)->isOpen($validated['type_id'], $student)) {
            return response()->json(['message' => 'Este serviço não está disponível para envio neste período. Seu rascunho foi preservado.'], 422);
        }

        try {
            return DB::transaction(function () use ($request, $validated, $documentIds, $idempotencyKey, $payloadHash, $legacyPayloadHash) {
                // Requerimentos são criados por alunos da tabela users. Um JWT
                // antigo pode continuar no navegador depois de trocar/resetar o
                // banco; não deixe esse ID inválido chegar à FK.
                $user = Auth::guard('api')->user() ?? $request->user();
                if (! $user instanceof User || ! User::whereKey($user->getKey())->exists()) {
                    return response()->json([
                        'message' => 'Sessão inválida. Faça login novamente para enviar o requerimento.',
                    ], 401);
                }

                if ($user->role !== User::ROLE_STUDENT) {
                    return response()->json([
                        'message' => 'Apenas alunos podem criar requerimentos.',
                    ], 403);
                }

                // Serializa o envio por aluno enquanto a política de duplicidade
                // está sendo verificada, evitando corridas em bancos com row locks.
                $user = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                if ($idempotencyKey) {
                    $previous = RequestModel::where('submission_idempotency_key', $idempotencyKey)->first();
                    if ($previous) {
                        if (! $this->sameSubmission($previous, $user->id, $validated, $payloadHash, $legacyPayloadHash)) {
                            return response()->json(['message' => 'Chave de idempotência já utilizada com dados diferentes.'], 409);
                        }

                        return response()->json(['message' => 'Sucesso', 'id' => $previous->id, 'idempotent_replay' => true], 200);
                    }
                }

                $type = TypeRequest::findOrFail($validated['type_id']);
                $schema = FormSchemaVersion::where('type_request_id', $type->id)->where('status', 'published')->latest('version')->first();
                if ($schema && ($validated['form_schema_version_id'] ?? null) !== $schema->id) {
                    return response()->json([
                        'message' => 'O formulário deste serviço mudou. Revise os dados antes de enviar.',
                        'form_schema_version_id' => $schema->id,
                    ], 409);
                }
                if (! $schema && ! empty($validated['form_schema_version_id'])) {
                    return response()->json(['message' => 'Não existe formulário publicado para este serviço.'], 422);
                }
                $this->validateFormResponses($schema?->schema, $validated['form_responses'] ?? []);
                $submissionDraft = null;
                if (! empty($validated['draft_id'])) {
                    $submissionDraft = RequestDraft::whereKey($validated['draft_id'])->lockForUpdate()->firstOrFail();
                    if ($submissionDraft->user_id !== $user->id) {
                        return response()->json(['message' => 'Este rascunho não pertence ao usuário autenticado.'], 403);
                    }
                    if ($submissionDraft->discarded_at || $submissionDraft->submitted_at) {
                        return response()->json(['message' => 'Este rascunho não está mais disponível para envio.'], 409);
                    }
                    if ($submissionDraft->type_request_id !== $type->id || $submissionDraft->form_schema_version_id !== $schema?->id) {
                        return response()->json(['message' => 'O serviço ou formulário do rascunho mudou. Revise antes de enviar.'], 409);
                    }
                    if (! empty($validated['enrollment_id']) && $validated['enrollment_id'] !== $submissionDraft->enrollment_id) {
                        return response()->json(['message' => 'A matrícula informada não coincide com a matrícula salva no rascunho.'], 409);
                    }
                    if (! $submissionDraft->enrollment_id && $user->enrollments()->where('status', 'active')->limit(2)->count() > 1) {
                        return response()->json(['message' => 'Escolha a matrícula e salve o rascunho antes de enviar.'], 409);
                    }
                    $savedDocuments = $submissionDraft->document_ids ?? [];
                    $incomingDocuments = $documentIds;
                    sort($savedDocuments);
                    sort($incomingDocuments);
                    if ($savedDocuments !== $incomingDocuments) {
                        return response()->json(['message' => 'Os anexos do rascunho mudaram. Salve o rascunho antes de enviar.'], 409);
                    }
                }
                $documents = Document::whereIn('id', $documentIds)
                    ->where('user_id', $user->getKey())
                    ->get();

                if ($documents->count() !== count($documentIds)) {
                    return response()->json([
                        'message' => 'Um ou mais documentos não pertencem ao usuário autenticado.',
                    ], 403);
                }
                if ($documents->first(fn (Document $document) => ! Storage::disk('local')->exists($document->path) && ! Storage::disk('public')->exists($document->path))) {
                    return response()->json(['message' => 'Um anexo não está mais disponível. Envie-o novamente.'], 422);
                }

                if ($type->requires_document && $documents->isEmpty()) {
                    return response()->json([
                        'message' => 'Este tipo de requerimento exige pelo menos um documento.',
                    ], 422);
                }

                $policy = SlaPolicy::where('status', 'active')->where(function ($q) use ($type) {
                    $q->where('type_request_id', $type->id)->orWhereNull('type_request_id');
                })->orderByRaw('CASE WHEN type_request_id IS NULL THEN 1 ELSE 0 END')->latest('version')->first();
                $createdAt = now();
                $enrollment = $submissionDraft
                    ? ['course_id' => $submissionDraft->course_id, 'campus_id' => $submissionDraft->campus_id, 'enrollment_id' => $submissionDraft->enrollment_id]
                    : app(EnrollmentSnapshotService::class)->forStudent($user, $validated['enrollment_id'] ?? null);
                if ($type->duplicate_window_days) {
                    $cutoff = now()->subDays($type->duplicate_window_days);
                    $candidates = RequestModel::query()
                        ->where('user_id', (string) $user->getJWTIdentifier())
                        ->where('type_id', $type->id)
                        ->whereNotIn('status', [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value])
                        ->where('created_at', '>=', $cutoff)
                        ->when($enrollment['enrollment_id'], fn ($query, $enrollmentId) => $query->where('enrollment_id', $enrollmentId))
                        ->when(! $enrollment['enrollment_id'], fn ($query) => $query->whereNull('enrollment_id'))
                        ->get(['id', 'protocol', 'subject']);
                    $normalizeSubject = static fn (string $subject): string => mb_strtolower(preg_replace('/\\s+/u', ' ', trim($subject)) ?? trim($subject));
                    $duplicate = $candidates->first(fn (RequestModel $candidate): bool => $normalizeSubject($candidate->subject) === $normalizeSubject($validated['subject']));
                    if ($duplicate) {
                        return response()->json([
                            'message' => 'Já existe um requerimento em aberto para este serviço, matrícula e assunto dentro do prazo configurado.',
                            'duplicate' => ['id' => $duplicate->id, 'protocol' => $duplicate->protocol],
                        ], 409);
                    }
                }
                $newRequest = RequestModel::create([
                    'user_id' => (string) $user->getJWTIdentifier(), // 🔥 Blinda contra conversões numéricas (1, 3, etc)
                    ...$enrollment,
                    'type_id' => $validated['type_id'],
                    'form_schema_version_id' => $schema?->id,
                    'subject' => $validated['subject'],
                    'description' => $validated['description'],
                    'form_responses' => $validated['form_responses'] ?? null,
                    'status' => \App\Enums\RequestStatus::PENDING,
                    'protocol' => $this->generateProtocol(),
                    'submission_idempotency_key' => $idempotencyKey,
                    'submission_payload_hash' => $idempotencyKey ? $payloadHash : null,
                    'sla_policy_id' => $policy?->id,
                    'sla_policy_version' => $policy?->version,
                    'sla_first_response_due_at' => $policy?->first_response_minutes ? $createdAt->copy()->addMinutes($policy->first_response_minutes) : null,
                    'sla_resolution_due_at' => $policy?->resolution_minutes ? $createdAt->copy()->addMinutes($policy->resolution_minutes) : null,
                ]);

                if ($documents->isNotEmpty()) {
                    $newRequest->documents()->sync($documents->modelKeys());
                }

                RequestEvent::create([
                    'request_id' => $newRequest->id,
                    'event_type' => 'created',
                    'actor_id' => $user->getKey(),
                    'actor_type' => 'student',
                    'sector' => 'CRADT',
                    'data' => ['status' => RequestStatus::PENDING->value],
                    'created_at' => now(),
                ]);

                if ($submissionDraft) {
                    $submissionDraft->update(['submitted_at' => now(), 'request_id' => $newRequest->id]);
                }

                return response()->json(['message' => 'Sucesso', 'id' => $newRequest->id], 201);
            });
        } catch (HttpExceptionInterface|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($idempotencyKey) {
                try {
                    $previous = RequestModel::where('submission_idempotency_key', $idempotencyKey)->first();
                    if ($previous) {
                        $actor = Auth::guard('api')->user() ?? $request->user();
                        if (! $this->sameSubmission($previous, $actor?->getKey(), $validated, $payloadHash, $legacyPayloadHash)) {
                            return response()->json(['message' => 'Chave de idempotência já utilizada com dados diferentes.'], 409);
                        }

                        return response()->json(['message' => 'Sucesso', 'id' => $previous->id, 'idempotent_replay' => true], 200);
                    }
                } catch (\Exception $lookupError) {
                    report($lookupError);
                }
            }
            report($e);

            // Se a criação falhar, remove apenas uploads do usuário que ainda
            // não estejam vinculados a outro requerimento.
            if ($documentIds) {
                $orphanDocuments = Document::whereIn('id', $documentIds)
                    ->whereHas('user', fn ($query) => $query->whereKey(optional(Auth::guard('api')->user() ?? $request->user())->getKey()))
                    ->whereDoesntHave('requests')
                    ->get();
                foreach ($orphanDocuments as $document) {
                    if (RequestDraft::whereNull('discarded_at')->whereNull('submitted_at')->whereJsonContains('document_ids', $document->id)->exists()) {
                        continue;
                    }
                    foreach (['local', 'public'] as $disk) {
                        Storage::disk($disk)->delete($document->path);
                    }
                    $document->delete();
                }
            }

            return response()->json([
                'message' => 'Não foi possível registrar o requerimento. Tente novamente.',
            ], 500);
        }
    }

    private function generateProtocol(): string
    {
        do {
            $protocol = now()->format('Ymd').'-'.random_int(100000, 999999);
        } while (RequestModel::where('protocol', $protocol)->exists());

        return $protocol;
    }

    private function validateFormResponses(?array $schema, array $responses): void
    {
        if (! $schema) {
            return;
        }
        app(FormSchemaValidator::class)->validateResponses($schema, $responses);
    }

    /**
     * Mostra detalhes de um requerimento
     */
    public function show(Request $request, $id)
    {
        $user = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
        $requestModel = RequestModel::with(['user.course', 'type', 'documents'])->findOrFail($id);

        if ($user instanceof User || $user instanceof StaffAdmin) {
            if (app(RequestAccessService::class)->canView($user, $requestModel)) {
                $capabilities = app(RequestAccessService::class)->capabilitiesFor($user, $requestModel);
                $canReviewDocuments = $user instanceof User || in_array('review_document', $capabilities, true);
                $requestModel->setAttribute('effective_capabilities', $capabilities);
                $requestModel->setAttribute('can_review_documents', $canReviewDocuments);
                if (! $canReviewDocuments) {
                    $requestModel->setRelation('documents', $requestModel->documents()->getRelated()->newCollection());
                }

                return response()->json($requestModel);
            }
        }

        return response()->json(['message' => 'Acesso não autorizado'], 403);
    }

    /**
     * Atualiza status/observação de um requerimento
     */
    public function update(Request $request, $id)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        $req = RequestModel::findOrFail($id);

        if (! in_array($user->role, ['admin', 'cradt'])) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (! app(RequestAccessService::class)->canOperate($user, $req, 'decide_request')) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);

        $currentStatus = $req->status instanceof RequestStatus ? $req->status->value : (string) $req->status;
        $nextStatus = $validated['status'];
        $transitions = [
            RequestStatus::PENDING->value => [RequestStatus::ANALYZING->value, RequestStatus::CANCELED->value],
            RequestStatus::ANALYZING->value => [RequestStatus::WAITING->value, RequestStatus::SOLVING->value, RequestStatus::CANCELED->value],
            RequestStatus::WAITING->value => [RequestStatus::ANALYZING->value, RequestStatus::SOLVING->value, RequestStatus::CANCELED->value],
            RequestStatus::SOLVING->value => [RequestStatus::WAITING->value, RequestStatus::CANCELED->value],
        ];
        if (! in_array($nextStatus, $transitions[$currentStatus] ?? [], true)) {
            return response()->json(['message' => 'Transição de status não permitida. Use a rota de decisão para concluir o requerimento.'], 409);
        }
        $observation = trim((string) ($validated['observation'] ?? ''));
        if ($nextStatus === RequestStatus::WAITING->value && $observation === '') {
            return response()->json(['message' => 'Informe ao aluno qual pendência precisa ser resolvida.'], 422);
        }
        if ($nextStatus === RequestStatus::CANCELED->value && $observation === '') {
            return response()->json(['message' => 'Informe o motivo do cancelamento.'], 422);
        }

        DB::transaction(function () use ($req, $user, $validated, $currentStatus, $nextStatus, $observation): void {
            $req->update([
                'status' => $nextStatus,
                'observation' => $validated['observation'] ?? $req->observation,
            ]);
            RequestEvent::create([
                'request_id' => $req->id,
                'event_type' => $nextStatus === RequestStatus::CANCELED->value ? 'canceled' : 'status_changed',
                'actor_id' => $user->getKey(),
                'actor_type' => 'staff_admin',
                'sector' => $req->responsible_sector,
                'data' => ['from' => $currentStatus, 'to' => $nextStatus, 'reason' => $nextStatus === RequestStatus::CANCELED->value ? $observation : null],
                'created_at' => now(),
            ]);
            if ($observation !== '') {
                Message::create([
                    'request_id' => $req->id,
                    'sender_id' => $user->getKey(),
                    'sender_type' => 'staff_admin',
                    'content' => $observation,
                ]);
            }
        });
        if ($req->user && $user instanceof StaffAdmin) {
            app(NotificationService::class)->forStudent($req->user, 'status', 'Atualização do requerimento', 'O status do seu requerimento foi atualizado.', $req, 'status:'.$req->id.':'.$validated['status'].':'.($req->updated_at?->timestamp ?? time()));
        }

        return response()->json(['message' => 'Atualizado']);
    }

    public function events(Request $request, string $id)
    {
        $target = RequestModel::with('events')->findOrFail($id);
        $user = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
        if (! ($user instanceof User || $user instanceof StaffAdmin) || ! app(RequestAccessService::class)->canView($user, $target)) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        return response()->json($target->events);
    }

    public function forward(Request $request, string $id)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        $target = RequestModel::findOrFail($id);
        if (! $user instanceof StaffAdmin || ! in_array($user->role, ['admin', 'cradt', 'coordenacao'], true)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (! app(RequestAccessService::class)->canOperate($user, $target, 'forward_request')) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (in_array($target->status->value, [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value], true)) {
            return response()->json(['message' => 'Requerimento encerrado.'], 409);
        }
        $validated = $request->validate(['sector' => ['required', 'string', 'max:80'], 'reason' => ['required', 'string', 'max:2000']]);
        if (! in_array($validated['sector'], ['CRADT', 'COORDENACAO'], true)) {
            return response()->json(['message' => 'Setor de destino inválido.'], 422);
        }
        if (strcasecmp($validated['sector'], (string) $target->responsible_sector) === 0) {
            return response()->json(['message' => 'O setor de destino deve ser diferente do atual.'], 422);
        }
        $old = $target->responsible_sector;
        DB::transaction(function () use ($target, $user, $validated, $old) {
            $target->update(['responsible_sector' => $validated['sector']]);
            RequestEvent::create(['request_id' => $target->id, 'event_type' => 'forwarded', 'actor_id' => $user->getKey(), 'actor_type' => 'staff_admin', 'sector' => $old, 'data' => ['from' => $old, 'to' => $validated['sector'], 'reason' => $validated['reason']], 'created_at' => now()]);
        });

        return response()->json(['message' => 'Requerimento encaminhado.', 'responsible_sector' => $target->responsible_sector]);
    }

    public function decision(Request $request, string $id)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        $target = RequestModel::findOrFail($id);
        if (! $user instanceof StaffAdmin || ! in_array($user->role, ['admin', 'cradt'], true)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (! app(RequestAccessService::class)->canOperate($user, $target, 'decide_request')) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        $validated = $request->validate(['result' => ['required', Rule::in(['deferido', 'indeferido', 'na'])], 'justification' => ['required', 'string', 'max:5000'], 'conclusion_summary' => ['required', 'string', 'max:5000']]);
        if (in_array($target->status->value, [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value], true)) {
            return response()->json(['message' => 'Requerimento encerrado.'], 409);
        }
        if ($validated['result'] === 'deferido' && $target->type?->requires_document && ! $target->documents()->exists()) {
            return response()->json(['message' => 'Não é possível deferir: o documento obrigatório não está anexado.'], 422);
        }
        if ($validated['result'] === 'na' && $target->type?->requires_decision) {
            return response()->json(['message' => 'Este serviço exige uma decisão de deferimento ou indeferimento.'], 422);
        }
        DB::transaction(function () use ($target, $user, $validated) {
            $target->update(['status' => RequestStatus::COMPLETED, 'result' => $validated['result'], 'observation' => $validated['justification'], 'conclusion_summary' => $validated['conclusion_summary']]);
            RequestEvent::create(['request_id' => $target->id, 'event_type' => 'final_decision', 'actor_id' => $user->getKey(), 'actor_type' => 'staff_admin', 'sector' => $target->responsible_sector, 'data' => $validated, 'created_at' => now()]);
            Message::create(['request_id' => $target->id, 'sender_id' => $user->getKey(), 'sender_type' => 'staff_admin', 'content' => $validated['conclusion_summary']]);
        });
        if ($target->user) {
            app(NotificationService::class)->forStudent(
                $target->user,
                'status',
                'Decisão registrada',
                $validated['conclusion_summary'],
                $target,
                'decision:'.$target->id.':'.$validated['result'],
            );
        }

        return response()->json(['message' => 'Decisão registrada.']);
    }

    public function returnToPrevious(Request $request, string $id)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        $target = RequestModel::findOrFail($id);
        if (! $user instanceof StaffAdmin || ! in_array($user->role, ['admin', 'cradt', 'coordenacao'], true)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (! app(RequestAccessService::class)->canOperate($user, $target, 'forward_request')) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        if (in_array($target->status->value, [RequestStatus::COMPLETED->value, RequestStatus::CANCELED->value], true)) {
            return response()->json(['message' => 'Requerimento encerrado.'], 409);
        }
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $lastForward = $target->events()
            ->where('event_type', 'forwarded')
            ->latest('created_at')
            ->first();
        $previous = $lastForward?->data['from'] ?? null;
        if (! $previous || strcasecmp($previous, (string) $target->responsible_sector) === 0) {
            return response()->json(['message' => 'Não existe setor anterior válido para devolução.'], 422);
        }
        $current = $target->responsible_sector;
        DB::transaction(function () use ($target, $user, $validated, $previous, $current) {
            $target->update(['responsible_sector' => $previous]);
            RequestEvent::create(['request_id' => $target->id, 'event_type' => 'returned', 'actor_id' => $user->getKey(), 'actor_type' => 'staff_admin', 'sector' => $current, 'data' => ['from' => $current, 'to' => $previous, 'reason' => $validated['reason']], 'created_at' => now()]);
        });

        return response()->json(['message' => 'Requerimento devolvido.', 'responsible_sector' => $target->responsible_sector]);
    }

    /**
     * Deleta um requerimento
     */
    public function destroy($id)
    {
        RequestModel::findOrFail($id)->delete();

        return response()->json(['message' => 'Deletado']);
    }

    private function authenticatedUser(Request $request): mixed
    {
        return Auth::guard('staff_admins')->user()
            ?? Auth::guard('api')->user()
            ?? $request->user();
    }

    private function sameSubmission(RequestModel $previous, ?string $studentId, array $validated, string $payloadHash, string $legacyPayloadHash): bool
    {
        if ($previous->user_id !== $studentId) {
            return false;
        }
        if (hash_equals((string) $previous->submission_payload_hash, $payloadHash)) {
            return true;
        }
        // Replays de versões anteriores usavam um hash sem matrícula/rascunho.
        // Só aceitamos esse formato quando não há outra matrícula explícita e
        // o rascunho informado realmente originou o pedido anterior.
        if (! empty($validated['enrollment_id']) || ! hash_equals((string) $previous->submission_payload_hash, $legacyPayloadHash)) {
            return false;
        }

        return empty($validated['draft_id']) || RequestDraft::whereKey($validated['draft_id'])
            ->where('user_id', $studentId)->where('request_id', $previous->id)->exists();
    }
}
