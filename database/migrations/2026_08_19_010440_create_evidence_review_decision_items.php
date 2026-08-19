<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_review_decision_items', function (Blueprint $table): void {
            $table->uuid('decision_id');
            $table->uuid('evidence_id');
            $table->uuid('evidence_revision_id');
            $table->unsignedSmallInteger('ordinal');
            $table->timestampTz('created_at');
            $table->primary(['decision_id', 'evidence_id'], 'evidence_review_decision_item_pk');
            $table->unique(['decision_id', 'evidence_revision_id'], 'evidence_review_decision_revision_unique');
            $table->unique(['decision_id', 'ordinal'], 'evidence_review_decision_ordinal_unique');
            $table->foreign('decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION cep_validate_evidence_review_decision_item()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM evidence_review_decisions AS decision
        INNER JOIN evidence_reviews AS review ON review.id = decision.review_id
        INNER JOIN evidence_review_scope_items AS scope
            ON scope.review_request_id = review.review_request_id
        WHERE decision.id = NEW.decision_id
          AND scope.evidence_id = NEW.evidence_id
          AND scope.evidence_revision_id = NEW.evidence_revision_id
    ) THEN
        RAISE EXCEPTION 'Review Decision item must be an exact item from the formal Review scope';
    END IF;

    RETURN NEW;
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_review_decision_items_validate
BEFORE INSERT ON evidence_review_decision_items
FOR EACH ROW
EXECUTE FUNCTION cep_validate_evidence_review_decision_item()
SQL);
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION cep_reject_evidence_review_decision_item_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'evidence_review_decision_items is immutable';
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_review_decision_items_immutable
BEFORE UPDATE OR DELETE ON evidence_review_decision_items
FOR EACH ROW
EXECUTE FUNCTION cep_reject_evidence_review_decision_item_mutation()
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS evidence_review_decision_items_immutable ON evidence_review_decision_items');
        DB::statement('DROP FUNCTION IF EXISTS cep_reject_evidence_review_decision_item_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS evidence_review_decision_items_validate ON evidence_review_decision_items');
        DB::statement('DROP FUNCTION IF EXISTS cep_validate_evidence_review_decision_item()');
        Schema::dropIfExists('evidence_review_decision_items');
    }
};
