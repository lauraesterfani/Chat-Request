<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Request as ModelsRequest;
use App\Models\RequestType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class RequestController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $requests = ModelsRequest::all();
      return response()->json($requests, 200);
    } catch (Exception $e) {
      Log::error('Error fetching requests: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching requests'], 500);
    }
  }

  /**
   * Cria uma nova solicitação e anexa seus documentos.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(Request $request)
  {
    // --- 1. VALIDAÇÃO INICIAL E OBTENÇÃO DE DADOS ESSENCIAIS ---
    try {
      // Valida os campos do formulário e a estrutura dos documentos
      $validated = $request->validate([
        'protocol' => 'required|string|max:255',
        'observations' => 'nullable|string|max:1000',
        'request_type_id' => 'required|integer|exists:request_types,id',
        'documents' => 'array|min:1', // Requer pelo menos um documento
        'documents.*.file' => 'required|file|mimes:pdf|max:5000',
        'documents.*.document_type_id' => 'required|integer|exists:document_types,id',
      ]);

      // Obtém o ID da matrícula do payload do token JWT
      $enrollmentId = JWTAuth::getPayload()->get('enrollment_id');
      if (!$enrollmentId) {
        throw new Exception('ID da matrícula não encontrado no token.');
      }
    } catch (ValidationException $e) {
      // Retorna erros de validação com status 422
      return response()->json(['errors' => $e->errors()], 422);
    } catch (Exception $e) {
      // Retorna um erro genérico se o token não for válido ou a matrícula não existir nele
      Log::error('Erro na validação do token ou dados: ' . $e->getMessage());
      return response()->json(['msg' => 'Erro na requisição. Verifique os dados e o token.'], 400);
    }

    // --- 2. INÍCIO DA TRANSAÇÃO NO BANCO DE DADOS ---
    DB::beginTransaction();

    try {
      // 3. Cria o registro da requisição principal
      $newRequest = new ModelsRequest($validated);
      $newRequest->status = 'open';
      $newRequest->enrollment_id = $enrollmentId;
      $newRequest->save();

      // 4. Obtém os tipos de documentos permitidos para este tipo de requisição
      $requestType = RequestType::find($validated['request_type_id']);
      $allowedDocumentTypeIds = $requestType->documentTypes->pluck('id')->toArray();

      // 5. Itera sobre cada documento enviado para processamento
      foreach ($request->file('documents') as $document) {
        $documentTypeId = (int) $document['document_type_id'];

        // Validação de segurança: verifica se o tipo de documento é permitido
        if (!in_array($documentTypeId, $allowedDocumentTypeIds)) {
          throw ValidationException::withMessages([
            'documents' => "O tipo de documento de ID {$documentTypeId} não é permitido para esta solicitação."
          ]);
        }

        // Validação de segurança: verifica a assinatura do PDF
        $fileContent = $document['file']->get();
        if (strpos($fileContent, '%PDF-') !== 0) {
          throw ValidationException::withMessages([
            'documents' => "O arquivo para o tipo de documento {$documentTypeId} não é um PDF válido."
          ]);
        }

        // Salva o arquivo no storage e obtém o caminho
        $path = $document['file']->store('documents/' . $newRequest->id, 'public');

        // 6. Cria o registro do documento no banco
        Document::create([
          'url' => Storage::url($path),
          'request_id' => $newRequest->id,
          'document_type_id' => $documentTypeId,
        ]);
      }

      // 7. Se tudo ocorreu sem erros, comita a transação
      DB::commit();

      return response()->json($newRequest, 201);
    } catch (ValidationException $e) {
      // Se houver um erro de validação durante o processamento, desfaz tudo
      DB::rollBack();
      return response()->json(['errors' => $e->errors()], 422);
    } catch (Exception $e) {
      // Se houver qualquer outro tipo de erro, desfaz tudo
      DB::rollBack();
      Log::error('Erro fatal ao criar solicitação: ' . $e->getMessage());
      return response()->json(['msg' => 'Erro ao criar solicitação. Tente novamente mais tarde.'], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request)
  {
    //
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
  public function destroy(Request $request)
  {
    //
  }
}
