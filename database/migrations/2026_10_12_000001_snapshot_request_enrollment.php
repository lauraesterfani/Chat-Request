<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_drafts', function (Blueprint $table) {
            $table->uuid('course_id')->nullable();
            $table->uuid('enrollment_id')->nullable();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->uuid('course_id')->nullable()->index();
            $table->uuid('enrollment_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('requests', fn (Blueprint $table) => $table->dropColumn(['course_id', 'enrollment_id']));
        Schema::table('request_drafts', fn (Blueprint $table) => $table->dropColumn(['course_id', 'enrollment_id']));
    }
};
