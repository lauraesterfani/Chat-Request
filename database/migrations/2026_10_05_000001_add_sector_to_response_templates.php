<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('response_templates', function (Blueprint $table) {
            $table->string('sector', 80)->default('CRADT')->index();
        });
    }

    public function down(): void
    {
        Schema::table('response_templates', fn (Blueprint $table) => $table->dropColumn('sector'));
    }
};
