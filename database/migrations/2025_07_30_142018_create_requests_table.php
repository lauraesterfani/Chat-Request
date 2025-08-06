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
    Schema::create('requests', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('protocol')->unique();
      $table->string('status');
      $table->text('observations')->nullable();
      $table->uuid('enrollment_id');
      $table->uuid('type_id');

      $table->foreign('enrollment_id', 'fk_requests_enrollment_id')
        ->references('id')
        ->on('enrollments')
        ->onDelete('cascade');

      $table->foreign('type_id', 'fk_type_requests_request_id')
        ->references('id')
        ->on('type_requests')
        ->onDelete('cascade');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('requests');
  }
};
