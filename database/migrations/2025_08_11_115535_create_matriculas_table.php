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
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id('id_matricula');
            $table->string('numero_matricula', 20)->unique();
            $table->string('periodo_ingresso', 6);
            $table->enum('turno', ['Manhã', 'Tarde', 'Noite', 'Integral']);
            $table->enum('status_matricula', ['Matriculado', 'Graduado', 'Desvinculado', 'Trancado'])->default('Matriculado');

            $table->foreignId('id_aluno')->constrained('alunos', 'id_aluno');
            $table->foreignId('id_campus')->constrained('campus', 'id_campus');
            $table->foreignId('id_curso')->constrained('cursos', 'id_curso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
