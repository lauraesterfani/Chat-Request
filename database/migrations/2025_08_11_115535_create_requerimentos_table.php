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
        Schema::create('requerimentos', function (Blueprint $table) {
            $table->id('id_requerimento');
            $table->string('protocolo', 30)->unique();
            $table->enum('status', ['Aberto', 'Em Análise', 'Deferido', 'Indeferido', 'Pendente'])->default('Aberto');
            $table->text('observacoes')->nullable();

            $table->foreignId('id_matricula')->constrained('matriculas', 'id_matricula');
            $table->foreignId('id_tipo_requerimento')->constrained('tipos_requerimento', 'id_tipo_requerimento');
            $table->timestamps();
            $table->timestamp('data_finalizacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requerimentos');
    }
};
