<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('responsible_sector', 80)->default('CRADT');
            $table->string('result', 30)->nullable();
            $table->text('conclusion_summary')->nullable();
        });

        Schema::create('request_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id');
            $table->string('event_type', 40);
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type', 32)->nullable();
            $table->string('sector', 80)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at');
            $table->foreign('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->index(['request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_events');
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['responsible_sector', 'result', 'conclusion_summary']);
        });
    }
};
