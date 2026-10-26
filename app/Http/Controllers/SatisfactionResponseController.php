<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\SatisfactionResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return $this->alreadyRated();
        }

        try {
            $response = DB::transaction(fn () => SatisfactionResponse::create($data + ['request_id' => $id, 'student_id' => $student->id]));
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            // The unique request_id constraint is the concurrency-safe guard.
            return $this->alreadyRated();
        }

        return response()->json($response, 201);
    }

    private function ownedClosedRequest(string $id, string $studentId): RequestModel
    {
        $request = RequestModel::findOrFail($id);
        abort_unless($request->user_id === $studentId, 403);
        abort_unless(in_array($request->status?->value, ['completed', 'canceled'], true), 422, 'A pesquisa só fica disponível após o encerramento do atendimento.');

        return $request;
    }

    private function alreadyRated()
    {
        return response()->json(['message' => 'A avaliação deste atendimento já foi registrada.'], 409);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23505'
            || ($sqlState === '23000' && in_array($driverCode, [19, 1062, 2601, 2627], true));
    }
}
