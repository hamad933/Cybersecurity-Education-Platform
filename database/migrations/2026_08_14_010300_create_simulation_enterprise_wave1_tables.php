<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_enterprises', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 120)->unique();
            $table->string('name_ar', 240);
            $table->string('name_en', 240)->nullable();
            $table->text('description_ar')->nullable();
            $table->jsonb('definition');
            $table->boolean('is_fixture')->default(false);
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
        });

        Schema::create('simulation_digital_twin_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->unsignedInteger('revision');
            $table->string('status', 24);
            $table->jsonb('topology');
            $table->jsonb('behavior_model');
            $table->char('digest', 64);
            $table->timestampTz('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->unique(['enterprise_id', 'revision'], 'sim_twin_enterprise_revision_unique');
            $table->foreign('enterprise_id', 'sim_twin_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
        });

        Schema::create('simulation_baselines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('digital_twin_revision_id');
            $table->unsignedInteger('revision');
            $table->string('status', 24);
            $table->jsonb('state');
            $table->char('digest', 64);
            $table->timestampTz('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->unique(['enterprise_id', 'revision'], 'sim_baseline_enterprise_revision_unique');
            $table->foreign('enterprise_id', 'sim_baseline_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
            $table->foreign('digital_twin_revision_id', 'sim_baseline_twin_fk')->references('id')->on('simulation_digital_twin_revisions')->restrictOnDelete();
        });

        Schema::create('simulation_scenario_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('baseline_id');
            $table->string('slug', 140);
            $table->string('title_ar', 240);
            $table->string('title_en', 240)->nullable();
            $table->unsignedInteger('revision');
            $table->string('status', 24);
            $table->jsonb('orchestration');
            $table->jsonb('validation');
            $table->char('digest', 64);
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->unique(['slug', 'revision'], 'sim_scenario_slug_revision_unique');
            $table->foreign('enterprise_id', 'sim_scenario_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
            $table->foreign('baseline_id', 'sim_scenario_baseline_fk')->references('id')->on('simulation_baselines')->restrictOnDelete();
        });

        Schema::create('simulation_lab_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('baseline_id');
            $table->string('slug', 140);
            $table->string('title_ar', 240);
            $table->string('title_en', 240)->nullable();
            $table->unsignedInteger('revision');
            $table->string('status', 24);
            $table->jsonb('configuration');
            $table->jsonb('validation');
            $table->char('digest', 64);
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->unique(['slug', 'revision'], 'sim_lab_slug_revision_unique');
            $table->foreign('enterprise_id', 'sim_lab_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
            $table->foreign('baseline_id', 'sim_lab_baseline_fk')->references('id')->on('simulation_baselines')->restrictOnDelete();
        });

        Schema::create('simulation_scenario_lab_references', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('scenario_definition_id');
            $table->uuid('lab_definition_id');
            $table->string('module_key', 120);
            $table->unsignedInteger('ordinal')->default(1);
            $table->jsonb('policy');
            $table->timestampsTz();
            $table->unique(['scenario_definition_id', 'module_key'], 'sim_scenario_module_key_unique');
            $table->foreign('scenario_definition_id', 'sim_scenario_lab_scenario_fk')->references('id')->on('simulation_scenario_definitions')->cascadeOnDelete();
            $table->foreign('lab_definition_id', 'sim_scenario_lab_lab_fk')->references('id')->on('simulation_lab_definitions')->restrictOnDelete();
        });

        Schema::create('simulation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_id');
            $table->uuid('digital_twin_revision_id');
            $table->uuid('baseline_id');
            $table->string('run_type', 32);
            $table->uuid('scenario_definition_id')->nullable();
            $table->uuid('standalone_lab_definition_id')->nullable();
            $table->string('lifecycle', 24);
            $table->jsonb('execution_policies');
            $table->bigInteger('seed');
            $table->jsonb('runtime_state');
            $table->char('input_digest', 64);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('prepared_at')->nullable();
            $table->timestampTz('ready_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('stopped_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('enterprise_id', 'sim_run_enterprise_fk')->references('id')->on('simulation_enterprises')->restrictOnDelete();
            $table->foreign('digital_twin_revision_id', 'sim_run_twin_fk')->references('id')->on('simulation_digital_twin_revisions')->restrictOnDelete();
            $table->foreign('baseline_id', 'sim_run_baseline_fk')->references('id')->on('simulation_baselines')->restrictOnDelete();
            $table->foreign('scenario_definition_id', 'sim_run_scenario_fk')->references('id')->on('simulation_scenario_definitions')->restrictOnDelete();
            $table->foreign('standalone_lab_definition_id', 'sim_run_lab_fk')->references('id')->on('simulation_lab_definitions')->restrictOnDelete();
        });

        Schema::create('simulation_run_lab_module_instances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('run_id');
            $table->uuid('scenario_lab_reference_id');
            $table->uuid('lab_definition_id');
            $table->string('instance_key', 160);
            $table->jsonb('state');
            $table->timestampsTz();
            $table->unique(['run_id', 'instance_key'], 'sim_run_module_instance_unique');
            $table->foreign('run_id', 'sim_run_module_run_fk')->references('id')->on('simulation_runs')->cascadeOnDelete();
            $table->foreign('scenario_lab_reference_id', 'sim_run_module_ref_fk')->references('id')->on('simulation_scenario_lab_references')->restrictOnDelete();
            $table->foreign('lab_definition_id', 'sim_run_module_lab_fk')->references('id')->on('simulation_lab_definitions')->restrictOnDelete();
        });

        Schema::create('simulation_run_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('run_id');
            $table->bigInteger('sequence');
            $table->string('event_type', 80);
            $table->jsonb('payload');
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['run_id', 'sequence'], 'sim_run_event_sequence_unique');
            $table->foreign('run_id', 'sim_run_event_run_fk')->references('id')->on('simulation_runs')->cascadeOnDelete();
        });

        Schema::create('simulation_runtime_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('run_id');
            $table->unsignedInteger('sequence');
            $table->bigInteger('event_sequence');
            $table->uuid('digital_twin_revision_id');
            $table->uuid('baseline_id');
            $table->jsonb('state');
            $table->char('state_digest', 64);
            $table->timestampTz('captured_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['run_id', 'sequence'], 'sim_runtime_snapshot_sequence_unique');
            $table->foreign('run_id', 'sim_snapshot_run_fk')->references('id')->on('simulation_runs')->cascadeOnDelete();
            $table->foreign('digital_twin_revision_id', 'sim_snapshot_twin_fk')->references('id')->on('simulation_digital_twin_revisions')->restrictOnDelete();
            $table->foreign('baseline_id', 'sim_snapshot_baseline_fk')->references('id')->on('simulation_baselines')->restrictOnDelete();
        });

        Schema::create('simulation_run_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('run_id')->unique();
            $table->string('outcome', 32);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('summary_ar');
            $table->jsonb('sealed_payload');
            $table->jsonb('replay_timeline');
            $table->jsonb('artifacts');
            $table->timestampTz('sealed_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign('run_id', 'sim_result_run_fk')->references('id')->on('simulation_runs')->restrictOnDelete();
        });

        Schema::create('simulation_candidate_evidence_handoffs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('result_id')->unique();
            $table->string('status', 32);
            $table->jsonb('candidate_manifest');
            $table->string('intake_contract_ref', 160)->nullable();
            $table->timestampTz('handed_off_at')->nullable();
            $table->timestampsTz();
            $table->foreign('result_id', 'sim_handoff_result_fk')->references('id')->on('simulation_run_results')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE simulation_digital_twin_revisions ADD CONSTRAINT sim_twin_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_baselines ADD CONSTRAINT sim_baseline_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_scenario_definitions ADD CONSTRAINT sim_scenario_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_lab_definitions ADD CONSTRAINT sim_lab_status_check CHECK (status IN ('DRAFT','PUBLISHED'))");
        DB::statement("ALTER TABLE simulation_runs ADD CONSTRAINT sim_run_type_check CHECK (run_type IN ('Standalone Lab Run','Scenario Run'))");
        DB::statement("ALTER TABLE simulation_runs ADD CONSTRAINT sim_run_lifecycle_check CHECK (lifecycle IN ('PREPARING','READY','RUNNING','PAUSED','COMPLETED','STOPPED','FAILED'))");
        DB::statement("ALTER TABLE simulation_runs ADD CONSTRAINT sim_run_definition_check CHECK ((run_type = 'Scenario Run' AND scenario_definition_id IS NOT NULL AND standalone_lab_definition_id IS NULL) OR (run_type = 'Standalone Lab Run' AND scenario_definition_id IS NULL AND standalone_lab_definition_id IS NOT NULL))");
        DB::statement("ALTER TABLE simulation_run_results ADD CONSTRAINT sim_result_outcome_check CHECK (outcome IN ('ACHIEVED','PARTIAL','NOT_ACHIEVED','INCONCLUSIVE','NOT_EVALUATED'))");
        DB::statement("ALTER TABLE simulation_run_results ADD CONSTRAINT sim_result_score_check CHECK (score IS NULL OR (score >= 0 AND score <= 100))");
        DB::statement("ALTER TABLE simulation_candidate_evidence_handoffs ADD CONSTRAINT sim_handoff_status_check CHECK (status IN ('READY_FOR_INTAKE','HANDED_OFF'))");

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_simulation_run_result_mutation() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'sealed simulation run results are immutable' USING ERRCODE = '55000';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER simulation_run_results_immutable
BEFORE UPDATE OR DELETE ON simulation_run_results
FOR EACH ROW EXECUTE FUNCTION prevent_simulation_run_result_mutation();
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS simulation_run_results_immutable ON simulation_run_results');
        DB::statement('DROP FUNCTION IF EXISTS prevent_simulation_run_result_mutation()');

        Schema::dropIfExists('simulation_candidate_evidence_handoffs');
        Schema::dropIfExists('simulation_run_results');
        Schema::dropIfExists('simulation_runtime_snapshots');
        Schema::dropIfExists('simulation_run_events');
        Schema::dropIfExists('simulation_run_lab_module_instances');
        Schema::dropIfExists('simulation_runs');
        Schema::dropIfExists('simulation_scenario_lab_references');
        Schema::dropIfExists('simulation_lab_definitions');
        Schema::dropIfExists('simulation_scenario_definitions');
        Schema::dropIfExists('simulation_baselines');
        Schema::dropIfExists('simulation_digital_twin_revisions');
        Schema::dropIfExists('simulation_enterprises');
    }
};
