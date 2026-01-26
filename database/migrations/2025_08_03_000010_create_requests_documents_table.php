<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests_documents', function (Blueprint $table) {
            $table->id(); 
            
            // Aceita os IDs longos (UUID) dos requerimentos e documentos
            $table->foreignUuid('request_id')->constrained('requests')->onDelete('cascade');
            $table->foreignUuid('document_id')->constrained('documents')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests_documents');
    }
};