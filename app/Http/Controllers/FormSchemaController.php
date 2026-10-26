<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\DraftVersionMigrator;
use App\Services\EnrollmentSnapshotService;
use App\Services\FormSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FormSchemaController extends Controller
{
    public function versions(Request $request, string $typeId)
    {
        $this->authorizeEditor($request);
        TypeRequest::findOrFail($typeId);

        return response()->json(FormSchemaVersion::where('type_request_id', $typeId)->orderByDesc('version')->get());
    }

    public function published(string $typeId)
    {
        $type = TypeRequest::findOrFail($typeId);
        $schema = FormSchemaVersion::where('type_request_id', $type->id)->where('status', 'published')->latest('version')->first();

        return response()->json(['type_id' => $type->id, 'schema' => $schema?->schema ?? $this->legacySchema(), 'version_id' => $schema?->id, 'version' => $schema?->version]);
    }

    public function store(Request $request, string $typeId)
    {
        $this->authorizeEditor($request);
        $data = $request->validate(['schema' => ['required', 'array'], 'change_summary' => ['nullable', 'string', 'max:1000']]);
        app(FormSchemaValidator::class)->validateDefinition($data['schema']);
        $schema = DB::transaction(function () use ($typeId, $data, $request) {
            TypeRequest::whereKey($typeId)->lockForUpdate()->firstOrFail();
            $version = ((int) FormSchemaVersion::where('type_request_id', $typeId)->max('version')) + 1;

            return FormSchemaVersion::create(['type_request_id' => $typeId, 'version' => $version, 'status' => 'draft', 'schema' => $data['schema'], 'change_summary' => $data['change_summary'] ?? null, 'created_by' => $this->actor($request)->getKey()]);
        });

        return response()->json($schema, 201);
    }

    public function update(Request $request, FormSchemaVersion $schemaVersion)
    {
        $this->authorizeEditor($request);
        $data = $request->validate(['schema' => ['required', 'array'], 'change_summary' => ['nullable', 'string', 'max:1000']]);
        app(FormSchemaValidator::class)->validateDefinition($data['schema']);

        return DB::transaction(function () use ($schemaVersion, $data) {
            $current = FormSchemaVersion::whereKey($schemaVersion->id)->lockForUpdate()->firstOrFail();
            if ($current->status !== 'draft') {
                return response()->json(['message' => 'Uma versão publicada ou substituída não pode ser editada. Crie uma nova versão.'], 409);
            }

            $current->update($data);

            return response()->json($current->fresh());
        });
    }

    public function publish(Request $request, FormSchemaVersion $schemaVersion)
    {
        $this->authorizeEditor($request);

        return DB::transaction(function () use ($schemaVersion) {
            TypeRequest::whereKey($schemaVersion->type_request_id)->lockForUpdate()->firstOrFail();
            $current = FormSchemaVersion::whereKey($schemaVersion->id)->lockForUpdate()->firstOrFail();
            if ($current->status !== 'draft') {
                return response()->json(['message' => 'Somente uma versão em rascunho pode ser publicada.'], 409);
            }
            if ($current->version !== FormSchemaVersion::where('type_request_id', $current->type_request_id)->max('version')) {
                return response()->json(['message' => 'Existe uma versão mais recente. Revise-a antes de publicar.'], 409);
            }
            if (! is_string($current->change_summary) || trim($current->change_summary) === '') {
                return response()->json(['message' => 'Descreva as mudanças antes de publicar a versão.'], 422);
            }

            app(FormSchemaValidator::class)->validateDefinition($current->schema);
            FormSchemaVersion::where('type_request_id', $current->type_request_id)
                ->where('status', 'published')->update(['status' => 'superseded']);
            $current->update(['status' => 'published', 'published_at' => now()]);

            return response()->json($current->fresh());
        });
    }

    public function drafts(Request $request)
    {
        $user = $this->student($request);
        $drafts = RequestDraft::with(['type', 'schemaVersion'])->where('user_id', $user->id)->whereNull('discarded_at')->whereNull('submitted_at')->latest()->get();
        $documentIds = $drafts->flatMap(fn (RequestDraft $draft) => $draft->document_ids ?? [])->unique()->all();
        $documents = Document::where('user_id', $user->id)->whereIn('id', $documentIds)->get(['id', 'name', 'mime_type', 'file_size'])->keyBy('id');
        $drafts->each(function (RequestDraft $draft) use ($documents) {
            $draft->attached_documents = collect($draft->document_ids ?? [])->map(fn (string $id) => $documents->get($id))->filter()->values();
        });

        return response()->json($drafts);
    }

    public function activeEnrollments(Request $request, EnrollmentSnapshotService $snapshots)
    {
        $enrollments = $snapshots->activeForStudent($this->student($request));

        return response()->json([
            'enrollments' => $enrollments,
            'requires_selection' => count($enrollments) > 1,
        ]);
    }

    public function migrationPreview(Request $request, RequestDraft $draft, DraftVersionMigrator $migrator)
    {
        $user = $this->student($request);
        abort_unless($draft->user_id === $user->id, 403);
        if ($draft->discarded_at || $draft->submitted_at) {
            return response()->json(['message' => 'Este rascunho não está disponível.'], 409);
        }
        $target = FormSchemaVersion::where('type_request_id', $draft->type_request_id)->where('status', 'published')->latest('version')->first();
        if (! $target || $draft->form_schema_version_id === $target->id) {
            return response()->json(['needed' => false]);
        }

        return response()->json(['needed' => true, ...$migrator->preview($draft->load('schemaVersion'), $target)]);
    }

    public function migrateDraft(Request $request, RequestDraft $draft, DraftVersionMigrator $migrator)
    {
        $user = $this->student($request);
        abort_unless($draft->user_id === $user->id, 403);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:1']]);

        return DB::transaction(function () use ($draft, $user, $data, $migrator) {
            TypeRequest::whereKey($draft->type_request_id)->lockForUpdate()->firstOrFail();
            $current = RequestDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->user_id === $user->id, 403);
            if ($current->discarded_at || $current->submitted_at) {
                return response()->json(['message' => 'Este rascunho não está disponível.'], 409);
            }
            if ($current->revision !== $data['revision']) {
                return response()->json(['message' => 'O rascunho foi alterado em outra sessão.', 'draft' => $this->withAttachedDocuments($current, $user)], 409);
            }
            $target = FormSchemaVersion::where('type_request_id', $current->type_request_id)->where('status', 'published')->latest('version')->first();
            if (! $target || $current->form_schema_version_id === $target->id) {
                return response()->json(['message' => 'Não há nova versão publicada para este rascunho.'], 409);
            }
            $preview = $migrator->preview($current->load('schemaVersion'), $target);
            $current->update([
                'form_schema_version_id' => $target->id,
                'responses' => $preview['responses'],
                'revision' => $current->revision + 1,
            ]);

            return response()->json(['draft' => $this->withAttachedDocuments($current->fresh(['type', 'schemaVersion']), $user), 'migration' => $preview]);
        });
    }

    public function saveDraft(Request $request, ?RequestDraft $draft = null)
    {
        $user = $this->student($request);
        if ($draft && $draft->user_id !== $user->id) {
            abort(403);
        }
        $data = $request->validate([
            'type_request_id' => ['required', 'uuid', Rule::exists('type_requests', 'id')],
            'form_schema_version_id' => ['nullable', 'uuid', Rule::exists('form_schema_versions', 'id')],
            'responses' => ['nullable', 'array'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['uuid', 'distinct', Rule::exists('documents', 'id')],
            'enrollment_id' => ['nullable', 'uuid'],
            'revision' => [$draft ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);

        if ($draft && $draft->form_schema_version_id !== ($data['form_schema_version_id'] ?? null)) {
            return response()->json(['message' => 'Revise a mudança de formulário antes de migrar o rascunho.'], 409);
        }
        if (! empty($data['form_schema_version_id'])) {
            $version = FormSchemaVersion::whereKey($data['form_schema_version_id'])->where('type_request_id', $data['type_request_id'])->first();
            if (! $version || ($version->status !== 'published' && ! ($draft && $draft->form_schema_version_id === $version->id && $version->status === 'superseded'))) {
                return response()->json(['message' => 'A versão do formulário não está disponível para este serviço.'], 422);
            }
        }

        $documentIds = $data['document_ids'] ?? [];
        if (Document::whereIn('id', $documentIds)->where('user_id', $user->id)->count() !== count($documentIds)) {
            return response()->json(['message' => 'Um ou mais documentos não pertencem ao usuário autenticado.'], 403);
        }
        $missingFile = Document::whereIn('id', $documentIds)->get()->first(fn (Document $document) => ! Storage::disk('local')->exists($document->path) && ! Storage::disk('public')->exists($document->path));
        if ($missingFile) {
            return response()->json(['message' => 'Um anexo do rascunho não está mais disponível. Envie-o novamente.'], 422);
        }

        return DB::transaction(function () use ($draft, $data, $user) {
            if ($draft) {
                $draft = RequestDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
                abort_unless($draft->user_id === $user->id, 403);
                if ($draft->discarded_at || $draft->submitted_at) {
                    return response()->json(['message' => 'Rascunho não pode ser alterado.'], 409);
                }
                if ($draft->type_request_id !== $data['type_request_id']) {
                    return response()->json(['message' => 'Crie outro rascunho para mudar de serviço.'], 409);
                }
                if ($draft->form_schema_version_id !== ($data['form_schema_version_id'] ?? null)) {
                    return response()->json(['message' => 'Revise a mudança de formulário antes de migrar o rascunho.'], 409);
                }
                if ((int) $data['revision'] !== (int) $draft->revision) {
                    return response()->json(['message' => 'O rascunho foi alterado em outra sessão.', 'draft' => $this->withAttachedDocuments($draft, $user)], 409);
                }
                if (! empty($data['enrollment_id']) && $data['enrollment_id'] !== $draft->enrollment_id) {
                    if ($draft->enrollment_id) {
                        return response()->json(['message' => 'A matrícula deste rascunho não pode ser trocada. Crie outro rascunho.'], 409);
                    }
                    $draft->fill(app(EnrollmentSnapshotService::class)->forStudent($user, $data['enrollment_id']));
                }
            } else {
                $draft = new RequestDraft(['user_id' => $user->id, ...app(EnrollmentSnapshotService::class)->forStudent($user, $data['enrollment_id'] ?? null)]);
            }

            $created = ! $draft->exists;
            unset($data['enrollment_id']);
            $draft->fill([...$data, 'revision' => $created ? 1 : $draft->revision + 1]);
            $draft->save();

            $saved = $this->withAttachedDocuments($draft->fresh(['type', 'schemaVersion']), $user);

            return response()->json($saved, $created ? 201 : 200);
        });
    }

    public function discard(Request $request, RequestDraft $draft)
    {
        $user = $this->student($request);
        abort_unless($draft->user_id === $user->id && ! $draft->submitted_at, 403);
        $draft->update(['discarded_at' => now()]);

        return response()->json(['message' => 'Rascunho descartado.']);
    }

    private function legacySchema(): array
    {
        return ['fields' => [['key' => 'subject', 'label' => 'Assunto', 'type' => 'short_text', 'required' => true], ['key' => 'description', 'label' => 'Descrição', 'type' => 'long_text', 'required' => true]]];
    }

    private function withAttachedDocuments(RequestDraft $draft, User $user): RequestDraft
    {
        $draft->attached_documents = Document::where('user_id', $user->id)
            ->whereIn('id', $draft->document_ids ?? [])
            ->get(['id', 'name', 'mime_type', 'file_size']);

        return $draft;
    }

    private function actor(Request $request): mixed
    {
        return Auth::guard('staff_admins')->user() ?? $request->user();
    }

    private function student(Request $request): User
    {
        $user = Auth::guard('api')->user() ?? $request->user();
        abort_unless($user instanceof User && $user->role === User::ROLE_STUDENT, 403);

        return $user;
    }

    private function authorizeEditor(Request $request): void
    {
        $actor = $this->actor($request);
        abort_unless($actor && in_array($actor->role, ['admin', 'cradt'], true), 403);
    }
}
