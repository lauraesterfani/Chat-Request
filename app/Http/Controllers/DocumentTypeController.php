<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
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
        'name' => 'required|string|unique:type_documents,name|max:63'
      ]);

      $validated['id'] = (string) Str::uuid();

      $typeDocuments = DocumentType::create($validated);

      return response()->json($typeDocuments);
    } catch (\Exception $e) {
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(DocumentType $typeDocuments)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, DocumentType $typeDocuments)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(DocumentType $typeDocuments)
  {
    //
  }
}
