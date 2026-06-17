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
            $table->uuid('id')->primary();
            $table->string('name')->unique();

            // Mensagem inicial/Pergunta que o robô fará no chat
            $table->text('description')->nullable(); 
            $table->string('icon')->nullable();      

            // ✨ NOVOS CAMPOS PARA A NOVA LÓGICA:
            // Define se precisa de documento (true/1) ou não (false/0). O padrão será não precisar (false).
            $table->boolean('requires_document')->default(false); 
            
            // Texto contendo as instruções de quais documentos anexar (aparece se o de cima for true)
            $table->text('document_instructions')->nullable();

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