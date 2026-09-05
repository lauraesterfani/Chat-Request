<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_access_scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_admin_id');
            $table->uuid('course_id')->nullable();
            $table->string('sector', 80)->nullable();
            $table->json('abilities');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->string('reason', 1000);
            $table->timestamps();
            $table->index(['staff_admin_id', 'expires_at']);
            $table->foreign('staff_admin_id')->references('id')->on('staff_admins')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_access_scopes');
    }
};
