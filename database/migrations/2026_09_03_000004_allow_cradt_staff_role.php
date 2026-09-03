<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->string('role')->change();
        });
    }

    public function down(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff', 'coordenacao'])->change();
        });
    }
};
