<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('duplicate_window_days')->nullable()->after('document_instructions');
            $table->boolean('requires_decision')->default(true)->after('duplicate_window_days');
        });
    }

    public function down(): void
    {
        Schema::table('type_requests', function (Blueprint $table) {
            $table->dropColumn(['duplicate_window_days', 'requires_decision']);
        });
    }
};
