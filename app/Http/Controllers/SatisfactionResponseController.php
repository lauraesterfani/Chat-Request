<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use Illuminate\Http\Request;

class SatisfactionResponseController extends Controller
{
    public function show(Request $request, string $id)
    {
        $student = auth('api')->user();
        $this->ownedClosedRequest($id, $student->id);

        return response()->json(SatisfactionResponse::where('request_id', $id)->where('student_id', $student->id)->first());
    }

    public function store(Request $request, string $id)
    {
        $student = auth('api')->user();
        $this->ownedClosedRequest($id, $student->id);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:1000']]);

        if (SatisfactionResponse::where('request_id', $id)->exists()) {
            return response()->json(['message' => 'A avaliação deste atendimento já foi registrada.'], 409);
        }

        return response()->json(SatisfactionResponse::create($data + ['request_id' => $id, 'student_id' => $student->id]), 201);
    }

    private function ownedClosedRequest(string $id, string $studentId): RequestModel
    {
        $request = RequestModel::findOrFail($id);
        abort_unless($request->user_id === $studentId, 403);
        abort_unless(in_array($request->status?->value, ['completed', 'canceled'], true), 422, 'A pesquisa só fica disponível após o encerramento do atendimento.');

        return $request;
    }
}
