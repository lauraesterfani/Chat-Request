<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id');
            $table->uuid('sender_id');
            $table->string('sender_type', 32);
            $table->text('content');
            $table->timestamps();
            $table->foreign('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->index(['request_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
        });
        Schema::create('message_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('reader_id');
            $table->string('reader_type', 32);
            $table->timestamp('read_at');
            $table->timestamps();
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->unique(['message_id', 'reader_type', 'reader_id']);
            $table->index(['reader_type', 'reader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('messages');
    }
};
