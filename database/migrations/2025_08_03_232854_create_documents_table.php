<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('documents', function (Blueprint $table) {
      $table->id();
      $table->string('url')->unique();
      $table->unsignedBigInteger('request_id');
      $table->unsignedBigInteger('document_type_id');

      $table->foreign('request_id')
        ->references('id')
        ->on('requests')
        ->onDelete('cascade');
      $table->foreign('document_type_id')
        ->references('id')
        ->on('document_types')
        ->onDelete('cascade');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('documents');
  }
};
