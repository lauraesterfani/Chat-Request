<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER audit_records_prevent_update BEFORE UPDATE ON audit_records BEGIN SELECT RAISE(ABORT, 'Audit records are append-only'); END");
            DB::unprepared("CREATE TRIGGER audit_records_prevent_delete BEFORE DELETE ON audit_records BEGIN SELECT RAISE(ABORT, 'Audit records are append-only'); END");

            return;
        }

        if ($driver === 'mysql') {
            DB::unprepared("CREATE TRIGGER audit_records_prevent_update BEFORE UPDATE ON audit_records FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit records are append-only'");
            DB::unprepared("CREATE TRIGGER audit_records_prevent_delete BEFORE DELETE ON audit_records FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit records are append-only'");

            return;
        }

        throw new RuntimeException("Append-only audit protection is not implemented for database driver [{$driver}].");
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_records_prevent_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_records_prevent_delete');
        } elseif ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_records_prevent_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_records_prevent_delete');
        }
    }
};
