<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'protocol',
    'status',
    'observations',
    'enrollments_id'
  ];
}
