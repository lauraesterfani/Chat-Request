<?php

namespace App\Http\Controllers;

use App\Models\TypeRequest;

class ServiceCatalogController extends Controller
{
    public function index()
    {
        return response()->json(TypeRequest::orderBy('name')->get()->map(fn (TypeRequest $type) => ['id' => $type->id, 'name' => $type->name, 'description' => $type->description, 'documentation' => $type->requires_document ? ['required' => true, 'instructions' => $type->document_instructions] : ['required' => false, 'instructions' => null], 'channel' => 'digital', 'institutional_status' => 'pending_homologation']));
    }
}
