<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->dropUnique('requests_submission_idempotency_key_unique');
            $table->unique(['user_id', 'submission_idempotency_key'], 'requests_user_submission_key_unique');
        });
    }

    public function down(): void
    {
        $duplicateKeyExists = DB::table('requests')
            ->select('submission_idempotency_key')
            ->whereNotNull('submission_idempotency_key')
            ->groupBy('submission_idempotency_key')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->exists();

        if ($duplicateKeyExists) {
            throw new RuntimeException('Não é possível voltar à chave global: existem chaves de idempotência iguais usadas por alunos diferentes.');
        }

        Schema::table('requests', function (Blueprint $table): void {
            $table->dropUnique('requests_user_submission_key_unique');
            $table->unique('submission_idempotency_key', 'requests_submission_idempotency_key_unique');
        });
    }
};
