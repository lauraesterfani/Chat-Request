<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor_type', 32)->nullable();
            $table->string('actor_id', 64)->nullable();
            $table->string('action', 80);
            $table->string('resource_type', 80);
            $table->string('resource_id', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at');
            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['actor_type', 'actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_records');
    }
};
