<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulator_rule_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('rule_set_id', 100);
            $table->unsignedInteger('revision');
            $table->string('authority_baseline_id', 100);
            $table->string('state', 24);
            $table->jsonb('rules');
            $table->char('digest', 64);
            $table->timestampTz('approved_at');
            $table->timestampsTz();
            $table->unique(['rule_set_id', 'revision']);
        });
        Schema::create('scenario_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scenario_id', 100);
            $table->unsignedInteger('revision');
            $table->string('state', 24);
            $table->uuid('rule_set_revision_id');
            $table->uuid('enterprise_baseline_revision_id');
            $table->jsonb('cases');
            $table->char('digest', 64);
            $table->timestampTz('published_at');
            $table->timestampsTz();
            $table->unique(['scenario_id', 'revision']);
            $table->foreign('rule_set_revision_id')->references('id')->on('simulator_rule_revisions')->restrictOnDelete();
        });
        Schema::create('scenario_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('scenario_revision_id');
            $table->uuid('rule_set_revision_id');
            $table->uuid('enterprise_baseline_revision_id');
            $table->string('case_id', 100);
            $table->unsignedBigInteger('seed');
            $table->string('status', 24);
            $table->jsonb('ordered_actions');
            $table->jsonb('normalized_input');
            $table->char('input_digest', 64);
            $table->char('baseline_digest_before', 64);
            $table->char('baseline_digest_after', 64)->nullable();
            $table->string('outcome', 32)->nullable();
            $table->char('trace_digest', 64)->nullable();
            $table->string('idempotency_key', 200)->unique();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('scenario_revision_id')->references('id')->on('scenario_revisions')->restrictOnDelete();
            $table->foreign('rule_set_revision_id')->references('id')->on('simulator_rule_revisions')->restrictOnDelete();
        });
        Schema::create('decision_traces', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('scenario_run_id')->unique();
            $table->jsonb('trace');
            $table->char('output_digest', 64);
            $table->timestampsTz();
            $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->cascadeOnDelete();
        });
        Schema::create('replay_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('original_run_id');
            $table->uuid('replay_run_id');
            $table->boolean('digest_match');
            $table->char('original_digest', 64);
            $table->char('replay_digest', 64);
            $table->timestampsTz();
            $table->foreign('original_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
            $table->foreign('replay_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });

        Schema::create('evidence_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('origin', 24);
            $table->string('capability_id', 80);
            $table->string('knowledge_unit_id', 80);
            $table->uuid('scenario_revision_id');
            $table->uuid('rule_set_revision_id');
            $table->uuid('enterprise_baseline_revision_id');
            $table->uuid('run_id');
            $table->string('case_id', 100);
            $table->char('input_digest', 64);
            $table->char('trace_digest', 64);
            $table->string('result', 32);
            $table->jsonb('limitations');
            $table->jsonb('source_claim_ids');
            $table->char('content_digest', 64);
            $table->boolean('locked')->default(false);
            $table->timestampsTz();
            $table->unique('run_id');
        });
        Schema::create('evidence_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('evidence_record_id')->unique();
            $table->string('decision', 24);
            $table->text('rationale');
            $table->uuid('decided_by');
            $table->timestampTz('decided_at');
            $table->timestampsTz();
            $table->foreign('evidence_record_id')->references('id')->on('evidence_records')->restrictOnDelete();
        });

        Schema::create('micro_practices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('practice_id', 100);
            $table->unsignedInteger('revision');
            $table->string('capability_id', 80);
            $table->string('knowledge_unit_id', 80);
            $table->jsonb('definition');
            $table->char('digest', 64);
            $table->timestampsTz();
            $table->unique(['practice_id', 'revision']);
        });
        Schema::create('practice_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('micro_practice_id');
            $table->uuid('actor_id');
            $table->string('case_id', 100);
            $table->jsonb('answer');
            $table->string('outcome', 32);
            $table->boolean('rationale_valid');
            $table->string('failure_class', 80)->nullable();
            $table->timestampsTz();
            $table->foreign('micro_practice_id')->references('id')->on('micro_practices')->restrictOnDelete();
        });
        Schema::create('mastery_rule_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('rule_id', 100);
            $table->unsignedInteger('revision');
            $table->jsonb('requirements');
            $table->char('digest', 64);
            $table->string('state', 24);
            $table->timestampsTz();
            $table->unique(['rule_id', 'revision']);
        });
        Schema::create('mastery_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('knowledge_unit_id', 80);
            $table->uuid('mastery_rule_revision_id');
            $table->string('status', 24);
            $table->jsonb('evidence_record_ids');
            $table->char('evaluation_digest', 64);
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();
            $table->unique(['actor_id', 'knowledge_unit_id']);
            $table->foreign('mastery_rule_revision_id')->references('id')->on('mastery_rule_revisions')->restrictOnDelete();
        });
        Schema::create('review_triggers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('knowledge_unit_id', 80);
            $table->string('case_id', 100)->nullable();
            $table->string('failure_class', 80);
            $table->string('source_reference', 200);
            $table->string('status', 24)->default('open');
            $table->timestampTz('scheduled_at');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE simulator_rule_revisions ADD CONSTRAINT simulator_rule_state_check CHECK (state = 'approved')");
        DB::statement("ALTER TABLE scenario_revisions ADD CONSTRAINT scenario_revision_state_check CHECK (state = 'published')");
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_status_check CHECK (status IN ('running','completed','failed','reset'))");
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_outcome_check CHECK (outcome IS NULL OR outcome IN ('ALLOW','DENY','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_origin_check CHECK (origin = 'SIMULATED')");
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_result_check CHECK (result IN ('ALLOW','DENY','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        DB::statement("ALTER TABLE evidence_decisions ADD CONSTRAINT evidence_decision_check CHECK (decision IN ('ACCEPTED','REJECTED','NEEDS_REVIEW'))");
        DB::statement("ALTER TABLE practice_attempts ADD CONSTRAINT practice_outcome_check CHECK (outcome IN ('correct','incorrect','needs_review'))");
        DB::statement("ALTER TABLE mastery_rule_revisions ADD CONSTRAINT mastery_rule_state_check CHECK (state = 'approved')");
        DB::statement("ALTER TABLE mastery_states ADD CONSTRAINT mastery_status_check CHECK (status IN ('NOT_MASTERED','IN_PROGRESS','MASTERED'))");
        DB::statement("ALTER TABLE review_triggers ADD CONSTRAINT review_trigger_status_check CHECK (status IN ('open','scheduled','completed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('review_triggers');
        Schema::dropIfExists('mastery_states');
        Schema::dropIfExists('mastery_rule_revisions');
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('micro_practices');
        Schema::dropIfExists('evidence_decisions');
        Schema::dropIfExists('evidence_records');
        Schema::dropIfExists('replay_records');
        Schema::dropIfExists('decision_traces');
        Schema::dropIfExists('scenario_runs');
        Schema::dropIfExists('scenario_revisions');
        Schema::dropIfExists('simulator_rule_revisions');
    }
};
