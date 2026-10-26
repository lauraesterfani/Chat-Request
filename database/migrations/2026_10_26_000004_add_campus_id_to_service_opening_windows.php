<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_opening_windows', function (Blueprint $table): void {
            $table->foreignUuid('campus_id')->nullable()->after('type_request_id')->constrained('campuses')->nullOnDelete();
            $table->index(['type_request_id', 'campus_id', 'course_id', 'status'], 'service_window_scope_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('service_opening_windows', function (Blueprint $table): void {
            $table->dropIndex('service_window_scope_status_idx');
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
