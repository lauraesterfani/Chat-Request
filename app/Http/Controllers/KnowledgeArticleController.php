<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleVersion;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KnowledgeArticleController extends Controller
{
    public function index(Request $request)
    {
        return $this->publishedFeed($request, 'public');
    }

    /** Authenticated students get only published material explicitly intended for them. */
    public function authenticatedIndex(Request $request)
    {
        $student = Auth::guard('api')->user();
        abort_unless($student instanceof User && $student->isStudent(), 403);

        return $this->publishedFeed($request, 'authenticated');
    }

    private function publishedFeed(Request $request, string $audience)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $query = KnowledgeArticle::query()->with('publishedVersion')
            ->where(function (Builder $query) use ($audience): void {
                $query->where(function (Builder $versioned) use ($audience): void {
                    $versioned->whereNotNull('published_version_id')
                        ->where('publication_active', true)
                        ->whereHas('publishedVersion', fn (Builder $version) => $version
                            ->where('status', 'published')
                            ->where('audience', $audience));
                })->orWhere(function (Builder $legacy) use ($audience): void {
                    $legacy->whereNull('published_version_id')
                        ->where('status', 'published')
                        ->where('audience', $audience);
                });
            });

        if ($data['q'] ?? null) {
            $term = $data['q'];
            $query->where(function (Builder $search) use ($term): void {
                $search->whereHas('publishedVersion', fn (Builder $version) => $version
                    ->where('title', 'like', '%'.$term.'%')
                    ->orWhere('summary', 'like', '%'.$term.'%'))
                    ->orWhere(function (Builder $legacy) use ($term): void {
                        $legacy->whereNull('published_version_id')
                            ->where(fn (Builder $fields) => $fields->where('title', 'like', '%'.$term.'%')
                                ->orWhere('summary', 'like', '%'.$term.'%'));
                    });
            });
        }

        if ($data['category'] ?? null) {
            $category = $data['category'];
            $query->where(function (Builder $filter) use ($category): void {
                $filter->whereHas('publishedVersion', fn (Builder $version) => $version->where('category', $category))
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('published_version_id')->where('category', $category));
            });
        }

        return response()->json($query->orderByDesc('published_at')->get()->map(function (KnowledgeArticle $article): array {
            $version = $article->publishedVersion;
            $source = $version ?? $article;

            return [
                'id' => $article->id,
                'title' => $source->title,
                'summary' => $source->summary,
                'content' => $source->content,
                'category' => $source->category,
                'source_reference' => $source->source_reference,
                'published_at' => $source->published_at,
            ];
        }));
    }

    /** Administrative listing includes working versions and the separate live publication. */
    public function adminIndex(Request $request)
    {
        $actor = $this->editor();
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'review', 'published', 'inactive'])],
            'audience' => ['nullable', Rule::in(['public', 'authenticated', 'internal'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $articles = KnowledgeArticle::query()
            ->with(['versions', 'publishedVersion'])
            ->when($data['q'] ?? null, fn ($query, $term) => $query->where(fn ($search) => $search
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('summary', 'like', '%'.$term.'%')))
            ->when($data['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['audience'] ?? null, fn ($query, $audience) => $query->where('audience', $audience))
            ->orderByDesc('updated_at')
            ->paginate($data['per_page'] ?? 25)
            ->withQueryString();

        $canPublish = $this->isGlobalPublisher($actor);
        $articles->getCollection()->transform(function (KnowledgeArticle $article) use ($canPublish): KnowledgeArticle {
            $article->setAttribute('can_publish', $canPublish);

            return $article;
        });

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        $actor = $this->editor();
        $data = $this->data($request);
        if ($data['status'] !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Artigos novos precisam começar como rascunho.']);
        }

        $article = DB::transaction(function () use ($actor, $data): KnowledgeArticle {
            $article = KnowledgeArticle::create($this->articleFields($data) + [
                'status' => 'draft',
                'updated_by' => $actor->id,
            ]);
            $this->appendVersion($article, $actor, 'draft', 'created');
            $article = $article->fresh()->load(['versions', 'publishedVersion']);
            $article->setAttribute('can_publish', $this->isGlobalPublisher($actor));

            return $article;
        });

        return response()->json($article, 201);
    }

    public function update(Request $request, KnowledgeArticle $knowledgeArticle)
    {
        $actor = $this->editor();
        $data = $this->data($request);

        $article = DB::transaction(function () use ($actor, $data, $knowledgeArticle): KnowledgeArticle {
            $article = KnowledgeArticle::query()->lockForUpdate()->findOrFail($knowledgeArticle->id);
            $nextStatus = $data['status'];
            $currentStatus = $article->status;

            if ($currentStatus === 'published' && ! $article->published_version_id) {
                $this->preserveLegacyPublication($article);
            }

            if ($nextStatus === 'review' && $currentStatus !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Somente um rascunho pode ser enviado para revisão.']);
            }

            if ($nextStatus === 'published') {
                $this->globalPublisher($actor);
                if ($currentStatus !== 'review') {
                    throw ValidationException::withMessages(['status' => 'Publique somente uma versão que já esteja em revisão.']);
                }
                $this->assertContentUnchanged($article, $data);
                if (trim((string) ($data['source_reference'] ?? '')) === '') {
                    throw ValidationException::withMessages(['source_reference' => 'Informe a referência utilizada na revisão antes de publicar.']);
                }
                if (! ($data['publication_confirmed'] ?? false)) {
                    throw ValidationException::withMessages(['publication_confirmed' => 'Confirme explicitamente que revisou o conteúdo e que a publicação está autorizada.']);
                }
            }

            if ($nextStatus === 'inactive') {
                $this->globalPublisher($actor);
                if ($currentStatus === 'inactive') {
                    throw ValidationException::withMessages(['status' => 'O artigo já está desativado.']);
                }
                $this->assertContentUnchanged($article, $data);
            }

            $event = match ($nextStatus) {
                'review' => 'submitted_for_review',
                'published' => 'published',
                'inactive' => 'deactivated',
                default => 'draft_saved',
            };

            $reviewVersion = $nextStatus === 'published'
                ? $article->versions()->where('status', 'review')->latest('version')->first()
                : null;

            $article->fill($this->articleFields($data));
            $article->status = $nextStatus;
            $article->updated_by = $actor->id;

            if ($nextStatus === 'published') {
                $article->published_at = now()->utc();
                $article->publication_active = true;
            } elseif ($nextStatus === 'inactive') {
                $article->publication_active = false;
            }

            $article->save();
            $version = $this->appendVersion(
                article: $article,
                actor: $actor,
                status: $nextStatus,
                event: $event,
                publicationConfirmed: $nextStatus === 'published',
                reviewedBy: $nextStatus === 'published' ? $actor : null,
                publishedAt: $nextStatus === 'published' ? now()->utc() : null,
                authoredVersion: $reviewVersion,
            );

            if ($nextStatus === 'published') {
                $article->published_version_id = $version->id;
                $article->save();
            }

            $article = $article->fresh()->load(['versions', 'publishedVersion']);
            $article->setAttribute('can_publish', $this->isGlobalPublisher($actor));

            return $article;
        });

        return response()->json($article);
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:100'],
            'audience' => ['required', Rule::in(['public', 'authenticated', 'internal'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'review', 'published', 'inactive'])],
            'publication_confirmed' => ['sometimes', 'boolean'],
            'updated_by' => ['prohibited'],
            'published_at' => ['prohibited'],
            'published_version_id' => ['prohibited'],
            'created_by' => ['prohibited'],
            'reviewed_by' => ['prohibited'],
            'acted_by' => ['prohibited'],
            'versions' => ['prohibited'],
        ]);
    }

    private function articleFields(array $data): array
    {
        return [
            'title' => trim($data['title']),
            'summary' => trim($data['summary']),
            'content' => trim($data['content']),
            'category' => isset($data['category']) ? trim($data['category']) : null,
            'audience' => $data['audience'],
            'source_reference' => isset($data['source_reference']) ? trim($data['source_reference']) : null,
        ];
    }

    private function assertContentUnchanged(KnowledgeArticle $article, array $data): void
    {
        foreach (['title', 'summary', 'content', 'category', 'audience', 'source_reference'] as $field) {
            $submitted = $this->articleFields($data)[$field];
            if ($submitted !== $article->getAttribute($field)) {
                throw ValidationException::withMessages([
                    $field => 'Para alterar o conteúdo, salve uma nova revisão como rascunho e envie-a novamente para revisão.',
                ]);
            }
        }
    }

    private function appendVersion(
        KnowledgeArticle $article,
        StaffAdmin $actor,
        string $status,
        string $event,
        bool $publicationConfirmed = false,
        ?StaffAdmin $reviewedBy = null,
        $publishedAt = null,
        ?KnowledgeArticleVersion $authoredVersion = null,
    ): KnowledgeArticleVersion {
        $version = ((int) $article->versions()->max('version')) + 1;
        $now = now()->utc();

        return KnowledgeArticleVersion::create([
            'knowledge_article_id' => $article->id,
            'version' => $version,
            'event' => $event,
            'status' => $status,
            'title' => $article->title,
            'summary' => $article->summary,
            'content' => $article->content,
            'category' => $article->category,
            'audience' => $article->audience,
            'source_reference' => $article->source_reference,
            'created_by' => $authoredVersion?->created_by ?? $actor->id,
            'created_by_name' => $authoredVersion?->created_by_name ?? $actor->name,
            'reviewed_by' => $reviewedBy?->id,
            'reviewed_by_name' => $reviewedBy?->name,
            'acted_by' => $actor->id,
            'acted_by_name' => $actor->name,
            'publication_confirmed' => $publicationConfirmed,
            'publication_confirmed_at' => $publicationConfirmed ? $now : null,
            'published_at' => $publishedAt,
            'created_at' => $now,
        ]);
    }

    private function preserveLegacyPublication(KnowledgeArticle $article): void
    {
        $publicationDate = $article->published_at ?? $article->updated_at ?? $article->created_at ?? now()->utc();
        $version = KnowledgeArticleVersion::create([
            'knowledge_article_id' => $article->id,
            'version' => 1,
            'event' => 'legacy_publication_preserved',
            'status' => 'published',
            'title' => $article->title,
            'summary' => $article->summary,
            'content' => $article->content,
            'category' => $article->category,
            'audience' => $article->audience,
            'source_reference' => $article->source_reference,
            'created_by' => null,
            'created_by_name' => null,
            'reviewed_by' => null,
            'reviewed_by_name' => null,
            'acted_by' => null,
            'acted_by_name' => null,
            'publication_confirmed' => false,
            'publication_confirmed_at' => null,
            'published_at' => $article->published_at,
            'created_at' => $publicationDate,
        ]);

        $article->published_version_id = $version->id;
        $article->publication_active = true;
        $article->save();
    }

    private function editor(): StaffAdmin
    {
        $actor = Auth::guard('staff_admins')->user();
        abort_unless(
            $actor instanceof StaffAdmin && $actor->isActive() && in_array($actor->role, ['admin', 'cradt'], true),
            403
        );

        return $actor;
    }

    private function globalPublisher(StaffAdmin $actor): StaffAdmin
    {
        abort_unless($this->isGlobalPublisher($actor), 403);

        return $actor;
    }

    private function isGlobalPublisher(StaffAdmin $actor): bool
    {
        return $actor->isActive() && $actor->role === 'admin' && ! $actor->accessScopes()->exists();
    }
}
