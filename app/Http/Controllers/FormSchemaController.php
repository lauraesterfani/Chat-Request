<?php

namespace App\Http\Controllers;

use App\Models\FormSchemaVersion;
use App\Models\RequestDraft;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        TypeRequest::findOrFail($typeId);
        $data = $request->validate(['schema' => ['required', 'array'], 'change_summary' => ['nullable', 'string', 'max:1000']]);
        $this->validateSchema($data['schema']);
        $version = ((int) FormSchemaVersion::where('type_request_id', $typeId)->max('version')) + 1;
        $schema = FormSchemaVersion::create(['type_request_id' => $typeId, 'version' => $version, 'status' => 'draft', 'schema' => $data['schema'], 'change_summary' => $data['change_summary'] ?? null, 'created_by' => $this->actor($request)->getKey()]);

        return response()->json($schema, 201);
    }

    public function publish(Request $request, FormSchemaVersion $schemaVersion)
    {
        $this->authorizeEditor($request);
        if ($schemaVersion->status === 'published') {
            return response()->json(['message' => 'Esta versão já está publicada.'], 409);
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
        if ($draft && ($draft->discarded_at || $draft->submitted_at)) {
            return response()->json(['message' => 'Rascunho não pode ser alterado.'], 409);
        }
        $data = $request->validate(['type_request_id' => ['required', 'uuid', Rule::exists('type_requests', 'id')], 'form_schema_version_id' => ['nullable', 'uuid', Rule::exists('form_schema_versions', 'id')], 'responses' => ['nullable', 'array'], 'document_ids' => ['nullable', 'array'], 'revision' => ['nullable', 'integer', 'min:1']]);
        if ($draft && array_key_exists('revision', $data) && $data['revision'] !== $draft->revision) {
            return response()->json(['message' => 'O rascunho foi alterado em outra sessão.', 'draft' => $draft], 409);
        }
        $draft ??= new RequestDraft(['user_id' => $user->id]);
        $draft->fill([...$data, 'revision' => $draft->exists ? $draft->revision + 1 : 1]);
        $draft->save();

        return response()->json($draft->fresh(['type', 'schemaVersion']), $draft->wasRecentlyCreated ? 201 : 200);
    }

    public function discard(Request $request, RequestDraft $draft)
    {
        $user = $this->student($request);
        abort_unless($draft->user_id === $user->id && ! $draft->submitted_at, 403);
        $draft->update(['discarded_at' => now()]);

        return response()->json(['message' => 'Rascunho descartado.']);
    }

    private function validateSchema(array $schema): void
    {
        $fields = $schema['fields'] ?? null;
        abort_unless(is_array($fields) && count($fields) <= 50, 422, 'Schema deve conter até 50 campos.');
        $keys = [];
        foreach ($fields as $field) {
            abort_unless(is_array($field) && isset($field['key'], $field['label'], $field['type']), 422, 'Campo inválido.');
            abort_unless(preg_match('/^[a-z][a-z0-9_]{1,59}$/', (string) $field['key']) === 1, 422, 'Chave de campo inválida.');
            abort_unless(! in_array($field['key'], $keys, true), 422, 'Chaves de campo devem ser únicas.');
            abort_unless(in_array($field['type'], ['short_text', 'long_text', 'number', 'date', 'single_select', 'multi_select', 'boolean', 'subjects'], true), 422, 'Tipo de campo inválido.');
            $keys[] = $field['key'];
        }
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
