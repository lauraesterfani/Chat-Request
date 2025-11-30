<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Corrigido para usar 'file_name' e adicionado nullable() ao campo 'type'.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // CHAVE ESTRANGEIRA (UUID)
            $table->foreignUuid('user_id')
                  ->constrained('users') // Referencia a tabela 'users'
                  ->cascadeOnDelete();
            
            // DADOS DO ARQUIVO
            $table->string('file_path');
            $table->string('file_name'); // CORRIGIDO: Agora corresponde ao campo usado no Controller
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // em bytes
            
            // METADADOS
            $table->string('type', 50)->nullable(); // Adicionado nullable para flexibilidade
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
