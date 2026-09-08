<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['actor_type', 'actor_id', 'action', 'resource_type', 'resource_id', 'metadata', 'ip_address', 'created_at'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];
}
