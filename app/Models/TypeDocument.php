<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- ESSA LINHA É A CHAVE!

class TypeDocument extends Model
{
    use HasFactory, HasUuids; // Aqui ativamos o uso

    protected $fillable = [
        'name',
        'description',
        'active'
    ];
}