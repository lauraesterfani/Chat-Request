<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_opening_windows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('type_request_id')->constrained('type_requests')->cascadeOnDelete();
            $table->foreignUuid('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64)->default('America/Recife');
            $table->enum('status', ['draft', 'published', 'inactive'])->default('draft');
            $table->string('source_reference', 255)->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['type_request_id', 'course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_opening_windows');
    }
};
