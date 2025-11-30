<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esta migration está sendo mantida VAZIA porque todos os campos
     * de usuário foram consolidados na migration principal:
     * 0001_01_01_000000_create_users_table.php.
     */
    public function up(): void
    {
        // Esta função está vazia para evitar o erro "Duplicate column name".
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta função está vazia, pois o arquivo não fez nenhuma alteração no UP.
    }
};
