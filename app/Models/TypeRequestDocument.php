<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeRequestDocument extends Model
{
  protected $keyType = 'string';
  public $incrementing = false;
  protected $fillable = [
    'id',
    'type_request_id',
    'type_document_id'
  ];

  protected $table = 'type_requests_documents';
}

