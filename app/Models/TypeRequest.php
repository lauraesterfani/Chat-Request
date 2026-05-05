<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeRequest extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'name', 'description', 'active'];

    /**
     * Relacionamento com as solicitações (requests).
     * Essencial para a trava de exclusão no Controller.
     */
    public function requests(): HasMany
    {
        // Certifique-se de que o Model Request existe e a FK é type_request_id
        return $this->hasMany(\App\Models\Request::class, 'type_id');
    }

    /**
     * Define quais tipos de documentos são exigidos para este tipo de requerimento.
     */
    public function typeDocuments(): BelongsToMany
    {
        return $this->belongsToMany(TypeDocument::class, 'type_request_documents');
    }
}