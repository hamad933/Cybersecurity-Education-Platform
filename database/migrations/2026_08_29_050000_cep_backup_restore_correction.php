<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE restore_runs DROP CONSTRAINT IF EXISTS restore_run_status_check');
        DB::statement("ALTER TABLE restore_runs ADD CONSTRAINT restore_run_status_check CHECK (status IN ('staged','verified','failed','activation_pending','abandoned','rollback_failed','applying'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE restore_runs DROP CONSTRAINT IF EXISTS restore_run_status_check');
        DB::statement("ALTER TABLE restore_runs ADD CONSTRAINT restore_run_status_check CHECK (status IN ('staged','verified','failed'))");
    }
};
