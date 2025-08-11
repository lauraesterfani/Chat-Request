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
        Schema::create('requerimento_anexos_exigidos', function (Blueprint $table) {
            $table->foreignId('id_tipo_requerimento')->constrained('tipos_requerimento', 'id_tipo_requerimento');
            $table->foreignId('id_tipo_anexo')->constrained('tipos_anexo', 'id_tipo_anexo');

            // Chave primária composta para evitar duplicatas
            $table->primary(['id_tipo_requerimento', 'id_tipo_anexo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requerimento_anexos_exigidos');
    }
};
