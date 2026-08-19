<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_review_scope_items', function (Blueprint $table): void {
            $table->uuid('review_request_id');
            $table->uuid('evidence_id');
            $table->uuid('evidence_revision_id');
            $table->unsignedSmallInteger('ordinal');
            $table->uuid('added_by');
            $table->timestampTz('created_at');
            $table->primary(['review_request_id', 'evidence_id'], 'evidence_review_scope_item_pk');
            $table->unique(['review_request_id', 'evidence_revision_id'], 'evidence_review_scope_revision_unique');
            $table->unique(['review_request_id', 'ordinal'], 'evidence_review_scope_ordinal_unique');
            $table->foreign('review_request_id')->references('id')->on('evidence_review_requests')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
CREATE FUNCTION cep_validate_evidence_review_scope_item()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    request_subject uuid;
    item_subject uuid;
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM governed_evidence AS evidence
        INNER JOIN governed_evidence_revisions AS revision
            ON revision.evidence_id = evidence.id
        WHERE evidence.id = NEW.evidence_id
          AND revision.id = NEW.evidence_revision_id
          AND evidence.lifecycle_state = 'ACTIVE'
          AND evidence.current_revision_number = revision.revision
    ) THEN
        RAISE EXCEPTION 'Review scope item must pin the current Revision of ACTIVE canonical Evidence';
    END IF;

    SELECT evidence.subject_actor_id
      INTO request_subject
      FROM evidence_review_requests AS request
      INNER JOIN governed_evidence AS evidence ON evidence.id = request.evidence_id
     WHERE request.id = NEW.review_request_id;

    SELECT subject_actor_id
      INTO item_subject
      FROM governed_evidence
     WHERE id = NEW.evidence_id;

    IF request_subject IS NULL OR item_subject IS NULL OR request_subject <> item_subject THEN
        RAISE EXCEPTION 'Formal Review scope cannot cross Evidence subject boundaries';
    END IF;

    RETURN NEW;
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_review_scope_items_validate
BEFORE INSERT ON evidence_review_scope_items
FOR EACH ROW
EXECUTE FUNCTION cep_validate_evidence_review_scope_item()
SQL);
        DB::statement(<<<'SQL'
CREATE FUNCTION cep_reject_evidence_review_scope_item_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'evidence_review_scope_items is immutable';
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_review_scope_items_immutable
BEFORE UPDATE OR DELETE ON evidence_review_scope_items
FOR EACH ROW
EXECUTE FUNCTION cep_reject_evidence_review_scope_item_mutation()
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS evidence_review_scope_items_immutable ON evidence_review_scope_items');
        DB::statement('DROP FUNCTION IF EXISTS cep_reject_evidence_review_scope_item_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS evidence_review_scope_items_validate ON evidence_review_scope_items');
        DB::statement('DROP FUNCTION IF EXISTS cep_validate_evidence_review_scope_item()');
        Schema::dropIfExists('evidence_review_scope_items');
    }
};
