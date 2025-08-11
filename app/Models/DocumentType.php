<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
  protected $fillable = [
    'name',
  ];

  public function documents()
  {
    return $this->hasMany(
      Document::class,
      'document_type_id',
      'id'
    );
  }

  public function requestTypes()
  {
    return $this->belongsToMany(
      RequestType::class,
      'document_type_request_type',
      'document_type_id',
      'request_type_id'
    );
  }
}
