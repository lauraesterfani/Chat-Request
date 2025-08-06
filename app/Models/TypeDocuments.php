<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDocuments extends Model
{
  protected $keyType = 'string';
  public $incrementing = false;
  protected $fillable = [
    'id',
    'name'
  ];

  public function typeRequests()
  {
    return $this->belongsToMany(
      TypeRequests::class,
      'requests_documents',
      'document_id',          // chave estrangeira da tabela atual (TypeDocuments)
      'request_id'            // chave estrangeira da tabela relacionada (TypeRequests)
    );
  }
}
