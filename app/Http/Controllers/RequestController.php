<?php 

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Http\Resources\RequestResource; // Certifique-se de que este Resource existe
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Support\Facades\Auth; 

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
     * Esta rota foi isolada no arquivo de rotas e agora verifica a autorização internamente.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Se o usuário não estiver autenticado (o middleware auth:api no construtor já deveria pegar isso, 
        // mas é um bom backup, embora não deva ser alcançado)
        if (!$user) {
             return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        // Garante que o usuário tem um papel reconhecido (Student, Admin ou Staff)
        if (!$user->isStudent() && !$user->isAdmin() && !$user->isStaff()) {
            return response()->json(['message' => 'Acesso negado. Papel de usuário inválido.'], 403);
        }
        
        // --- LÓGICA DE FILTRAGEM BASEADA NO PAPEL ---
        
        $requestsQuery = RequestModel::query()->with('user');
        
        // Se for Student, aplica o filtro: só vê os próprios requerimentos
        if ($user->isStudent()) {
            $requestsQuery->where('user_id', $user->id);
        } 
        
        // Se for Admin ou Staff, a query continua sem filtro, vendo todos.

        // Aplica paginação e retorna
        $requests = $requestsQuery->paginate(10);
        
        return RequestResource::collection($requests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Autorização: Policy::create (apenas Student pode criar).
        $this->authorize('create', RequestModel::class);
        
        $authenticatedUser = Auth::guard('api')->user();
        // Lógica de Store (criação) ...
        
        if (!$authenticatedUser) {
            return response()->json(['msg' => 'Usuário não autenticado via token.'], 401);
        }

        $user = User::find($authenticatedUser->id);

        if (!$user) {
            return response()->json(['msg' => 'Usuário autenticado não encontrado no banco de dados.'], 404);
        }

        try {
            $validated = $request->validate([
                'type_id' => 'required|uuid|exists:type_requests,id',
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
            ]);

            $enrollment = $user->enrollments()->where('status', 'active')->first();

            if (!$enrollment) {
                return response()->json(['msg' => 'Matrícula ativa não encontrada para o usuário.'], 400);
            }
            
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

            $requestModel->load(['user', 'typeRequest']);

            return response()->json($requestModel, 201);

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
            $requestModel = RequestModel::with(['user', 'typeRequest'])->find($id);

            if (!$requestModel) {
                return response()->json(['msg' => 'Request not found'], 404);
            }
            
            // Autorização: Policy::view (Student só vê o dele, Admin/Staff veem todos).
            $this->authorize('view', $requestModel);

            return response()->json($requestModel, 200);

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

        // Autorização: Policy::update (apenas Admin/Staff podem atualizar).
        $this->authorize('update', $requestModel);

        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,rejected,cancelled',
            ]);

            $requestModel->update($validated);
            
            $requestModel->load(['user', 'typeRequest']);

            return response()->json($requestModel, 200);

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
            
            // Autorização: Policy::delete (apenas Admin pode deletar).
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
