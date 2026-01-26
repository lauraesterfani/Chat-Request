<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Request extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type_id',
        'subject',
        'description',
        'status',
        'observation',
        'protocol' // <--- OBRIGATÓRIO: Sem isso, o salvamento falha!
    ];

    public function documents()
    {
        // Garante o uso da tabela pivô correta
        return $this->belongsToMany(Document::class, 'requests_documents', 'request_id', 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function type()
    {
        return $this->belongsTo(TypeRequest::class, 'type_id');
    }
}