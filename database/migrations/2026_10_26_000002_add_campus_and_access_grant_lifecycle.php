<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('must_change_password');
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->uuid('campus_id')->nullable()->after('code')->index();
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->uuid('campus_id')->nullable()->after('course_id')->index();
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
        });

        Schema::table('request_drafts', function (Blueprint $table) {
            $table->uuid('campus_id')->nullable()->after('course_id')->index();
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
        });

        Schema::table('staff_access_scopes', function (Blueprint $table) {
            $table->uuid('campus_id')->nullable()->after('staff_admin_id')->index();
            $table->timestamp('starts_at')->nullable()->after('abilities');
            $table->timestamp('revoked_at')->nullable()->after('expires_at');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('granted_by');
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
            $table->foreign('revoked_by')->references('id')->on('staff_admins')->nullOnDelete();
        });

        DB::table('staff_access_scopes')->whereNull('starts_at')->update(['starts_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('staff_access_scopes', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropForeign(['campus_id']);
            $table->dropIndex(['campus_id']);
            $table->dropColumn(['campus_id', 'starts_at', 'revoked_at', 'revoked_by']);
        });
        Schema::table('request_drafts', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropIndex(['campus_id']);
            $table->dropColumn('campus_id');
        });
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropIndex(['campus_id']);
            $table->dropColumn('campus_id');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropIndex(['campus_id']);
            $table->dropColumn('campus_id');
        });
        Schema::dropIfExists('campuses');
    }
};
