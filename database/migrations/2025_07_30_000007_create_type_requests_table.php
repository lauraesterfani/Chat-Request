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
        Schema::create('type_requests', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Chave primária UUID
            $table->string('name', 63)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true); // Exemplo de campo booleano
             $table->string('code', 31)->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_requests');
    }
};
