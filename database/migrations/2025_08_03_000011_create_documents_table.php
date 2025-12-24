<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            // 1. UUID para a chave primária (Compatível com seu Model)
            $table->uuid('id')->primary();

            // 2. Chave Estrangeira para o Usuário (UUID)
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            // 3. DADOS DO ARQUIVO
            $table->string('file_path');
            $table->string('file_name'); 
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // em bytes

            // 4. TIPO DE DOCUMENTO (Já criando com o nome correto)
            // nullable() permite enviar o arquivo antes de classificar, se precisar
            $table->foreignUuid('type_document_id')
                  ->nullable()
                  ->constrained('type_documents')
                  ->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};