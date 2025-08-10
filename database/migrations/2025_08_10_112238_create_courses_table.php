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
        Schema::create('courses', function (Blueprint $table) {
            $table->id(); // Chave primária padrão
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps(); // Cria os campos created_at e updated_at corretamente
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};