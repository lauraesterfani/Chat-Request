<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage; // Importante para gerar a URL

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['path', 'name', 'mime_type'];

    // Adiciona o campo 'url' automaticamente quando o Model for convertido para JSON
    protected $appends = ['url'];

    /**
     * Acessor para a URL completa do documento.
     * Isso permite que o frontend use 'documento.url' diretamente.
     */
    public function getUrlAttribute()
    {
        // Se o path já for uma URL externa, retorna ele. 
        // Caso contrário, gera a URL correta do Storage.
        return $this->path ? asset(Storage::url($this->path)) : null;
    }

    /**
     * Relacionamento com Requerimentos.
     * Certifique-se de que o nome da tabela pivô (request_documents) 
     * é o mesmo que você usou no RequestController.
     */
    public function requests()
    {
        return $this->belongsToMany(Request::class, 'request_documents');
    }
}