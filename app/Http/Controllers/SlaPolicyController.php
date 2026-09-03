<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SlaPolicyController extends Controller
{
    public function index() { return response()->json(SlaPolicy::with('typeRequest')->latest()->paginate(20)); }

    public function store(Request $request)
    {
        $actor = Auth::guard('staff_admins')->user();
        $data = $request->validate([
            'name' => ['required','string','max:120'], 'type_request_id' => ['nullable','uuid','exists:type_requests,id'],
            'first_response_minutes' => ['nullable','integer','min:1'], 'resolution_minutes' => ['nullable','integer','min:1'],
            'near_due_minutes' => ['nullable','integer','min:0'], 'counting_mode' => ['nullable','in:elapsed'], 'timezone' => ['nullable','timezone'],
            'justification' => ['nullable','string','max:2000'], 'status' => ['nullable','in:draft,inactive'],
        ]);
        $data['created_by'] = $actor?->getKey();
        $data['status'] = $data['status'] ?? 'draft';
        return response()->json(SlaPolicy::create($data), 201);
    }

    public function activate(SlaPolicy $slaPolicy)
    {
        if (!$slaPolicy->first_response_minutes || !$slaPolicy->resolution_minutes) return response()->json(['message'=>'Informe as duas metas antes de ativar.'], 422);
        DB::transaction(function () use ($slaPolicy) {
            SlaPolicy::where('status','active')->where('type_request_id',$slaPolicy->type_request_id)->update(['status'=>'inactive']);
            $slaPolicy->update(['status'=>'active','activated_at'=>now()]);
        });
        return response()->json($slaPolicy->fresh());
    }
}
