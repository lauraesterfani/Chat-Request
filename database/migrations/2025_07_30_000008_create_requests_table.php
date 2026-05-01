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
        Schema::create('requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Vamos deixar nulo por enquanto para evitar erro se não gerar na hora
            $table->string('protocol')->unique()->nullable(); 

            // --- CAMPOS QUE FALTAVAM (Essenciais para o Frontend) ---
            $table->string('subject');
            $table->text('description');

            // --- CHAVES ESTRANGEIRAS (Usando sintaxe moderna para UUID) ---
            // Substituí enrollment_id por user_id para facilitar o vínculo com o Auth
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            // O frontend manda 'type_id', então a coluna deve ter esse nome
            $table->foreignUuid('type_id')->constrained('type_requests')->onDelete('cascade');

            $table->enum('status', [
                'pending',      // Aberto
                'analyzing',    // Em Análise
                'waiting',      // Pendente
                'solving',      // Em Solução
                'completed',    // Concluído
                'canceled'      // Cancelado
            ])->default('pending');

            $table->text('observation')->nullable(); // Singular (padrão Laravel)
            $table->timestamp('due_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};