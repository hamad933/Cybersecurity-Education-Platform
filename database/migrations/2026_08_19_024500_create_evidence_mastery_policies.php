<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_mastery_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_actor_id');
            $table->string('target_type', 24)->default('CAPABILITY');
            $table->string('target_id', 100);
            $table->string('name', 180);
            $table->unsignedInteger('current_revision_number')->default(0);
            $table->uuid('published_revision_id')->nullable();
            $table->timestampsTz();
            $table->unique(
                ['owner_actor_id', 'target_type', 'target_id', 'name'],
                'evidence_mastery_policy_identity_unique',
            );
            $table->index(
                ['owner_actor_id', 'target_type', 'target_id'],
                'evidence_mastery_policy_target_idx',
            );
        });

        Schema::create('evidence_mastery_policy_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('policy_id');
            $table->uuid('previous_revision_id')->nullable()->unique();
            $table->unsignedInteger('revision');
            $table->jsonb('required_criteria');
            $table->jsonb('qualifying_review_decisions');
            $table->jsonb('evidence_diversity');
            $table->decimal('minimum_attribution_confidence', 5, 4)->nullable();
            $table->string('conflict_handling', 48)->default('REQUIRE_INCONCLUSIVE');
            $table->jsonb('permitted_limitations');
            $table->unsignedInteger('recency_days')->nullable();
            $table->jsonb('freshness_triggers');
            $table->jsonb('revalidation_conditions');
            $table->text('rationale');
            $table->uuid('authored_by');
            $table->uuid('published_by')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->char('content_digest', 64);
            $table->timestampsTz();
            $table->unique(['policy_id', 'revision'], 'evidence_mastery_policy_revision_unique');
            $table->foreign('policy_id')
                ->references('id')
                ->on('evidence_mastery_policies')
                ->restrictOnDelete();
            $table->index(['policy_id', 'published_at'], 'evidence_mastery_policy_publish_idx');
        });

        Schema::table('evidence_mastery_policy_revisions', function (Blueprint $table): void {
            $table->foreign('previous_revision_id')
                ->references('id')
                ->on('evidence_mastery_policy_revisions')
                ->restrictOnDelete();
        });

        Schema::table('evidence_mastery_policies', function (Blueprint $table): void {
            $table->foreign('published_revision_id')
                ->references('id')
                ->on('evidence_mastery_policy_revisions')
                ->restrictOnDelete();
        });

        DB::statement("ALTER TABLE evidence_mastery_policies ADD CONSTRAINT evidence_mastery_policy_target_type_check CHECK (target_type = 'CAPABILITY')");
        DB::statement("ALTER TABLE evidence_mastery_policy_revisions ADD CONSTRAINT evidence_mastery_policy_conflict_check CHECK (conflict_handling IN ('REQUIRE_INCONCLUSIVE','REQUIRE_MANUAL_REVIEW'))");
        DB::statement('ALTER TABLE evidence_mastery_policy_revisions ADD CONSTRAINT evidence_mastery_policy_recency_check CHECK (recency_days IS NULL OR recency_days > 0)');
        DB::statement('ALTER TABLE evidence_mastery_policy_revisions ADD CONSTRAINT evidence_mastery_policy_attribution_check CHECK (minimum_attribution_confidence IS NULL OR (minimum_attribution_confidence >= 0 AND minimum_attribution_confidence <= 1))');

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION cep_reject_published_mastery_policy_revision()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    IF OLD.published_at IS NOT NULL THEN
        RAISE EXCEPTION 'published Mastery Policy Revision is immutable' USING ERRCODE = '55000';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE TRIGGER evidence_mastery_policy_revisions_immutable
BEFORE UPDATE OR DELETE ON evidence_mastery_policy_revisions
FOR EACH ROW EXECUTE FUNCTION cep_reject_published_mastery_policy_revision();
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS evidence_mastery_policy_revisions_immutable ON evidence_mastery_policy_revisions;
DROP FUNCTION IF EXISTS cep_reject_published_mastery_policy_revision();
SQL);

        Schema::table('evidence_mastery_policies', function (Blueprint $table): void {
            $table->dropForeign(['published_revision_id']);
        });
        Schema::dropIfExists('evidence_mastery_policy_revisions');
        Schema::dropIfExists('evidence_mastery_policies');
    }
};
