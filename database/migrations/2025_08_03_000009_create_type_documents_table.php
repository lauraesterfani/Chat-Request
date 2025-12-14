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
    // O erro estava aqui: estava escrito 'type_requests'
    Schema::create('type_documents', function (Blueprint $table) { 
        $table->uuid('id')->primary();
        $table->string('name'); // Nome do tipo de documento (Ex: RG, CPF, Histórico)
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_documents');
    }
};
