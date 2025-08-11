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
    'enrollment_id',
    'request_type_id',
  ];

  public function enrollments()
  {
    return $this->belongsTo(Enrollment::class, 'enrollment_id');
  }

  public function requestTypes()
  {
    return $this->belongsTo(RequestType::class, 'request_type_id');
  }

  public function documents()
  {
    return $this->hasMany(
      Document::class,
      'request_id',
      'id'
    );
  }
}
