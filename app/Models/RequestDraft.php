<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RequestDraft extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'type_request_id', 'form_schema_version_id', 'responses', 'document_ids', 'revision', 'discarded_at', 'submitted_at', 'request_id'];

    protected $casts = ['responses' => 'array', 'document_ids' => 'array', 'discarded_at' => 'datetime', 'submitted_at' => 'datetime'];

    public function type()
    {
        return $this->belongsTo(TypeRequest::class, 'type_request_id');
    }

    public function schemaVersion()
    {
        return $this->belongsTo(FormSchemaVersion::class, 'form_schema_version_id');
    }
}
