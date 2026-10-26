<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_requests', function (Blueprint $table) {
            $table->string('duplicate_mode', 16)->default('warn')->after('duplicate_window_days');
            $table->string('duplicate_rule_reference', 255)->nullable()->after('duplicate_mode');
        });
    }

    public function down(): void
    {
        Schema::table('type_requests', function (Blueprint $table) {
            $table->dropColumn(['duplicate_mode', 'duplicate_rule_reference']);
        });
    }
};
