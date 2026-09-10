<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeArticleController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'category' => ['nullable', 'string', 'max:100']]);
        $query = KnowledgeArticle::where('status', 'published')->where('audience', 'public');
        if ($data['q'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$data['q'].'%')->orWhere('summary', 'like', '%'.$data['q'].'%'));
        }
        if ($data['category'] ?? null) {
            $query->where('category', $data['category']);
        }

        return response()->json($query->orderByDesc('published_at')->get(['id', 'title', 'summary', 'content', 'category', 'source_reference', 'published_at']));
    }

    public function store(Request $request)
    {
        $data = $this->data($request) + ['updated_by' => auth('staff_admins')->id()];
        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        return response()->json(KnowledgeArticle::create($data), 201);
    }

    public function update(Request $request, KnowledgeArticle $knowledgeArticle)
    {
        $data = $this->data($request) + ['updated_by' => auth('staff_admins')->id()];
        if ($data['status'] === 'published' && ! $knowledgeArticle->published_at) {
            $data['published_at'] = now();
        }
        if ($data['status'] !== 'published') {
            $data['published_at'] = null;
        }
        $knowledgeArticle->update($data);

        return response()->json($knowledgeArticle->fresh());
    }

    private function data(Request $request): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:180'], 'summary' => ['required', 'string', 'max:500'], 'content' => ['required', 'string', 'max:20000'], 'category' => ['nullable', 'string', 'max:100'], 'audience' => ['required', Rule::in(['public', 'authenticated', 'internal'])], 'source_reference' => ['nullable', 'string', 'max:255'], 'status' => ['required', Rule::in(['draft', 'review', 'published', 'inactive'])]]);
    }
}
