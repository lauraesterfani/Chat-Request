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
      $table->string('enrollment')->unique();
      $table->enum('status', ['active', 'locked', 'finished']);
      $table->unsignedBigInteger('user_id');

      $table->foreign('user_id', 'fk_enrollments_user_id')
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
