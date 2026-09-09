<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCatalogEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'type_request_id', 'category', 'audience', 'channel', 'channel_instructions',
        'responsible_sector', 'normative_reference', 'status', 'version', 'updated_by', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function typeRequest(): BelongsTo
    {
        return $this->belongsTo(TypeRequest::class);
    }
}
