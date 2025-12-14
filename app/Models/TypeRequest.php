<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeRequest extends Model
{
    use HasFactory;

    protected $table = 'type_requests'; // Nome da tabela no banco
    protected $keyType = 'string';      // O ID é UUID (string)
    public $incrementing = false;       // Não é auto-incremento numérico

    protected $fillable = ['id', 'name', 'description', 'icon'];

    // Relação com Documentos (caso precise no futuro)
    public function documentTypes()
    {
        return $this->belongsToMany(TypeDocument::class, 'type_request_documents');
    }
}