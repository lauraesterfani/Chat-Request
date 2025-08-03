<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
  protected $keyType = 'string';
  public $incrementing = false;
  protected $fillable = [
    'id',
    'protocol',
    'status',
    'observations',
    'enrollment'
  ];
}
