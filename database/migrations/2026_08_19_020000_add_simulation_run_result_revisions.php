<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add previous_result_id and correction_reason
        Schema::table('simulation_run_results', function (Blueprint $table): void {
            // Drop the old unique constraint on run_id first so we can have multiple revisions for the same run
            $table->dropUnique(['run_id']);

            $table->uuid('previous_result_id')->nullable();
            $table->text('correction_reason')->nullable();

            $table->foreign('previous_result_id', 'sim_result_prev_fk')->references('id')->on('simulation_run_results')->restrictOnDelete();

            // Add unique constraint for run_id and result_revision combination
            $table->unique(['run_id', 'result_revision'], 'sim_result_run_rev_unq');
        });

        // Add constraints for revisions
        DB::statement('ALTER TABLE simulation_run_results ADD CONSTRAINT sim_result_revision_check CHECK ((result_revision = 1 AND previous_result_id IS NULL AND correction_reason IS NULL) OR (result_revision > 1 AND previous_result_id IS NOT NULL AND correction_reason IS NOT NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE simulation_run_results DROP CONSTRAINT IF EXISTS sim_result_revision_check');

        Schema::table('simulation_run_results', function (Blueprint $table): void {
            $table->dropUnique('sim_result_run_rev_unq');
            $table->dropForeign('sim_result_prev_fk');
            $table->dropColumn(['previous_result_id', 'correction_reason']);
            $table->unique('run_id');
        });
    }
};
