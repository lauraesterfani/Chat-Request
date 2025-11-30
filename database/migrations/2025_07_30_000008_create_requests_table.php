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
            $table->string('protocol')->unique();
            
            // CORREÇÃO CRÍTICA: Mudar o ENUM para termos em inglês e manter o default em português
            $table->enum('status', [
                'pending',      // Corresponde a "Aberto"
                'analyzing',    // Corresponde a "Em Análise"
                'waiting',      // Corresponde a "Pendente"
                'solving',      // Corresponde a "Em Solução"
                'completed',    // Corresponde a "Concluído"
                'canceled'      // Corresponde a "Cancelado"
            ])->default('pending'); // O valor default agora é o termo em inglês

            $table->text('observations')->nullable();
            $table->uuid('enrollment_id');
            $table->uuid('type_request_id');
            $table->timestamp('due_date')->nullable();

            $table->foreign('enrollment_id', 'fk_requests_enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->onDelete('cascade');

            $table->foreign('type_request_id', 'fk_type_requests_request_id')
                ->references('id')
                ->on('type_requests')
                ->onDelete('cascade');

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