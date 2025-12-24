<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request', function (Blueprint $table) {
            $table->id();

            // Chave do Requerimento
            $table->foreignUuid('request_id')
                  ->constrained('requests')
                  ->cascadeOnDelete();

            // Chave do Documento
            $table->foreignUuid('document_id')
                  ->constrained('documents')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request');
    }
};