<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_template_type_request', function (Blueprint $table) {
            $table->foreignUuid('response_template_id')
                ->constrained('response_templates')
                ->cascadeOnDelete();
            $table->foreignUuid('type_request_id')
                ->constrained('type_requests')
                ->cascadeOnDelete();

            $table->primary(['response_template_id', 'type_request_id']);
            $table->index('type_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_template_type_request');
    }
};
