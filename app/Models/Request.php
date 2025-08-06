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
    'enrollment_id',
    'type_id'
  ];

  public function enrollment() {
    return $this->belongsTo(Enrollment::class, 'enrollment_id');
  }

  public function typeRequest() {
    return $this->belongsTo(TypeRequests::class, 'type_id');
  }
}
