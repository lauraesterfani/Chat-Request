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
      $table->id();
      $table->string('protocol')->unique();
      $table->string('status');
      $table->text('observations')->nullable();
      $table->string('enrollment');
      // $table->unsignedBigInteger('type');

      $table->foreign('enrollment', 'fk_requests_enrollments')
        ->references('enrollment')
        ->on('enrollments')
        ->onDelete('cascade');
      // $table->foreign('type', 'fk_requests_type_id')
      //   ->references('id')
      //   ->on('requests_type')
      //   ->onDelete('cascade');

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
