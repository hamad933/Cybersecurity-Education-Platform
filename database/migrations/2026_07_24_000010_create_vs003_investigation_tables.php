<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE scenario_runs DROP CONSTRAINT scenario_run_outcome_check');
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_outcome_check CHECK (outcome IS NULL OR outcome IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE','BENIGN_EXPLAINED','SUSPICIOUS','INCIDENT_CONFIRMED','INSUFFICIENT_TELEMETRY'))");
        DB::statement('ALTER TABLE evidence_records DROP CONSTRAINT evidence_result_check');
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_result_check CHECK (result IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE','BENIGN_EXPLAINED','SUSPICIOUS','INCIDENT_CONFIRMED','INSUFFICIENT_TELEMETRY'))");
        Schema::create('vs003_telemetry_dataset_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->string('dataset_id', 100); $table->unsignedInteger('revision');
            $table->string('state', 24); $table->string('timezone', 40); $table->jsonb('events'); $table->char('digest', 64); $table->timestampTz('published_at'); $table->timestampsTz();
            $table->unique(['dataset_id', 'revision']);
        });
        Schema::create('vs003_investigation_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('scenario_revision_id'); $table->uuid('dataset_revision_id'); $table->string('case_id', 100); $table->string('expected_outcome', 32); $table->jsonb('definition'); $table->char('digest', 64); $table->timestampsTz();
            $table->unique(['scenario_revision_id', 'case_id']); $table->foreign('scenario_revision_id')->references('id')->on('scenario_revisions')->restrictOnDelete(); $table->foreign('dataset_revision_id')->references('id')->on('vs003_telemetry_dataset_revisions')->restrictOnDelete();
        });
        Schema::create('vs003_investigation_alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('scenario_run_id')->unique(); $table->string('rule_id', 100); $table->string('state', 24); $table->string('severity', 24); $table->char('timeline_digest', 64); $table->jsonb('rationale'); $table->timestampsTz(); $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });
        Schema::create('vs003_triage_records', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('scenario_run_id')->unique(); $table->uuid('actor_id'); $table->string('outcome', 32); $table->string('severity', 24); $table->string('scope', 80); $table->string('confidence', 24); $table->jsonb('alternative_hypotheses'); $table->jsonb('missing_data'); $table->text('rationale'); $table->string('owner', 100); $table->timestampTz('escalated_at')->nullable(); $table->char('digest', 64); $table->timestampsTz(); $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->restrictOnDelete(); $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::create('vs003_custody_events', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('scenario_run_id'); $table->uuid('actor_id'); $table->string('origin', 24); $table->jsonb('source_event_ids'); $table->char('source_digest', 64); $table->timestampTz('collected_at'); $table->string('storage_reference', 160); $table->string('copy_kind', 24); $table->jsonb('limitations'); $table->char('digest', 64); $table->timestampsTz(); $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->restrictOnDelete(); $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::create('vs003_containment_proposals', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('scenario_run_id'); $table->uuid('actor_id'); $table->string('state', 24); $table->string('proposal_type', 100); $table->text('expected_effect'); $table->text('risk'); $table->text('rollback_condition'); $table->uuid('approved_by')->nullable(); $table->timestampTz('approved_at')->nullable(); $table->char('digest', 64); $table->timestampsTz(); $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->restrictOnDelete(); $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete(); $table->foreign('approved_by')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::create('vs003_control_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->string('control_id', 100); $table->unsignedInteger('revision'); $table->string('state', 24); $table->jsonb('definition'); $table->char('digest', 64); $table->uuid('remediates_run_id'); $table->timestampTz('published_at'); $table->timestampsTz(); $table->unique(['control_id', 'revision']); $table->foreign('remediates_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });
        Schema::create('vs003_verification_replays', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->uuid('original_run_id'); $table->uuid('verification_run_id')->unique(); $table->uuid('control_revision_id'); $table->char('original_timeline_digest', 64); $table->char('verification_timeline_digest', 64); $table->boolean('passed'); $table->char('digest', 64); $table->timestampsTz(); $table->foreign('original_run_id')->references('id')->on('scenario_runs')->restrictOnDelete(); $table->foreign('verification_run_id')->references('id')->on('scenario_runs')->restrictOnDelete(); $table->foreign('control_revision_id')->references('id')->on('vs003_control_revisions')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE vs003_telemetry_dataset_revisions ADD CONSTRAINT vs003_dataset_state CHECK (state = 'published')");
        DB::statement("ALTER TABLE vs003_investigation_alerts ADD CONSTRAINT vs003_alert_state CHECK (state IN ('OPEN','NONE','UNSUPPORTED'))");
        DB::statement("ALTER TABLE vs003_triage_records ADD CONSTRAINT vs003_outcome CHECK (outcome IN ('BENIGN_EXPLAINED','SUSPICIOUS','INCIDENT_CONFIRMED','INSUFFICIENT_TELEMETRY','UNSUPPORTED_STATE'))");
        DB::statement("ALTER TABLE vs003_custody_events ADD CONSTRAINT vs003_custody_origin CHECK (origin = 'SIMULATED')");
        DB::statement("ALTER TABLE vs003_custody_events ADD CONSTRAINT vs003_copy_kind CHECK (copy_kind IN ('PRESERVED_ORIGINAL','WORKING_COPY'))");
        DB::statement("ALTER TABLE vs003_containment_proposals ADD CONSTRAINT vs003_containment_state CHECK (state IN ('PROPOSED','APPROVED','REJECTED'))");
        DB::statement("ALTER TABLE vs003_control_revisions ADD CONSTRAINT vs003_control_state CHECK (state = 'published')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE evidence_records DROP CONSTRAINT evidence_result_check');
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_result_check CHECK (result IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        DB::statement('ALTER TABLE scenario_runs DROP CONSTRAINT scenario_run_outcome_check');
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_outcome_check CHECK (outcome IS NULL OR outcome IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        Schema::dropIfExists('vs003_verification_replays'); Schema::dropIfExists('vs003_control_revisions'); Schema::dropIfExists('vs003_containment_proposals'); Schema::dropIfExists('vs003_custody_events'); Schema::dropIfExists('vs003_triage_records'); Schema::dropIfExists('vs003_investigation_alerts'); Schema::dropIfExists('vs003_investigation_cases'); Schema::dropIfExists('vs003_telemetry_dataset_revisions');
    }
};
