<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
  protected $fillable = ['enrollment', 'status', 'user_id'];

  protected $primaryKey = 'enrollment';
  public $incrementing = false;
  protected $keyType = 'string';

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
