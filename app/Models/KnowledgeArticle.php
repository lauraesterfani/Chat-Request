<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'summary', 'content', 'category', 'audience', 'source_reference', 'status', 'updated_by', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
