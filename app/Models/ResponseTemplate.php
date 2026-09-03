<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResponseTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function typeRequests(): BelongsToMany
    {
        return $this->belongsToMany(TypeRequest::class, 'response_template_type_request');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(StaffAdmin::class, 'created_by');
    }
}
