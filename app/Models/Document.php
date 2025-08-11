<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
      'url',
      'request_id',
      'document_type_id',
    ];

    public function requests() {
      return $this->belongsTo(Request::class, 'request_id');
    }

    public function documentTypes() {
      return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
