<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 

class TypeRequest extends Model
{
    use HasFactory, HasUuids; 

    protected $table = 'type_requests';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'description',
    ];
    
    public function requests()
    {
        return $this->hasMany(\App\Models\Request::class, 'type_id');
    }
}