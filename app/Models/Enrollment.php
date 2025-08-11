<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
  protected $fillable = [
    'id',
    'enrollment',
    'status',
    'user_id'
  ];

  protected $keyType = 'string';
  public $incrementing = false;

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function requests()
  {
    return $this->hasMany(Request::class);
  }
}
