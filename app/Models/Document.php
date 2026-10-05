<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['path', 'name', 'mime_type', 'user_id', 'file_size'];

    /**
     * Relacionamento com Requerimentos.
     * Certifique-se de que o nome da tabela pivô (request_documents)
     * é o mesmo que você usou no RequestController.
     */
    public function requests()
    {
        return $this->belongsToMany(Request::class, 'requests_documents', 'document_id', 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
