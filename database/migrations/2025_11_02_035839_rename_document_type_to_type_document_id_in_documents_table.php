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
        Schema::table('documents', function (Blueprint $table) {
            // Renomeia a coluna 'type' (que você tem) para 'type_document_id' (o padrão correto)
            $table->renameColumn('type', 'type_document_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Reverte o nome da coluna de 'type_document_id' para 'type'
            $table->renameColumn('type_document_id', 'type');
        });
    }
};
