<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schema_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('type_request_id');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('draft');
            $table->json('schema');
            $table->string('change_summary', 1000)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['type_request_id', 'version']);
            $table->index(['type_request_id', 'status']);
            $table->foreign('type_request_id')->references('id')->on('type_requests')->cascadeOnDelete();
        });

        Schema::create('request_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('type_request_id');
            $table->uuid('form_schema_version_id')->nullable();
            $table->json('responses')->nullable();
            $table->json('document_ids')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('discarded_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->uuid('request_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'discarded_at', 'submitted_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('type_request_id')->references('id')->on('type_requests')->cascadeOnDelete();
            $table->foreign('form_schema_version_id')->references('id')->on('form_schema_versions')->nullOnDelete();
            $table->foreign('request_id')->references('id')->on('requests')->nullOnDelete();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->uuid('form_schema_version_id')->nullable()->after('type_id');
            $table->json('form_responses')->nullable()->after('description');
            $table->string('submission_idempotency_key', 120)->nullable()->unique()->after('protocol');
            $table->string('submission_payload_hash', 64)->nullable()->after('submission_idempotency_key');
            $table->foreign('form_schema_version_id')->references('id')->on('form_schema_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['form_schema_version_id']);
            $table->dropUnique(['submission_idempotency_key']);
            $table->dropColumn(['form_schema_version_id', 'form_responses', 'submission_idempotency_key', 'submission_payload_hash']);
        });
        Schema::dropIfExists('request_drafts');
        Schema::dropIfExists('form_schema_versions');
    }
};
