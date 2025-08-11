<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
  protected $fillable = [
    'name'
  ];

  public function requests()
  {
    return $this->hasMany(
      Request::class,
      'request_type_id',
      'id'
    );
  }

  public function documentTypes()
  {
    return $this->belongsToMany(
      DocumentType::class,
      'document_type_request_type',
      'request_type_id',
      'document_type_id'
    );
  }
}
