<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('document_type_request_type', function (Blueprint $table) {
      $table->foreignId('document_type_id')->constrained()->onDelete('cascade');
      $table->foreignId('request_type_id')->constrained()->onDelete('cascade');
      $table->primary(['document_type_id', 'request_type_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('document_type_request_type');
  }
};
