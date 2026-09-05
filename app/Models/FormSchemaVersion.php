<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FormSchemaVersion extends Model
{
    use HasUuids;

    protected $fillable = ['type_request_id', 'version', 'status', 'schema', 'change_summary', 'created_by', 'published_at'];

    protected $casts = ['schema' => 'array', 'published_at' => 'datetime'];

    public function type()
    {
        return $this->belongsTo(TypeRequest::class, 'type_request_id');
    }
}
