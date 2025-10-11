<?php 

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\User; // NOVO: Importa o modelo User
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
        // Força o uso do guard 'api' (auth:api)
        $this->middleware('auth:api'); 
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtém o ID do usuário autenticado, que é necessário para a Policy e a filtragem.
        $authenticatedUser = Auth::guard('api')->user();

        if (!$authenticatedUser) {
            return response()->json(['msg' => 'Acesso negado. Token inválido ou expirado.'], 401);
        }
        
        // NOVO: Busca o modelo completo do usuário para ter acesso a métodos customizados (isStudent) e relations.
        $user = User::find($authenticatedUser->id);

        if (!$user) {
            return response()->json(['msg' => 'Usuário autenticado não encontrado no banco de dados.'], 404);
        }
        
        // Autorização: Usa o método viewAny() da RequestPolicy.
        $this->authorize('viewAny', RequestModel::class);
        
        try {
            // Inicia a query builder com EAGER LOADING (Carregamento Antecipado)
            // Carrega 'user' (quem fez a requisição) e 'typeRequest' (tipo de requerimento)
            $requestsQuery = RequestModel::query()->with(['user', 'typeRequest']);

            // Se for Student, aplica o filtro: ele só pode ver as requisições dele.
            if ($user->isStudent()) {
                $requestsQuery->where('user_id', $user->id);
            }
            
            // Para Admin e Staff, a query retorna todas as requisições (sem filtro).
            $requests = $requestsQuery->get();

            return response()->json($requests, 200);

        } catch (\Exception $e) {
            Log::error('Error fetching requests list: ' . $e->getMessage());
            return response()->json(['msg' => 'Error fetching requests list'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Autorização: Apenas Students podem criar requisições (Policy::create).
        $this->authorize('create', RequestModel::class);
        
        // Obtém o ID do usuário autenticado, que é necessário para a Policy e a criação.
        $authenticatedUser = Auth::guard('api')->user();

        if (!$authenticatedUser) {
            return response()->json(['msg' => 'Usuário não autenticado via token.'], 401);
        }

        // NOVO: Busca o modelo completo do usuário para ter acesso ao relacionamento enrollments().
        $user = User::find($authenticatedUser->id);

        if (!$user) {
            return response()->json(['msg' => 'Usuário autenticado não encontrado no banco de dados.'], 404);
        }

        try {
            // 2. Validação dos dados que VÊM do Postman (JSON)
            $validated = $request->validate([
                'type_id' => 'required|uuid|exists:type_requests,id',
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
            ]);

            // 3. Pega o ID da Matrícula (Enrollment) do usuário logado.
            // O método enrollments() agora existe porque $user é o modelo Eloquent completo.
            $enrollment = $user->enrollments()->where('status', 'active')->first();

            if (!$enrollment) {
                return response()->json(['msg' => 'Matrícula ativa não encontrada para o usuário.'], 400);
            }
            
            // 4. Cria o número de protocolo 
            $protocol = date('Y-m-d') . '-' . Str::random(8); 

            // 5. Cria o requerimento no banco de dados
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

            // Carrega os dados relacionados antes de retornar a resposta
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
            // Usa EAGER LOADING (Carregamento Antecipado) ao buscar a requisição
            $requestModel = RequestModel::with(['user', 'typeRequest'])->find($id);

            if (!$requestModel) {
                return response()->json(['msg' => 'Request not found'], 404);
            }
            
            // Autorização: Usa o método view(User $user, Request $request) da RequestPolicy.
            // Admin/Staff veem todas. Student só vê a dele.
            $this->authorize('view', $requestModel);

            return response()->json($requestModel, 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Exceção lançada se a Policy negar o acesso (ex: Student tentando ver requisição de outro)
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
        // 1. Encontra o modelo
        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json(['msg' => 'Request not found'], 404);
        }

        // 2. Autorização: Apenas Admin/Staff podem atualizar (Policy::update).
        $this->authorize('update', $requestModel);

        try {
            // 3. Validação: Apenas permitindo a alteração do status por enquanto
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,rejected,cancelled',
                // Se desejar permitir editar subject/description, adicione-os aqui
            ]);

            $requestModel->update($validated);
            
            // Carrega os dados relacionados antes de retornar a resposta
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
            // CORREÇÃO: Usando RequestModel::find($id)
            $requestModel = RequestModel::find($id);

            if (!$requestModel) {
                return response()->json(['msg' => 'Request not found'], 404);
            }
            
            // Autorização: Apenas Admin podem deletar (Policy::delete).
            $this->authorize('delete', $requestModel);

            $requestModel->delete();

            return response()->json(['msg' => 'Request deleted successfully'], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Exceção lançada se a Policy negar o acesso (ex: Staff/Student tentando deletar)
            return response()->json(['msg' => 'Você não tem permissão para deletar esta requisição.'], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting request: ' . $e->getMessage());
            return response()->json(['msg' => 'Error deleting request'], 500);
        }
    }
}
