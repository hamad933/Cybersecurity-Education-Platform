<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('authority_class', 40);
            $table->string('title', 300);
            $table->text('exact_url')->nullable();
            $table->string('relative_path', 500)->nullable();
            $table->char('sha256', 64);
            $table->string('review_status', 32);
            $table->jsonb('metadata');
            $table->timestampsTz();
        });
        Schema::create('source_claims', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('source_record_id');
            $table->string('claim_id', 80)->unique();
            $table->string('segment_ref', 300);
            $table->text('supported_scope');
            $table->text('excluded_semantics');
            $table->string('assessment', 40);
            $table->timestampsTz();
            $table->foreign('source_record_id')->references('id')->on('source_records')->cascadeOnDelete();
        });

        Schema::create('knowledge_units', function (Blueprint $table): void {
            $table->string('id', 80)->primary();
            $table->string('title_ar', 300);
            $table->string('title_en', 300);
            $table->timestampsTz();
        });
        Schema::create('lesson_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('knowledge_unit_id', 80);
            $table->unsignedInteger('revision');
            $table->string('state', 24);
            $table->unsignedInteger('lock_version')->default(1);
            $table->jsonb('blocks');
            $table->jsonb('citations');
            $table->string('authority_baseline_id', 100)->nullable();
            $table->char('content_digest', 64);
            $table->string('review_decision', 24)->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->uuid('published_by')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->uuid('derived_from_revision_id')->nullable();
            $table->timestampsTz();
            $table->unique(['knowledge_unit_id', 'revision']);
            $table->foreign('knowledge_unit_id')->references('id')->on('knowledge_units')->restrictOnDelete();
        });
        Schema::table('lesson_revisions', function (Blueprint $table): void {
            $table->foreign('derived_from_revision_id')->references('id')->on('lesson_revisions')->nullOnDelete();
        });

        Schema::create('curriculum_placements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('capability_id', 80);
            $table->string('knowledge_unit_id', 80);
            $table->unsignedInteger('revision');
            $table->jsonb('lifecycle');
            $table->timestampsTz();
            $table->unique(['capability_id', 'knowledge_unit_id', 'revision']);
        });

        Schema::create('enterprise_baseline_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('baseline_id', 100);
            $table->unsignedInteger('revision');
            $table->string('state', 24);
            $table->jsonb('snapshot');
            $table->char('snapshot_digest', 64);
            $table->timestampTz('published_at');
            $table->timestampsTz();
            $table->unique(['baseline_id', 'revision']);
        });
        Schema::create('improvement_proposals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enterprise_baseline_revision_id');
            $table->uuid('scenario_run_id');
            $table->jsonb('proposal');
            $table->string('status', 24)->default('proposed');
            $table->timestampsTz();
            $table->foreign('enterprise_baseline_revision_id')->references('id')->on('enterprise_baseline_revisions')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE source_records ADD CONSTRAINT source_authority_class_check CHECK (authority_class IN ('Product Authority','Technical Authority','Internal Reviewed Support','Generic Context','Reviewer Interpretation','Simulation Rule','Unresolved Gap'))");
        DB::statement("ALTER TABLE source_records ADD CONSTRAINT source_review_status_check CHECK (review_status IN ('reviewed','approved','rejected','unreviewed'))");
        DB::statement("ALTER TABLE source_claims ADD CONSTRAINT source_claim_assessment_check CHECK (assessment IN ('supported','partial','excluded','unresolved'))");
        DB::statement("ALTER TABLE lesson_revisions ADD CONSTRAINT lesson_state_check CHECK (state IN ('draft','under_review','reviewed','published'))");
        DB::statement("ALTER TABLE lesson_revisions ADD CONSTRAINT lesson_review_decision_check CHECK (review_decision IS NULL OR review_decision IN ('APPROVED','REJECTED','RETURNED'))");
        DB::statement("ALTER TABLE enterprise_baseline_revisions ADD CONSTRAINT enterprise_baseline_state_check CHECK (state = 'published')");
        DB::statement("ALTER TABLE improvement_proposals ADD CONSTRAINT improvement_status_check CHECK (status IN ('proposed','accepted','rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('improvement_proposals');
        Schema::dropIfExists('enterprise_baseline_revisions');
        Schema::dropIfExists('curriculum_placements');
        Schema::dropIfExists('lesson_revisions');
        Schema::dropIfExists('knowledge_units');
        Schema::dropIfExists('source_claims');
        Schema::dropIfExists('source_records');
    }
};
