<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrivacyNotice extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'version',
        'title',
        'content',
        'controller_name',
        'contact_channel',
        'legal_basis_summary',
        'retention_summary',
        'source_reference',
        'institutional_approval_reference',
        'effective_date',
        'status',
        'author_id',
        'reviewer_id',
        'review_submitted_at',
        'reviewed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'review_submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(StaffAdmin::class, 'author_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(StaffAdmin::class, 'reviewer_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
