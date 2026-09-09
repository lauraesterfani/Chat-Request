<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_catalog_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('type_request_id')->unique()->constrained('type_requests')->cascadeOnDelete();
            $table->string('category', 100)->nullable();
            $table->string('audience', 120)->nullable();
            $table->enum('channel', ['digital', 'external'])->default('digital');
            $table->text('channel_instructions')->nullable();
            $table->string('responsible_sector', 80)->nullable();
            $table->string('normative_reference', 255)->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'inactive'])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignUuid('updated_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalog_entries');
    }
};
