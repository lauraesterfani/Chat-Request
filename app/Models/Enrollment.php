<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'enrollment',
    'status',
    'user_id'
  ];

  public function user() {
    return $this->belongsTo(User::class);
  }
}
