<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['message_id', 'reader_id', 'reader_type', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
