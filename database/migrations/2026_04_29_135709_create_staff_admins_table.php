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
     Schema::create('staff_admins', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('cpf')->unique();
    $table->enum('role', ['admin', 'staff', 'coordenacao']);
    $table->foreignUuid('course_id')->nullable()->constrained('courses')->onDelete('cascade');
    $table->string('password');
    $table->boolean('must_change_password')->default(false); // <- novo campo
    $table->timestamps();
});



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_admins');
    }
};
