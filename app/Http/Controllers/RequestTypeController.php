<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RequestTypeController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    try {
      $validated = $request->validate([
        'name' => 'required|string|unique:type_requests,name|max:63'
      ]);

      $validated['id'] = (string) Str::uuid();

      $typeRequests = RequestType::create($validated);

      return response()->json($typeRequests);
    } catch (\Exception $e) {
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(RequestType $typeRequest)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, RequestType $typeRequest)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(RequestType $typeRequest)
  {
    //
  }

  public function attachDocumentType(Request $request, $requestTypeId)
  {
    $validator = Validator::make(
      array_merge($request->all(), ['request_type_id' => $requestTypeId]),
      [
        'request_type_id' => 'required|uuid|exists:request_types,id',
        'document_type_id' => 'required|uuid|exists:document_types,id',
      ]
    );

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $validated = $validator->validated();

    try {
      $requestType = RequestType::findOrFail($validated['request_type_id']);
      $requestType->documentTypes()->attach($validated['document_type_id']);

      return response()->json(['message' => 'Documento associado com sucesso']);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Erro ao associar documento'], 500);
    }
  }
}
