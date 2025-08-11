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
    Schema::create('enrollments', function (Blueprint $table) {
      $table->id();
      $table->string('enrollment');
      $table->enum('status', ['matriculado', 'graduado', 'desvinculado']);
      $table->string('campus_name');
      $table->string('period');
      $table->string('turn');
      $table->string('modality');
      $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('user_id');

      $table->foreign('course_id')
        ->references('id')
        ->on('courses')
        ->onDelete('cascade');
      $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('enrollments');
  }
};
