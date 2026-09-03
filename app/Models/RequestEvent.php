<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestEvent extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['request_id', 'event_type', 'actor_id', 'actor_type', 'sector', 'data', 'created_at'];

    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];
}
