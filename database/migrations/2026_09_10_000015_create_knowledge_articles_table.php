<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 180);
            $table->string('summary', 500);
            $table->text('content');
            $table->string('category', 100)->nullable();
            $table->string('audience', 80)->default('public');
            $table->string('source_reference', 255)->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'inactive'])->default('draft');
            $table->foreignUuid('updated_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
