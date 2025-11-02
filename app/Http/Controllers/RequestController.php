<?php 

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Models\TypeRequest; 
use App\Models\Document;    
use App\Http\Resources\RequestResource; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Support\Facades\Auth; 
use Symfony\Component\HttpFoundation\Response;

class RequestController extends Controller
{
    /**
     * Aplica o middleware de autenticação JWT a todos os métodos deste controller.
     */
    public function __construct()
    {
        // Restaurando o middleware de autenticação
        $this->middleware('auth:api'); 
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authenticatedUser = Auth::user();

        if (!$authenticatedUser) {
             return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }
        
        // Buscamos o Model completo para garantir que o campo 'role' esteja acessível
        $user = User::find($authenticatedUser->id);

        if (!$user) {
            return response()->json(['message' => 'Usuário autenticado não encontrado no banco de dados.'], 404);
        }
        
        $role = strtolower($user->role);
        $validRoles = [User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_STUDENT];

        // Lógica de checagem de papel
        if (!in_array($role, $validRoles)) {
            return response()->json(['message' => 'Acesso negado. Papel de usuário inválido.'], 403);
        }
        
        // Lógica de filtragem baseada no papel
        $requestsQuery = RequestModel::query()
                                     ->with(['user', 'typeRequest']); 
        
        // Se for Student, aplica o filtro: só vê os próprios requerimentos
        if ($role === User::ROLE_STUDENT) {
            $requestsQuery->where('user_id', $user->id);
        } 
        
        $requests = $requestsQuery->paginate(10);
        
        return RequestResource::collection($requests);
    }

    /**
     * Store a newly created resource in storage, including document validation and attachment.
     */
    public function store(Request $request)
    {
        // Autorização: Policy::create (apenas Student pode criar).
        $this->authorize('create', RequestModel::class);
        
        $authenticatedUser = Auth::guard('api')->user();
        
        if (!$authenticatedUser) {
            return response()->json(['msg' => 'Usuário não autenticado via token.'], 401);
        }

        $user = User::find($authenticatedUser->id); 

        if (!$user) {
            return response()->json(['msg' => 'Usuário autenticado não encontrado no banco de dados.'], 404);
        }

        try {
            // 1. Validação: 'document_ids' é OBRIGATÓRIO ser um array (pode ser vazio, mas deve ser enviado)
            $validated = $request->validate([
                'type_id' => 'required|uuid|exists:type_requests,id',
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'document_ids' => 'required|array', 
                'document_ids.*' => 'uuid|exists:documents,id',
            ]);
            
            // Busca por matrícula ativa
            $enrollment = $user->enrollments()->where('status', 'active')->first();

            if (!$enrollment) {
                return response()->json(['msg' => 'Matrícula ativa não encontrada para o usuário.'], 400);
            }
            
            // 2. Busca o Tipo de Requerimento e seus Documentos Exigidos
            $typeRequest = TypeRequest::with('typeDocuments')->findOrFail($validated['type_id']);
            // IDs dos TIPOS de documento (conceito: RG) exigidos
            $requiredDocumentTypeIds = $typeRequest->typeDocuments->pluck('id')->toArray();
            
            // 3. Validação de Documentos Obrigatórios
            $submittedDocumentIds = $validated['document_ids'];
            
            // CORREÇÃO FINAL: Usando 'type_document_id', o nome correto da coluna
            $submittedDocumentTypes = Document::whereIn('id', $submittedDocumentIds)
                                              ->pluck('type_document_id') // <-- CORRIGIDO para o nome correto
                                              ->filter()
                                              ->toArray();

            // Compara: se faltar algum tipo de documento exigido no que foi enviado.
            $missingDocumentTypeIds = array_diff($requiredDocumentTypeIds, $submittedDocumentTypes);

            if (!empty($missingDocumentTypeIds)) {
                
                // Retorna a lista dos tipos de documentos faltantes
                // A relação 'typeDocuments' no TypeRequestModel deve ser ajustada para retornar os TypeDocuments, 
                // e não o pivô. Usaremos o 'typeDocuments' (TypeDocument models)
                $missingTypeNames = $typeRequest->typeDocuments
                                                ->whereIn('id', $missingDocumentTypeIds) // Filtra os modelos TypeDocument com base nos IDs faltantes
                                                ->pluck('name')
                                                ->toArray();
                
                return response()->json([
                    'msg' => 'Documentos obrigatórios pendentes. Por favor, anexe os seguintes tipos de documentos para prosseguir.', 
                    'required_types' => $typeRequest->typeDocuments->pluck('name'),
                    'missing_types' => $missingTypeNames, 
                ], 422);
            }

            // 4. Criação do Requerimento
            $protocol = date('Y-m-d') . '-' . Str::random(8); 

            $requestModel = RequestModel::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id, 
                'enrollment_id' => $enrollment->id, 
                'type_request_id' => $validated['type_id'],
                'protocol' => $protocol,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'status' => 'pending',
            ]);

            // 5. Vinculação dos Documentos Enviados
            if (!empty($submittedDocumentIds)) {
                 $requestModel->documents()->sync($submittedDocumentIds); 
            }

            $requestModel->load(['user', 'typeRequest']);

            return (new RequestResource($requestModel))->response()->setStatusCode(Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error creating requests: ' . $e->getMessage());
            return response()->json(['msg' => 'Validation error', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Error creating requests: ' . $e->getMessage());
            return response()->json(['msg' => 'Error creating requests', 'error_details' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Pré-carrega documentos anexados
            $requestModel = RequestModel::with(['user', 'typeRequest', 'documents'])->find($id);

            if (!$requestModel) {
                return response()->json(['msg' => 'Request not found'], 404);
            }
            
            // Autorização: Policy::view
            $this->authorize('view', $requestModel);

            return new RequestResource($requestModel);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['msg' => 'Você não tem permissão para visualizar esta requisição.'], 403);
        } catch (\Exception $e) {
            Log::error('Error fetching request: ' . $e->getMessage());
            return response()->json(['msg' => 'Error fetching request'], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json(['msg' => 'Request not found'], 404);
        }

        // Autorização: Policy::update
        $this->authorize('update', $requestModel);

        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,rejected,cancelled',
            ]);

            $requestModel->update($validated);
            
            $requestModel->load(['user', 'typeRequest']);

            return new RequestResource($requestModel);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['msg' => 'Validation error', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('Error updating request: ' . $e->getMessage());
            return response()->json(['msg' => 'Error updating request'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $requestModel = RequestModel::find($id);

            if (!$requestModel) {
                return response()->json(['msg' => 'Request not found'], 404);
            }
            
            // Autorização: Policy::delete
            $this->authorize('delete', $requestModel);

            $requestModel->delete();

            return response()->json(['msg' => 'Request deleted successfully'], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['msg' => 'Você não tem permissão para deletar esta requisição.'], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting request: ' . $e->getMessage());
            return response()->json(['msg' => 'Error deleting request'], 500);
        }
    }
}
