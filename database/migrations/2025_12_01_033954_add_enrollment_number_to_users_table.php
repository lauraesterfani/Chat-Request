<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::table('users', function (Blueprint $table) {
                // Adiciona a nova coluna 'enrollment_number'
                // O ideal é que seja única e possa ser nula (para usuários que não são estudantes, como Admin/Staff)
                $table->string('enrollment_number', 255)->unique()->nullable()->after('email'); 
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down()
{
    Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'enrollment_number')) {
            $table->dropColumn('enrollment_number');
        }
    });
}

    };