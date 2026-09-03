<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->foreignUuid('type_request_id')->nullable()->constrained('type_requests')->nullOnDelete();
            $table->unsignedInteger('first_response_minutes')->nullable();
            $table->unsignedInteger('resolution_minutes')->nullable();
            $table->unsignedInteger('near_due_minutes')->default(120);
            $table->string('counting_mode')->default('elapsed');
            $table->string('timezone')->default('America/Recife');
            $table->text('justification')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff_admins')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'type_request_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('assigned_staff_id')->nullable()->after('responsible_sector')->constrained('staff_admins')->nullOnDelete();
            $table->string('priority')->default('normal')->after('assigned_staff_id');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->unsignedInteger('sla_policy_version')->nullable();
            $table->timestamp('sla_first_response_due_at')->nullable();
            $table->timestamp('sla_resolution_due_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_staff_id']);
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn(['assigned_staff_id', 'priority', 'first_response_at', 'resolved_at', 'sla_policy_id', 'sla_policy_version', 'sla_first_response_due_at', 'sla_resolution_due_at']);
        });
        Schema::dropIfExists('sla_policies');
    }
};
