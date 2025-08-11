<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;
    protected $table = 'campus';
    protected $primaryKey = 'id_campus';
    public $timestamps = false;
    protected $fillable = ['nome_campus', 'cidade'];
}