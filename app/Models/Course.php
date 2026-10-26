<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Course extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'code', 'is_active', 'campus_id'];
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Relacionamento (opcional, mas recomendado)
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}
