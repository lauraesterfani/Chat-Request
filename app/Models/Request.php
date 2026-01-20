<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- O IMPORT QUE DEVE ESTAR FALTANDO

class Request extends Model
{
    use HasFactory, HasUuids;

    // Garante que o Laravel deixe salvar esses campos
    protected $fillable = [
        'user_id',
        'type_id',
        'subject',
        'description',
        'status',
        'observation'
    ];

    // Relacionamento com Documentos (caso precise)
    public function documents()
    {
        return $this->belongsToMany(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    // Relacionamento com Tipo
    public function type()
    {
        return $this->belongsTo(TypeRequest::class, 'type_id');
    }
}