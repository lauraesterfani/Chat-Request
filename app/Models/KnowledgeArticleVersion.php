<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class KnowledgeArticleVersion extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'knowledge_article_id', 'version', 'event', 'status', 'title', 'summary', 'content',
        'category', 'audience', 'source_reference', 'created_by', 'created_by_name',
        'reviewed_by', 'reviewed_by_name', 'acted_by', 'acted_by_name',
        'publication_confirmed', 'publication_confirmed_at', 'published_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'publication_confirmed' => 'boolean',
            'publication_confirmed_at' => 'datetime',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Versões de artigos são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Versões de artigos não podem ser removidas pela aplicação.'));
    }
}
