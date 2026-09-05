<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_type', 40);
            $table->string('recipient_id', 64);
            $table->uuid('request_id')->nullable();
            $table->string('category', 60);
            $table->string('title', 180);
            $table->text('body');
            $table->string('link', 255)->nullable();
            $table->string('channel', 20)->default('internal');
            $table->timestamp('read_at')->nullable();
            $table->string('email_status', 20)->default('not_requested');
            $table->string('idempotency_key', 190)->unique();
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_id', 'created_at']);
            $table->index(['recipient_type', 'recipient_id', 'read_at']);
            $table->foreign('request_id')->references('id')->on('requests')->nullOnDelete();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type', 40);
            $table->string('recipient_id', 64);
            $table->string('category', 60);
            $table->boolean('internal_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->timestamps();
            $table->unique(['recipient_type', 'recipient_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_records');
    }
};
