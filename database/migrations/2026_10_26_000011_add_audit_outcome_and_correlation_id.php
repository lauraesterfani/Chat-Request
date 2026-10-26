<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_records', function (Blueprint $table): void {
            $table->uuid('correlation_id')->nullable()->after('resource_id');
            $table->string('outcome', 16)->default('unknown')->after('correlation_id');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_records', function (Blueprint $table): void {
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['correlation_id', 'outcome']);
        });
    }
};
