<?php

namespace App\Http\Controllers;

use App\Models\TypeDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeDocumentsController extends Controller
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

      $typeDocuments = TypeDocuments::create($validated);

      return response()->json($typeDocuments);
    } catch (\Exception $e) {
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(TypeDocuments $typeDocuments)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, TypeDocuments $typeDocuments)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(TypeDocuments $typeDocuments)
  {
    //
  }
}
