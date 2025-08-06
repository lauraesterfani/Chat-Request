<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeRequests extends Model
{
  protected $keyType = 'string';
  public $incrementing = false;
  protected $fillable = [
    'id',
    'name'
  ];

  protected $table = 'type_requests';

  public function request()
  {
    return $this->hasMany(Request::class, 'type_id');
  }

  public function typeDocuments()
  {
    return $this->belongsToMany(
      TypeDocuments::class,
      'requests_documents',
      'type_request_id',
      'type_document_id'
    );
  }
}
