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
        // Tabela pivô para o relacionamento Many-to-Many
        Schema::create('document_type_request', function (Blueprint $table) {
            // Chave estrangeira para TypeRequest
            $table->uuid('type_request_id');
            $table->foreign('type_request_id')
                  ->references('id')
                  ->on('type_requests')
                  ->onDelete('cascade'); // Exclui a associação se o requerimento for excluído

            // Chave estrangeira para TypeDocument
            $table->uuid('type_document_id');
            $table->foreign('type_document_id')
                  ->references('id')
                  ->on('type_documents')
                  ->onDelete('cascade'); // Exclui a associação se o documento for excluído

            // Define a chave primária composta (evita duplicidade de associações)
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
