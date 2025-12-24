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
        // Cria a tabela pivô que liga 'Tipos de Requerimento' a 'Tipos de Documento'
        Schema::create('document_type_request', function (Blueprint $table) {
            
            // 1. Chave Estrangeira para TypeRequests (UUID)
            $table->foreignUuid('type_request_id')
                  ->constrained('type_requests') // Aponta para a tabela type_requests
                  ->cascadeOnDelete();           // Se deletar o tipo de requerimento, deleta a relação

            // 2. Chave Estrangeira para TypeDocuments (UUID)
            $table->foreignUuid('type_document_id')
                  ->constrained('type_documents') // Aponta para a tabela type_documents
                  ->cascadeOnDelete();            // Se deletar o tipo de documento, deleta a relação

            // 3. Chave Primária Composta (Evita duplicatas do mesmo par)
            $table->primary(['type_request_id', 'type_document_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_type_request');
    }
};