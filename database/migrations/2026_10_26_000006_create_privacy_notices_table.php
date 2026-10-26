<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('version')->unique();
            $table->string('title', 180);
            $table->text('content');
            $table->string('controller_name', 255)->nullable();
            $table->string('contact_channel', 255)->nullable();
            $table->text('legal_basis_summary')->nullable();
            $table->text('retention_summary')->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->string('institutional_approval_reference', 500)->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->foreignUuid('author_id')->constrained('staff_admins')->restrictOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('staff_admins')->restrictOnDelete();
            $table->timestamp('review_submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_notices');
    }
};
