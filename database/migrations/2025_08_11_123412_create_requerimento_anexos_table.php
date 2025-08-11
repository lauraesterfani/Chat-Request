<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimento_anexos', function (Blueprint $table) {
            $table->id('id_anexo');
            
            // Chave estrangeira para o requerimento específico
            $table->foreignId('id_requerimento')->constrained('requerimentos', 'id_requerimento')->cascadeOnDelete();

            // Chave estrangeira para o tipo de anexo que este arquivo representa
            $table->foreignId('id_tipo_anexo')->constrained('tipos_anexo', 'id_tipo_anexo');

            // O caminho para o arquivo armazenado no servidor
            $table->string('caminho_arquivo');

            // O nome original do arquivo enviado pelo usuário (boa prática)
            $table->string('nome_original');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimento_anexos');
    }
};