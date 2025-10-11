<?php 

namespace App\Http\Controllers;

use App\Models\Request as RequestModel; // CORREÇÃO: Usando RequestModel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class RequestController extends Controller
{
    // ... index, show, destroy ...

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Pega o usuário autenticado (que é o token)
        $user = auth()->user(); 

        if (!$user) {
             // Esta linha garante que, se o token for lido, mas for inválido por algum motivo, ele pare.
            return response()->json(['msg' => 'Usuário não autenticado via token.'], 401);
        }

        try {
            // 2. Validação dos dados que VÊM do Postman (JSON)
            $validated = $request->validate([
                'type_id' => 'required|uuid|exists:type_requests,id', // O UUID que veio do Tinker
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
            ]);

            // 3. Pega o ID da Matrícula (Enrollment) do usuário logado.
            // Assumindo que o usuário tem APENAS UMA matrícula.
            $enrollment = $user->enrollments()->where('status', 'active')->first();

            if (!$enrollment) {
                return response()->json(['msg' => 'Matrícula ativa não encontrada para o usuário.'], 400);
            }
            
            // 4. Cria o número de protocolo (ex: AAAA-MM-DD-UUID_curto)
            $protocol = date('Y-m-d') . '-' . Str::random(8); 

            // 5. Cria o requerimento no banco de dados
            $requestModel = RequestModel::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id, // ID do usuário autenticado
                'enrollment_id' => $enrollment->id, // ID da matrícula ativa
                'type_request_id' => $validated['type_id'],
                'protocol' => $protocol, // Gerado automaticamente
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'status' => 'pending', // Status inicial fixo
            ]);

            return response()->json($requestModel, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Retorna erros de validação (ex: type_id não é UUID)
            Log::error('Validation Error creating requests: ' . $e->getMessage());
            return response()->json(['msg' => 'Validation error', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            // Retorna o erro real do banco de dados (DEBUG)
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
      $request = ModelsRequest::find($id);

      if (!$request) {
        return response()->json(['msg' => 'Request not found'], 404);
      }

      return response()->json($request, 200);
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
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      $request = ModelsRequest::find($id);

      if (!$request) {
        return response()->json(['msg' => 'Request not found'], 404);
      }

      $request->delete();

      return response()->json(['msg' => 'Request deleted successfully'], 200);
    } catch (\Exception $e) {
      Log::error('Error deleting request: ' . $e->getMessage());
      return response()->json(['msg' => 'Error deleting request'], 500);
    }
  }
}
