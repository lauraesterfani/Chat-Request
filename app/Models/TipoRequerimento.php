<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoRequerimento extends Model
{
    use HasFactory;
    protected $table = 'tipos_requerimento';
    protected $primaryKey = 'id_tipo_requerimento';
    public $timestamps = false;
    protected $fillable = ['nome_requerimento'];

    // Relacionamento N:M com TipoAnexo
    public function anexosExigidos(): BelongsToMany
    {
        return $this->belongsToMany(TipoAnexo::class, 'requerimento_anexos_exigidos', 'id_tipo_requerimento', 'id_tipo_anexo');
    }
}