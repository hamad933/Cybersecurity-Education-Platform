<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_run_result_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('result_id');
            $table->unsignedInteger('revision');
            $table->string('outcome', 32);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('summary_ar');
            $table->jsonb('sealed_payload');
            $table->jsonb('replay_timeline');
            $table->jsonb('artifacts');
            $table->text('correction_reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('sealed_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['result_id', 'revision'], 'sim_result_revision_unique');
            $table->foreign('result_id', 'sim_result_revision_result_fk')
                ->references('id')
                ->on('simulation_run_results')
                ->restrictOnDelete();
        });

        DB::statement("ALTER TABLE simulation_run_result_revisions ADD CONSTRAINT sim_result_revision_outcome_check CHECK (outcome IN ('ACHIEVED','PARTIAL','NOT_ACHIEVED','INCONCLUSIVE','NOT_EVALUATED'))");
        DB::statement('ALTER TABLE simulation_run_result_revisions ADD CONSTRAINT sim_result_revision_score_check CHECK (score IS NULL OR (score >= 0 AND score <= 100))');
        DB::statement("ALTER TABLE simulation_run_result_revisions ADD CONSTRAINT sim_result_revision_reason_check CHECK ((revision = 1 AND correction_reason IS NULL) OR (revision > 1 AND correction_reason IS NOT NULL AND length(trim(correction_reason)) > 0))");

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_simulation_run_result_revision_mutation() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'sealed simulation run result revisions are immutable' USING ERRCODE = '55000';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_run_result_revisions_immutable
BEFORE UPDATE OR DELETE ON simulation_run_result_revisions
FOR EACH ROW EXECUTE FUNCTION prevent_simulation_run_result_revision_mutation();
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS simulation_run_result_revisions_immutable ON simulation_run_result_revisions');
        DB::statement('DROP FUNCTION IF EXISTS prevent_simulation_run_result_revision_mutation()');
        Schema::dropIfExists('simulation_run_result_revisions');
    }
};
