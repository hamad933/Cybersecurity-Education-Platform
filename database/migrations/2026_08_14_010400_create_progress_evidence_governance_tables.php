<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subject_actor_id');
            $table->uuid('submitted_by');
            $table->string('source_type', 64);
            $table->string('source_id', 160);
            $table->string('source_revision', 80);
            $table->char('source_digest', 64);
            $table->jsonb('selected_material_refs');
            $table->string('capability_id', 100);
            $table->text('evidence_claim');
            $table->jsonb('criterion_scope');
            $table->string('governed_purpose', 180);
            $table->char('semantic_identity_digest', 64);
            $table->string('proposed_title', 180);
            $table->text('proposed_summary');
            $table->jsonb('proposed_facts');
            $table->jsonb('metadata');
            $table->string('state', 32)->default('RECEIVED');
            $table->uuid('admitted_evidence_id')->nullable();
            $table->timestampTz('admitted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['subject_actor_id', 'semantic_identity_digest'], 'evidence_candidates_semantic_identity_unique');
            $table->index(['subject_actor_id', 'state']);
        });

        Schema::create('governed_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->unique();
            $table->uuid('subject_actor_id');
            $table->string('capability_id', 100);
            $table->text('evidence_claim');
            $table->string('governed_purpose', 180);
            $table->string('lifecycle_state', 24)->default('ACTIVE');
            $table->string('review_status', 24)->default('UNREVIEWED');
            $table->string('effective_review_decision', 40)->default('NONE');
            $table->uuid('effective_review_decision_id')->nullable();
            $table->unsignedInteger('current_revision_number')->default(1);
            $table->uuid('admitted_by');
            $table->timestampTz('admitted_at');
            $table->timestampsTz();
            $table->foreign('candidate_id')->references('id')->on('evidence_candidates')->restrictOnDelete();
            $table->index(['subject_actor_id', 'lifecycle_state']);
            $table->index(['subject_actor_id', 'review_status']);
        });

        Schema::create('governed_evidence_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('evidence_id');
            $table->uuid('previous_revision_id')->nullable()->unique();
            $table->unsignedInteger('revision');
            $table->string('title', 180);
            $table->text('summary');
            $table->jsonb('facts');
            $table->jsonb('selected_material_refs');
            $table->jsonb('criterion_scope');
            $table->string('source_type', 64);
            $table->string('source_id', 160);
            $table->string('source_revision', 80);
            $table->char('source_digest', 64);
            $table->text('revision_reason');
            $table->char('content_digest', 64);
            $table->uuid('sealed_by');
            $table->timestampTz('sealed_at');
            $table->timestampsTz();
            $table->unique(['evidence_id', 'revision'], 'governed_evidence_revision_unique');
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
        });

        Schema::table('governed_evidence_revisions', function (Blueprint $table): void {
            $table->foreign('previous_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
        });

        Schema::create('evidence_review_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('evidence_id');
            $table->uuid('evidence_revision_id');
            $table->uuid('requested_by');
            $table->string('review_scope_key', 160);
            $table->jsonb('criterion_refs');
            $table->string('purpose', 180);
            $table->uuid('prior_decision_id')->nullable();
            $table->string('status', 32)->default('REQUESTED');
            $table->timestampTz('requested_at');
            $table->uuid('assigned_reviewer_id')->nullable();
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
            $table->index(['evidence_id', 'review_scope_key', 'status'], 'evidence_review_request_scope_status_idx');
        });

        Schema::create('evidence_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('review_request_id')->unique();
            $table->uuid('evidence_id');
            $table->uuid('evidence_revision_id');
            $table->uuid('reviewer_id');
            $table->string('review_scope_key', 160);
            $table->jsonb('criterion_refs');
            $table->string('status', 32)->default('IN_REVIEW');
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('review_request_id')->references('id')->on('evidence_review_requests')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
            $table->index(['reviewer_id', 'status']);
        });

        Schema::create('evidence_review_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->string('criterion_key', 120);
            $table->string('finding', 32);
            $table->text('statement');
            $table->jsonb('supporting_evidence_revision_ids');
            $table->uuid('recorded_by');
            $table->timestampTz('recorded_at');
            $table->timestampsTz();
            $table->unique(['review_id', 'criterion_key'], 'evidence_review_finding_criterion_unique');
            $table->foreign('review_id')->references('id')->on('evidence_reviews')->restrictOnDelete();
        });

        Schema::create('evidence_review_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('review_id')->unique();
            $table->uuid('evidence_id');
            $table->uuid('evidence_revision_id');
            $table->uuid('supersedes_decision_id')->nullable()->unique();
            $table->string('review_scope_key', 160);
            $table->string('decision', 40);
            $table->text('rationale');
            $table->uuid('decided_by');
            $table->timestampTz('decided_at');
            $table->timestampsTz();
            $table->foreign('review_id')->references('id')->on('evidence_reviews')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
            $table->foreign('supersedes_decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
            $table->index(['evidence_id', 'review_scope_key', 'decided_at'], 'evidence_review_decision_scope_idx');
        });

        Schema::table('governed_evidence', function (Blueprint $table): void {
            $table->foreign('effective_review_decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
        });

        Schema::table('evidence_review_requests', function (Blueprint $table): void {
            $table->foreign('prior_decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
        });

        Schema::create('evidence_mastery_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subject_actor_id');
            $table->string('target_type', 24)->default('CAPABILITY');
            $table->string('target_id', 100);
            $table->string('policy_revision_id', 120);
            $table->string('judgment', 32);
            $table->string('freshness_status', 32);
            $table->jsonb('review_decision_ids');
            $table->jsonb('supporting_evidence_revision_ids');
            $table->jsonb('contradicting_evidence_revision_ids');
            $table->text('rationale');
            $table->uuid('evaluator_id');
            $table->char('content_digest', 64);
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();
            $table->index(['subject_actor_id', 'target_type', 'target_id'], 'evidence_mastery_eval_target_idx');
        });

        Schema::create('evidence_mastery_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subject_actor_id');
            $table->string('target_type', 24)->default('CAPABILITY');
            $table->string('target_id', 100);
            $table->string('judgment', 32);
            $table->string('freshness_status', 32);
            $table->string('policy_revision_id', 120);
            $table->uuid('evaluation_id')->unique();
            $table->uuid('previous_state_id')->nullable()->unique();
            $table->text('reason');
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();
            $table->foreign('evaluation_id')->references('id')->on('evidence_mastery_evaluations')->restrictOnDelete();
            $table->foreign('previous_state_id')->references('id')->on('evidence_mastery_states')->restrictOnDelete();
            $table->index(['subject_actor_id', 'target_type', 'target_id', 'evaluated_at'], 'evidence_mastery_state_history_idx');
        });

        Schema::create('evidence_mastery_state_decisions', function (Blueprint $table): void {
            $table->uuid('mastery_state_id');
            $table->uuid('review_decision_id');
            $table->primary(['mastery_state_id', 'review_decision_id'], 'evidence_mastery_state_decision_pk');
            $table->timestampTz('created_at');
            $table->foreign('mastery_state_id')->references('id')->on('evidence_mastery_states')->restrictOnDelete();
            $table->foreign('review_decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
        });

        Schema::create('evidence_mastery_state_evidence', function (Blueprint $table): void {
            $table->uuid('mastery_state_id');
            $table->uuid('evidence_revision_id');
            $table->string('contribution', 24);
            $table->primary(['mastery_state_id', 'evidence_revision_id'], 'evidence_mastery_state_evidence_pk');
            $table->timestampTz('created_at');
            $table->foreign('mastery_state_id')->references('id')->on('evidence_mastery_states')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
        });

        Schema::create('evidence_portfolios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_actor_id');
            $table->string('name', 180);
            $table->string('view_scope', 120)->nullable();
            $table->string('grouping', 80)->default('CAPABILITY');
            $table->jsonb('filters');
            $table->jsonb('annotations');
            $table->timestampsTz();
            $table->index('owner_actor_id');
        });

        Schema::create('evidence_portfolio_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('portfolio_id');
            $table->uuid('evidence_id');
            $table->uuid('mastery_state_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('annotation')->nullable();
            $table->timestampsTz();
            $table->unique(['portfolio_id', 'evidence_id'], 'evidence_portfolio_item_unique');
            $table->foreign('portfolio_id')->references('id')->on('evidence_portfolios')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('mastery_state_id')->references('id')->on('evidence_mastery_states')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE evidence_candidates ADD CONSTRAINT evidence_candidate_state_check CHECK (state IN ('RECEIVED','DRAFT','PREPARED','SUBMITTED_FOR_INTAKE','RETURNED_FOR_CONTEXT','ADMITTED','DECLINED','WITHDRAWN'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_lifecycle_check CHECK (lifecycle_state IN ('ACTIVE','WITHDRAWN','SUPERSEDED'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_review_status_check CHECK (review_status IN ('UNREVIEWED','IN_REVIEW','REVIEWED'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_effective_decision_check CHECK (effective_review_decision IN ('NONE','ACCEPT','ACCEPT_WITH_LIMITATIONS','MORE_EVIDENCE_REQUIRED','REJECT'))");
        DB::statement("ALTER TABLE evidence_review_requests ADD CONSTRAINT evidence_review_request_status_check CHECK (status IN ('REQUESTED','ASSIGNED','IN_REVIEW','READY_FOR_DECISION','CLOSED','CANCELLED'))");
        DB::statement("ALTER TABLE evidence_reviews ADD CONSTRAINT evidence_review_status_check CHECK (status IN ('IN_REVIEW','READY_FOR_DECISION','CLOSED','CANCELLED'))");
        DB::statement("ALTER TABLE evidence_review_findings ADD CONSTRAINT evidence_review_finding_check CHECK (finding IN ('SATISFIED','PARTIALLY_SATISFIED','NOT_SATISFIED','NOT_ASSESSABLE'))");
        DB::statement("ALTER TABLE evidence_review_decisions ADD CONSTRAINT evidence_review_decision_check CHECK (decision IN ('ACCEPT','ACCEPT_WITH_LIMITATIONS','MORE_EVIDENCE_REQUIRED','REJECT'))");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_target_type_check CHECK (target_type = 'CAPABILITY')");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_judgment_check CHECK (judgment IN ('NOT_EVALUATED','INSUFFICIENT_EVIDENCE','INCONCLUSIVE','NOT_MASTERED','MASTERED'))");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_freshness_check CHECK (freshness_status IN ('CURRENT','REVALIDATION_REQUIRED'))");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_target_type_check CHECK (target_type = 'CAPABILITY')");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_judgment_check CHECK (judgment IN ('NOT_EVALUATED','INSUFFICIENT_EVIDENCE','INCONCLUSIVE','NOT_MASTERED','MASTERED'))");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_freshness_check CHECK (freshness_status IN ('CURRENT','REVALIDATION_REQUIRED'))");
        DB::statement("ALTER TABLE evidence_mastery_state_evidence ADD CONSTRAINT evidence_mastery_state_evidence_contribution_check CHECK (contribution IN ('SUPPORTING','CONTRADICTING'))");

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION cep_reject_immutable_governance_row()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    RAISE EXCEPTION '% is immutable once sealed', TG_TABLE_NAME USING ERRCODE = '55000';
END;
$fn$;

CREATE TRIGGER governed_evidence_revisions_immutable
BEFORE UPDATE OR DELETE ON governed_evidence_revisions
FOR EACH ROW EXECUTE FUNCTION cep_reject_immutable_governance_row();

CREATE TRIGGER evidence_review_decisions_immutable
BEFORE UPDATE OR DELETE ON evidence_review_decisions
FOR EACH ROW EXECUTE FUNCTION cep_reject_immutable_governance_row();

CREATE TRIGGER evidence_mastery_states_immutable
BEFORE UPDATE OR DELETE ON evidence_mastery_states
FOR EACH ROW EXECUTE FUNCTION cep_reject_immutable_governance_row();
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS evidence_mastery_states_immutable ON evidence_mastery_states;
DROP TRIGGER IF EXISTS evidence_review_decisions_immutable ON evidence_review_decisions;
DROP TRIGGER IF EXISTS governed_evidence_revisions_immutable ON governed_evidence_revisions;
DROP FUNCTION IF EXISTS cep_reject_immutable_governance_row();
SQL);

        Schema::dropIfExists('evidence_portfolio_items');
        Schema::dropIfExists('evidence_portfolios');
        Schema::dropIfExists('evidence_mastery_state_evidence');
        Schema::dropIfExists('evidence_mastery_state_decisions');
        Schema::dropIfExists('evidence_mastery_states');
        Schema::dropIfExists('evidence_mastery_evaluations');

        Schema::table('evidence_review_requests', function (Blueprint $table): void {
            $table->dropForeign(['prior_decision_id']);
        });
        Schema::table('governed_evidence', function (Blueprint $table): void {
            $table->dropForeign(['effective_review_decision_id']);
        });

        Schema::dropIfExists('evidence_review_decisions');
        Schema::dropIfExists('evidence_review_findings');
        Schema::dropIfExists('evidence_reviews');
        Schema::dropIfExists('evidence_review_requests');
        Schema::dropIfExists('governed_evidence_revisions');
        Schema::dropIfExists('governed_evidence');
        Schema::dropIfExists('evidence_candidates');
    }
};
