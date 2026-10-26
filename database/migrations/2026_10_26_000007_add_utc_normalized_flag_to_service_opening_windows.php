<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_opening_windows', function (Blueprint $table): void {
            // Existing rows have no trustworthy timezone provenance.
            $table->boolean('timestamps_utc_normalized')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('service_opening_windows', function (Blueprint $table): void {
            $table->dropColumn('timestamps_utc_normalized');
        });
    }
};
