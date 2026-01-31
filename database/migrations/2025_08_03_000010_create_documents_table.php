<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // --- COLUNAS QUE O CONTROLLER JÁ ENVIA ---
            $table->string('path');      // O Controller manda 'path'
            $table->string('name');      // O Controller manda 'name'
            $table->string('mime_type'); // O Controller manda 'mime_type'

            // --- COLUNAS EXTRAS ---
            $table->foreignUuid('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('file_size')->nullable();

            // Agora configurado como chave estrangeira para 'type_documents'
            $table->foreignUuid('type_document_id')
                  ->nullable()
                  ->constrained('type_documents')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
