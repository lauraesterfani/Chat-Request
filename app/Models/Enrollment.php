<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
  protected $fillable = [
    'enrollment',
    'status',
    'campus_name',
    'period',
    'turn',
    'modality',
    'course_id',
    'user_id',
  ];

  public function users()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function courses()
  {
    return $this->belongsTo(Course::class, 'course_id');
  }

  public function requests()
  {
    return $this->hasMany(
      Request::class,
      'erollment_id',
      'id'
    );
  }
}
