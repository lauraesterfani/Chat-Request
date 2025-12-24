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
        $table->string('name');      // O Controller manda 'name' (não file_name)
        $table->string('mime_type'); // O Controller manda 'mime_type'

        // --- COLUNAS EXTRAS (Deixamos como nullable para não travar) ---
        // Como o controller ainda não manda o user_id, deixamos nullable por segurança
        $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
        
        $table->unsignedBigInteger('file_size')->nullable(); 
        
        // Se a tabela 'type_documents' ainda não existir, isso daria erro.
        // Deixamos nullable para garantir.
        $table->foreignUuid('type_document_id')->nullable(); 
        
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};