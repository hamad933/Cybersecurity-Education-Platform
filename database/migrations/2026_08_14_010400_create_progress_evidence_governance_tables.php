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
            $table->string('source_revision', 80)->nullable();
            $table->char('source_digest', 64);
            $table->string('capability_id', 100);
            $table->string('proposed_title', 180);
            $table->text('proposed_summary');
            $table->jsonb('proposed_facts');
            $table->jsonb('metadata');
            $table->string('state', 24)->default('CANDIDATE');
            $table->uuid('admitted_evidence_id')->nullable();
            $table->timestampTz('admitted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['subject_actor_id', 'source_type', 'source_id', 'source_digest'], 'evidence_candidates_source_unique');
            $table->index(['subject_actor_id', 'state']);
        });

        Schema::create('governed_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->unique();
            $table->uuid('subject_actor_id');
            $table->string('capability_id', 100);
            $table->string('lifecycle_state', 24)->default('ACTIVE');
            $table->string('review_status', 24)->default('UNREVIEWED');
            $table->string('effective_review_decision', 40)->default('NONE');
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
            $table->unsignedInteger('revision');
            $table->string('title', 180);
            $table->text('summary');
            $table->jsonb('facts');
            $table->string('source_type', 64);
            $table->string('source_id', 160);
            $table->string('source_revision', 80)->nullable();
            $table->char('source_digest', 64);
            $table->char('content_digest', 64);
            $table->uuid('sealed_by');
            $table->timestampTz('sealed_at');
            $table->timestampsTz();
            $table->unique(['evidence_id', 'revision'], 'governed_evidence_revision_unique');
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
        });

        Schema::create('evidence_review_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('evidence_id');
            $table->uuid('requested_by');
            $table->string('status', 24)->default('REQUESTED');
            $table->timestampTz('requested_at');
            $table->uuid('admitted_by')->nullable();
            $table->timestampTz('admitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->index(['evidence_id', 'status']);
        });

        Schema::create('evidence_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('review_request_id')->unique();
            $table->uuid('evidence_id');
            $table->uuid('reviewer_id');
            $table->string('status', 24)->default('IN_REVIEW');
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('review_request_id')->references('id')->on('evidence_review_requests')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->index(['reviewer_id', 'status']);
        });

        Schema::create('evidence_review_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('review_id');
            $table->string('criterion_key', 120);
            $table->string('finding', 32);
            $table->text('statement');
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
            $table->string('decision', 40);
            $table->text('rationale');
            $table->uuid('decided_by');
            $table->timestampTz('decided_at');
            $table->timestampsTz();
            $table->foreign('review_id')->references('id')->on('evidence_reviews')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->index(['evidence_id', 'decided_at']);
        });

        Schema::create('evidence_mastery_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('subject_actor_id');
            $table->string('target_type', 24)->default('CAPABILITY');
            $table->string('target_id', 100);
            $table->string('policy_revision_id', 120);
            $table->string('judgment', 32);
            $table->string('freshness_status', 32);
            $table->jsonb('supporting_evidence_ids');
            $table->jsonb('contradicting_evidence_ids');
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
            $table->string('judgment', 32)->default('NOT_EVALUATED');
            $table->string('freshness_status', 32)->default('CURRENT');
            $table->uuid('latest_evaluation_id');
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();
            $table->unique(['subject_actor_id', 'target_type', 'target_id'], 'evidence_mastery_state_target_unique');
            $table->foreign('latest_evaluation_id')->references('id')->on('evidence_mastery_evaluations')->restrictOnDelete();
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

        DB::statement("ALTER TABLE evidence_candidates ADD CONSTRAINT evidence_candidate_state_check CHECK (state IN ('CANDIDATE','ADMITTED','DECLINED'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_lifecycle_check CHECK (lifecycle_state IN ('ACTIVE','WITHDRAWN','SUPERSEDED'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_review_status_check CHECK (review_status IN ('UNREVIEWED','IN_REVIEW','REVIEWED'))");
        DB::statement("ALTER TABLE governed_evidence ADD CONSTRAINT governed_evidence_effective_decision_check CHECK (effective_review_decision IN ('NONE','ACCEPT','ACCEPT_WITH_LIMITATIONS','MORE_EVIDENCE_REQUIRED','REJECT'))");
        DB::statement("ALTER TABLE evidence_review_requests ADD CONSTRAINT evidence_review_request_status_check CHECK (status IN ('REQUESTED','ADMITTED','CANCELLED','COMPLETED'))");
        DB::statement("ALTER TABLE evidence_reviews ADD CONSTRAINT evidence_review_status_check CHECK (status IN ('IN_REVIEW','COMPLETED'))");
        DB::statement("ALTER TABLE evidence_review_findings ADD CONSTRAINT evidence_review_finding_check CHECK (finding IN ('SATISFIED','PARTIALLY_SATISFIED','NOT_SATISFIED','NOT_ASSESSABLE'))");
        DB::statement("ALTER TABLE evidence_review_decisions ADD CONSTRAINT evidence_review_decision_check CHECK (decision IN ('ACCEPT','ACCEPT_WITH_LIMITATIONS','MORE_EVIDENCE_REQUIRED','REJECT'))");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_target_type_check CHECK (target_type = 'CAPABILITY')");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_judgment_check CHECK (judgment IN ('NOT_EVALUATED','INSUFFICIENT_EVIDENCE','INCONCLUSIVE','NOT_MASTERED','MASTERED'))");
        DB::statement("ALTER TABLE evidence_mastery_evaluations ADD CONSTRAINT evidence_mastery_eval_freshness_check CHECK (freshness_status IN ('CURRENT','REVALIDATION_REQUIRED'))");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_target_type_check CHECK (target_type = 'CAPABILITY')");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_judgment_check CHECK (judgment IN ('NOT_EVALUATED','INSUFFICIENT_EVIDENCE','INCONCLUSIVE','NOT_MASTERED','MASTERED'))");
        DB::statement("ALTER TABLE evidence_mastery_states ADD CONSTRAINT evidence_mastery_state_freshness_check CHECK (freshness_status IN ('CURRENT','REVALIDATION_REQUIRED'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_portfolio_items');
        Schema::dropIfExists('evidence_portfolios');
        Schema::dropIfExists('evidence_mastery_states');
        Schema::dropIfExists('evidence_mastery_evaluations');
        Schema::dropIfExists('evidence_review_decisions');
        Schema::dropIfExists('evidence_review_findings');
        Schema::dropIfExists('evidence_reviews');
        Schema::dropIfExists('evidence_review_requests');
        Schema::dropIfExists('governed_evidence_revisions');
        Schema::dropIfExists('governed_evidence');
        Schema::dropIfExists('evidence_candidates');
    }
};
