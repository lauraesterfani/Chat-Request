<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
  protected $fillable = [
    'name',
    'description'
  ];

  public function enrollments()
  {
    return $this->HasMany(
      Enrollment::class,
      'course_id',
      'id'
    );
  }
}
