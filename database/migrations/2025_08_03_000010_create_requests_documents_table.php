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
        // 🔧 EDITADO: Nome da tabela pivot padronizado no singular
        // Laravel usa padrão alphabetical: document_type / request_type
        // Mas deixar como "document_type_request" também funciona — aqui só padronizei.
        Schema::create('document_type_request', function (Blueprint $table) {

            // 🔧 EDITADO: usar foreignUuid deixa o código mais limpo e correto
            $table->foreignUuid('type_request_id')
                  ->constrained('type_requests')
                  ->cascadeOnDelete();

            $table->foreignUuid('type_document_id')
                  ->constrained('type_documents')
                  ->cascadeOnDelete();

            // 🔧 MANTIDO: chave primária composta
            $table->primary(['type_request_id', 'type_document_id']);

            // 🔧 ADICIONADO: timestamps não são necessários, mas podem ser úteis
            // Mantive porque você colocou — mas poderia ser removido.
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
