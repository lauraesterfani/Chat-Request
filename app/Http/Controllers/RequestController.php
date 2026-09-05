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
use App\Services\NotificationService;
use App\Services\RequestAccessService;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('course_id', $user->course_id);
            });
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
            $query->whereHas('user', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
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
            $query->whereHas('user', fn ($q) => $q->where('course_id', $user->course_id));
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

        $validated = $request->validate([
            'type_id' => ['required', 'uuid', 'exists:type_requests,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'form_schema_version_id' => ['nullable', 'uuid', 'exists:form_schema_versions,id'],
            'form_responses' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'min:8', 'max:120'],
            'draft_id' => ['nullable', 'uuid', 'exists:request_drafts,id'],
            'document_ids' => ['sometimes', 'array'],
            'document_ids.*' => ['uuid', 'distinct', 'exists:documents,id'],
        ]);

        $documentIds = $validated['document_ids'] ?? [];

        try {
            return DB::transaction(function () use ($request, $validated, $documentIds) {
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

                $idempotencyKey = $validated['idempotency_key'] ?? $request->header('Idempotency-Key');
                $payloadHash = hash('sha256', json_encode([$validated['type_id'], $validated['subject'], $validated['description'], $validated['form_responses'] ?? [], $documentIds]));
                if ($idempotencyKey) {
                    $previous = RequestModel::where('submission_idempotency_key', $idempotencyKey)->first();
                    if ($previous) {
                        if ($previous->user_id !== $user->id || $previous->submission_payload_hash !== $payloadHash) {
                            return response()->json(['message' => 'Chave de idempotência já utilizada com dados diferentes.'], 409);
                        }

                        return response()->json(['message' => 'Sucesso', 'id' => $previous->id, 'idempotent_replay' => true], 200);
                    }
                }

                $type = TypeRequest::findOrFail($validated['type_id']);
                $schema = ! empty($validated['form_schema_version_id']) ? FormSchemaVersion::where('id', $validated['form_schema_version_id'])->where('type_request_id', $type->id)->where('status', 'published')->firstOrFail() : null;
                $this->validateFormResponses($schema?->schema, $validated['form_responses'] ?? []);
                $documents = Document::whereIn('id', $documentIds)
                    ->where('user_id', $user->getKey())
                    ->get();

                if ($documents->count() !== count($documentIds)) {
                    return response()->json([
                        'message' => 'Um ou mais documentos não pertencem ao usuário autenticado.',
                    ], 403);
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
                $newRequest = RequestModel::create([
                    'user_id' => (string) $user->getJWTIdentifier(), // 🔥 Blinda contra conversões numéricas (1, 3, etc)
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

                if (! empty($validated['draft_id'])) {
                    RequestDraft::where('id', $validated['draft_id'])->where('user_id', $user->id)->whereNull('submitted_at')->update(['submitted_at' => now(), 'request_id' => $newRequest->id]);
                }

                return response()->json(['message' => 'Sucesso', 'id' => $newRequest->id], 201);
            });
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);

            // Se a criação falhar, remove apenas uploads do usuário que ainda
            // não estejam vinculados a outro requerimento.
            if ($documentIds) {
                $orphanDocuments = Document::whereIn('id', $documentIds)
                    ->whereHas('user', fn ($query) => $query->whereKey(optional(Auth::guard('api')->user() ?? $request->user())->getKey()))
                    ->whereDoesntHave('requests')
                    ->get();
                foreach ($orphanDocuments as $document) {
                    Storage::disk('public')->delete($document->path);
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
        $fields = collect($schema['fields'] ?? []);
        $allowed = $fields->pluck('key')->all();
        abort_if(array_diff(array_keys($responses), $allowed), 422, 'O formulário contém campos desconhecidos.');
        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $responses[$key] ?? null;
            abort_if(! empty($field['required']) && ($value === null || $value === '' || $value === []), 422, "Preencha {$field['label']}.");
            if ($value === null) {
                continue;
            }
            abort_if($field['type'] === 'number' && ! is_numeric($value), 422, "Valor inválido em {$field['label']}.");
            abort_if($field['type'] === 'date' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value), 422, "Data inválida em {$field['label']}.");
            abort_if(in_array($field['type'], ['single_select', 'multi_select'], true) && ! empty($field['options']) && count(array_diff((array) $value, (array) $field['options'])) > 0, 422, "Opção inválida em {$field['label']}.");
        }
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

        $req->update([
            'status' => $validated['status'],
            'observation' => $validated['observation'] ?? $req->observation,
        ]);

        RequestEvent::create([
            'request_id' => $req->id,
            'event_type' => 'status_changed',
            'actor_id' => $user->getKey(),
            'actor_type' => 'staff_admin',
            'sector' => $req->responsible_sector,
            'data' => ['from' => $req->getOriginal('status')?->value ?? $req->getOriginal('status'), 'to' => $validated['status']],
            'created_at' => now(),
        ]);

        // A mensagem usada na decisão também compõe o histórico do chat para
        // que o aluno receba a resposta pronta, inclusive após o encerramento.
        $observation = trim((string) ($validated['observation'] ?? ''));
        if ($observation !== '' && $user instanceof StaffAdmin) {
            Message::create([
                'request_id' => $req->id,
                'sender_id' => $user->getKey(),
                'sender_type' => 'staff_admin',
                'content' => $observation,
            ]);
        }
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
        DB::transaction(function () use ($target, $user, $validated) {
            $target->update(['status' => RequestStatus::COMPLETED, 'result' => $validated['result'], 'observation' => $validated['justification'], 'conclusion_summary' => $validated['conclusion_summary']]);
            RequestEvent::create(['request_id' => $target->id, 'event_type' => 'final_decision', 'actor_id' => $user->getKey(), 'actor_type' => 'staff_admin', 'sector' => $target->responsible_sector, 'data' => $validated, 'created_at' => now()]);
        });

        return response()->json(['message' => 'Decisão registrada.']);
    }

    public function returnToPrevious(Request $request, string $id)
    {
        $user = Auth::guard('staff_admins')->user() ?? $request->user();
        $target = RequestModel::findOrFail($id);
        if (! $user instanceof StaffAdmin || ! in_array($user->role, ['admin', 'cradt', 'coordenacao'], true)) {
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
}
