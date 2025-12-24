<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TypeRequest extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'name', 'description', 'active'];

    /**
     * Define quais tipos de documentos são exigidos para este tipo de requerimento.
     */
    public function typeDocuments()
    {
        // Certifique-se de que o nome da tabela pivô (segundo parâmetro) esteja correto
        return $this->belongsToMany(TypeDocument::class, 'type_request_documents');
    }
}