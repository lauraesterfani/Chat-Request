<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAnexo extends Model
{
    use HasFactory;
    protected $table = 'tipos_anexo';
    protected $primaryKey = 'id_tipo_anexo';
    public $timestamps = false;
    protected $fillable = ['nome_anexo'];
}