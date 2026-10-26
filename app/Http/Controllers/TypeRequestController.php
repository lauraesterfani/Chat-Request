<?php

namespace App\Http\Controllers;

use App\Models\TypeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TypeRequestController extends Controller
{
    /**
     * Lista todos os tipos de requerimentos.
     */
    public function index()
    {
        $typeRequests = TypeRequest::orderBy('name', 'asc')->get();
        $typeRequests->each(fn (TypeRequest $typeRequest) => $typeRequest->makeHidden(['duplicate_rule_reference']));

        return response()->json($typeRequests);
    }

    public function adminIndex()
    {
        return response()->json(TypeRequest::orderBy('name')->get());
    }

    /**
     * Cria um novo tipo de requerimento.
     */
    public function store(Request $request)
    {
        try {
            // ✨ ATUALIZADO: Adicionado validações para os novos campos
            $validated = $request->validate([
                'name' => 'required|string|unique:type_requests,name|max:63',
                'description' => 'nullable|string',
                'icon' => 'nullable|string',
                'requires_document' => 'nullable|boolean',
                'document_instructions' => 'nullable|string',
                'duplicate_window_days' => 'nullable|integer|min:1|max:365',
                'duplicate_mode' => 'nullable|in:warn,block',
                'duplicate_rule_reference' => ['nullable', 'string', 'max:255', 'required_if:duplicate_mode,block'],
                'requires_decision' => 'nullable|boolean',
            ]);

            if (! $this->canConfigureDuplicateBlock($validated)) {
                return response()->json(['message' => 'Somente a administração institucional pode configurar bloqueio de duplicidade.'], 403);
            }
            $this->validateDuplicateBlockWindow($validated);

            $validated['id'] = (string) Str::uuid();
            $typeRequest = TypeRequest::create($validated);

            return response()->json($typeRequest, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar TypeRequest: '.$e->getMessage());

            return response()->json(['error' => 'Erro ao criar tipo de requerimento.'], 500);
        }
    }

    /**
     * Exibe o tipo de requerimento especificado.
     */
    public function show($id)
    {
        $typeRequest = TypeRequest::find($id);
        if (! $typeRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($typeRequest);
    }

    /**
     * Atualiza o tipo de requerimento especificado.
     */
    public function update(Request $request, $id)
    {
        try {
            $typeRequest = TypeRequest::find($id);
            if (! $typeRequest) {
                return response()->json(['error' => 'Not found'], 404);
            }

            // ✨ ATUALIZADO: Adicionado validações para os novos campos no update também
            $validated = $request->validate([
                'name' => 'required|string|max:63|unique:type_requests,name,'.$id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string',
                'requires_document' => 'nullable|boolean',
                'document_instructions' => 'nullable|string',
                'duplicate_window_days' => 'nullable|integer|min:1|max:365',
                'duplicate_mode' => 'nullable|in:warn,block',
                'duplicate_rule_reference' => ['nullable', 'string', 'max:255', 'required_if:duplicate_mode,block'],
                'requires_decision' => 'nullable|boolean',
            ]);

            if (! $this->canConfigureDuplicateBlock($validated)) {
                return response()->json(['message' => 'Somente a administração institucional pode configurar bloqueio de duplicidade.'], 403);
            }
            $this->validateDuplicateBlockWindow($validated);

            $typeRequest->update($validated);

            return response()->json($typeRequest, 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar: '.$e->getMessage());

            return response()->json(['error' => 'Erro ao atualizar.'], 500);
        }
    }

    /**
     * Remove o tipo de requerimento especificado.
     */
    public function destroy($id)
    {
        try {
            $typeRequest = TypeRequest::find($id);

            if (! $typeRequest) {
                return response()->json(['error' => 'Não encontrado'], 404);
            }

            if ($typeRequest->requests()->exists()) {
                return response()->json([
                    'error' => 'Erro ao excluir porque está em uso',
                ], 422);
            }

            $typeRequest->delete();

            return response()->json(['message' => 'Excluído com sucesso.'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir TypeRequest: '.$e->getMessage());

            return response()->json(['error' => 'Erro ao excluir o registro.'], 500);
        }
    }

    /**
     * Sincroniza documentos a um tipo de requerimento.
     */
    public function syncDocumentTypes(Request $request, string $requestTypeId)
    {
        $validator = Validator::make(
            array_merge($request->all(), ['id' => $requestTypeId]),
            [
                'id' => 'required|exists:type_requests,id',
                'document_type_ids' => 'required|array',
                'document_type_ids.*' => 'required|exists:type_documents,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $requestType = TypeRequest::findOrFail($requestTypeId);
            $requestType->typeDocuments()->sync($request->document_type_ids);

            return response()->json(['message' => 'Sincronizado com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error('Erro sync: '.$e->getMessage());

            return response()->json(['error' => 'Erro ao sincronizar'], 500);
        }
    }

    /** A hard block is an institutional rule; only an unrestricted admin can configure it. */
    private function canConfigureDuplicateBlock(array $validated): bool
    {
        if (($validated['duplicate_mode'] ?? 'warn') !== 'block') {
            return true;
        }

        $admin = Auth::guard('staff_admins')->user();

        return (bool) $admin && $admin->role === 'admin' && ! $admin->accessScopes()->exists();
    }

    private function validateDuplicateBlockWindow(array $validated): void
    {
        if (($validated['duplicate_mode'] ?? 'warn') !== 'block') {
            return;
        }
        if (empty($validated['duplicate_window_days'])) {
            throw ValidationException::withMessages([
                'duplicate_window_days' => 'Defina um período para a regra de bloqueio.',
            ]);
        }
    }
}
