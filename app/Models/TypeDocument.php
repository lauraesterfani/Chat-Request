<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para Tipos de Documentos.
 * Armazena as definições de documentos necessários (ex: 'RG', 'Comprovante de Residência').
 */
class TypeDocument extends Model // Classe ajustada para o singular (TypeDocument)
{
    use HasFactory;

    // A tabela no banco de dados é 'type_documents' (plural), que é o padrão.
    protected $table = 'type_documents';

    // Desativa timestamps (opcional, remova a linha se usar created_at/updated_at)
    // public $timestamps = false; 

    // --- Configuração para UUID (String) como Chave Primária ---
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    // Campos que podem ser preenchidos via Mass Assignment.
    // O campo 'description' é necessário pois é validado e criado no Controller.
    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    /**
     * Relacionamento N:N com TypeRequest (Tipos de Requerimentos).
     * Mapeia os documentos necessários para cada tipo de requerimento.
     */
    public function typeRequests()
    {
        return $this->belongsToMany(
            TypeRequest::class,
            'requests_documents', // Tabela pivot
            'document_id',        // Chave estrangeira do Model atual (TypeDocument) na tabela pivot
            'request_id'          // Chave estrangeira do Model relacionado (TypeRequest) na tabela pivot
        );
    }
}
