<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- Importante!

class Document extends Model
{
    use HasFactory, HasUuids; // <--- Ativa UUID

    protected $fillable = ['path', 'name', 'mime_type'];

    // Relacionamento inverso (opcional, mas bom ter)
    public function requests()
    {
        return $this->belongsToMany(Request::class, 'request_documents');
    }
}