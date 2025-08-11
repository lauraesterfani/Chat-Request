<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id('id_curso');
            $table->string('nome_curso', 150)->unique();
            $table->enum('modalidade', ['Presencial', 'EAD', 'Híbrido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};