<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id('id_aluno');
            $table->string('nome_completo', 255);
            $table->string('telefone', 20)->nullable();
            $table->string('email', 255)->unique();
            $table->string('password'); // <<< ADICIONE ESTA LINHA AQUI
            $table->string('cpf', 11)->unique();
            $table->string('identidade', 20);
            $table->string('orgao_expedidor', 20);
            $table->enum('tipo_usuario', ['Estudante', 'CRADT', 'Admin'])->default('Estudante');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};