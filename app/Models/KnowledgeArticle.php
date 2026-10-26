<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArticle extends Model
{
    use HasUuids;

    protected $fillable = [
        'title', 'summary', 'content', 'category', 'audience', 'source_reference',
        'status', 'updated_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'publication_active' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleVersion::class)->orderBy('version');
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticleVersion::class, 'published_version_id');
    }
}
