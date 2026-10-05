<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\FormSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FormSchemaController extends Controller
{
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

    public function publish(Request $request, FormSchemaVersion $schemaVersion)
    {
        $this->authorizeEditor($request);
        if ($schemaVersion->status !== 'draft') {
            return response()->json(['message' => 'Somente uma versão em rascunho pode ser publicada.'], 409);
        }
        $schemaVersion->update(['status' => 'published', 'published_at' => now()]);

        return response()->json($schemaVersion);
    }

    public function drafts(Request $request)
    {
        $user = $this->student($request);

        return response()->json(RequestDraft::with(['type', 'schemaVersion'])->where('user_id', $user->id)->whereNull('discarded_at')->whereNull('submitted_at')->latest()->get());
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
            'revision' => [$draft ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);

        if (! empty($data['form_schema_version_id']) && ! FormSchemaVersion::whereKey($data['form_schema_version_id'])->where('type_request_id', $data['type_request_id'])->where('status', 'published')->exists()) {
            return response()->json(['message' => 'A versão do formulário não está publicada para este serviço.'], 422);
        }

        $documentIds = $data['document_ids'] ?? [];
        if (Document::whereIn('id', $documentIds)->where('user_id', $user->id)->count() !== count($documentIds)) {
            return response()->json(['message' => 'Um ou mais documentos não pertencem ao usuário autenticado.'], 403);
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
                if ((int) $data['revision'] !== (int) $draft->revision) {
                    return response()->json(['message' => 'O rascunho foi alterado em outra sessão.', 'draft' => $draft], 409);
                }
            } else {
                $draft = new RequestDraft(['user_id' => $user->id]);
            }

            $created = ! $draft->exists;
            $draft->fill([...$data, 'revision' => $created ? 1 : $draft->revision + 1]);
            $draft->save();

            return response()->json($draft->fresh(['type', 'schemaVersion']), $created ? 201 : 200);
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
