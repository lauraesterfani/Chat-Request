<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_data_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('protocol', 40)->unique();
            $table->uuid('user_id');
            $table->string('request_type', 24);
            $table->text('description');
            $table->string('status', 24)->default('submitted');
            $table->text('response_summary')->nullable();
            $table->string('rejection_reason', 800)->nullable();
            $table->foreignUuid('responded_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_data_requests');
    }
};
