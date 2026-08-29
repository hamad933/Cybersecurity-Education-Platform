<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_effective_review_decisions', function (Blueprint $table): void {
            $table->uuid('evidence_id');
            $table->string('review_scope_key', 160);
            $table->uuid('evidence_revision_id');
            $table->uuid('decision_id');
            $table->string('decision', 40);
            $table->timestampTz('decided_at');
            $table->timestampTz('projected_at');
            $table->primary(['evidence_id', 'review_scope_key'], 'evidence_effective_decision_pk');
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
            $table->foreign('decision_id')->references('id')->on('evidence_review_decisions')->restrictOnDelete();
            $table->index('decision_id', 'evidence_effective_decision_id_idx');
        });

        DB::statement("ALTER TABLE evidence_effective_review_decisions ADD CONSTRAINT evidence_effective_decision_outcome_check CHECK (decision IN ('ACCEPT','ACCEPT_WITH_LIMITATIONS','MORE_EVIDENCE_REQUIRED','REJECT'))");
        DB::statement("UPDATE evidence_portfolios SET grouping = 'MASTERY_JUDGMENT' WHERE grouping = 'MASTERY'");
        DB::statement("ALTER TABLE evidence_portfolios ADD CONSTRAINT evidence_portfolio_grouping_check CHECK (grouping IN ('CAPABILITY','REVIEW_DECISION','EVIDENCE_TYPE','TIME','MASTERY_JUDGMENT','FRESHNESS_STATUS')) NOT VALID");
        DB::statement('ALTER TABLE evidence_portfolios VALIDATE CONSTRAINT evidence_portfolio_grouping_check');

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION cep_validate_evidence_review_assignment()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    request_row evidence_review_requests%ROWTYPE;
BEGIN
    SELECT * INTO request_row
      FROM evidence_review_requests
     WHERE id = NEW.review_request_id;

    IF request_row.id IS NULL
       OR request_row.assigned_reviewer_id IS NULL
       OR request_row.assigned_reviewer_id <> NEW.reviewer_id THEN
        RAISE EXCEPTION 'Evidence Review requires its explicitly assigned reviewer';
    END IF;

    IF request_row.evidence_id <> NEW.evidence_id
       OR request_row.evidence_revision_id <> NEW.evidence_revision_id
       OR request_row.review_scope_key <> NEW.review_scope_key THEN
        RAISE EXCEPTION 'Evidence Review identity must match its governed Review Request';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE TRIGGER evidence_reviews_assignment_validate
BEFORE INSERT ON evidence_reviews
FOR EACH ROW EXECUTE FUNCTION cep_validate_evidence_review_assignment();

CREATE OR REPLACE FUNCTION cep_validate_evidence_review_request_governance()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    IF NEW.assigned_reviewer_id IS NULL THEN
        RAISE EXCEPTION 'Formal Review Request requires an explicitly assigned reviewer';
    END IF;

    IF NOT EXISTS (
        SELECT 1
          FROM evidence_review_scope_items AS item
         WHERE item.review_request_id = NEW.id
    ) THEN
        RAISE EXCEPTION 'Formal Review Request requires canonical Evidence scope items';
    END IF;

    IF EXISTS (
        SELECT 1
          FROM jsonb_array_elements_text(NEW.criterion_refs) AS criterion(value)
         WHERE NOT EXISTS (
             SELECT 1
               FROM evidence_review_scope_items AS item
               INNER JOIN governed_evidence_revisions AS revision
                       ON revision.id = item.evidence_revision_id
              WHERE item.review_request_id = NEW.id
                AND revision.criterion_scope ? criterion.value
         )
    ) THEN
        RAISE EXCEPTION 'Formal Review criterion is outside its canonical Evidence scope';
    END IF;

    IF jsonb_array_length(NEW.criterion_refs) = 0
       AND EXISTS (
           SELECT 1
             FROM evidence_review_scope_items AS item
             INNER JOIN governed_evidence AS evidence
                     ON evidence.id = item.evidence_id
            WHERE item.review_request_id = NEW.id
              AND evidence.governed_purpose = 'FORMAL_CAPABILITY_EVIDENCE'
       ) THEN
        RAISE EXCEPTION 'Formal capability Evidence Review requires criterion scope';
    END IF;

    IF TG_OP = 'UPDATE'
       AND EXISTS (
           SELECT 1
             FROM evidence_reviews AS review
            WHERE review.review_request_id = NEW.id
       )
       AND (
           OLD.assigned_reviewer_id IS DISTINCT FROM NEW.assigned_reviewer_id
           OR OLD.evidence_id IS DISTINCT FROM NEW.evidence_id
           OR OLD.evidence_revision_id IS DISTINCT FROM NEW.evidence_revision_id
           OR OLD.review_scope_key IS DISTINCT FROM NEW.review_scope_key
           OR OLD.criterion_refs IS DISTINCT FROM NEW.criterion_refs
           OR OLD.prior_decision_id IS DISTINCT FROM NEW.prior_decision_id
       ) THEN
        RAISE EXCEPTION 'Started Review Request authority and scope are immutable';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE CONSTRAINT TRIGGER evidence_review_requests_governance_validate
AFTER INSERT OR UPDATE ON evidence_review_requests
DEFERRABLE INITIALLY DEFERRED
FOR EACH ROW EXECUTE FUNCTION cep_validate_evidence_review_request_governance();

CREATE OR REPLACE FUNCTION cep_validate_evidence_review_finding()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    review_row evidence_reviews%ROWTYPE;
BEGIN
    SELECT * INTO review_row
      FROM evidence_reviews
     WHERE id = NEW.review_id;

    IF review_row.id IS NULL OR review_row.reviewer_id <> NEW.recorded_by THEN
        RAISE EXCEPTION 'Evidence Review Finding requires the assigned reviewer';
    END IF;

    IF NOT (review_row.criterion_refs ? NEW.criterion_key) THEN
        RAISE EXCEPTION 'Evidence Review Finding criterion is outside the pinned criterion scope';
    END IF;

    IF EXISTS (
        SELECT 1
          FROM jsonb_array_elements_text(NEW.supporting_evidence_revision_ids) AS supporting(revision_id)
         WHERE NOT EXISTS (
             SELECT 1
               FROM evidence_review_scope_items AS item
              WHERE item.review_request_id = review_row.review_request_id
                AND item.evidence_revision_id::text = supporting.revision_id
         )
    ) THEN
        RAISE EXCEPTION 'Evidence Review Finding support is outside the pinned Evidence scope';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE TRIGGER evidence_review_findings_scope_validate
BEFORE INSERT ON evidence_review_findings
FOR EACH ROW EXECUTE FUNCTION cep_validate_evidence_review_finding();

CREATE OR REPLACE FUNCTION cep_validate_evidence_review_decision_authority()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    review_row evidence_reviews%ROWTYPE;
    request_row evidence_review_requests%ROWTYPE;
BEGIN
    SELECT * INTO review_row
      FROM evidence_reviews
     WHERE id = NEW.review_id;
    SELECT * INTO request_row
      FROM evidence_review_requests
     WHERE id = review_row.review_request_id;

    IF review_row.id IS NULL
       OR review_row.reviewer_id <> NEW.decided_by
       OR review_row.status <> 'READY_FOR_DECISION' THEN
        RAISE EXCEPTION 'Evidence Review Decision requires the assigned reviewer and decision-ready Review';
    END IF;

    IF review_row.review_scope_key <> NEW.review_scope_key
       OR review_row.evidence_id <> NEW.evidence_id
       OR review_row.evidence_revision_id <> NEW.evidence_revision_id
       OR request_row.prior_decision_id IS DISTINCT FROM NEW.supersedes_decision_id THEN
        RAISE EXCEPTION 'Evidence Review Decision authority does not match its governed Review Request';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE TRIGGER evidence_review_decisions_authority_validate
BEFORE INSERT ON evidence_review_decisions
FOR EACH ROW EXECUTE FUNCTION cep_validate_evidence_review_decision_authority();

CREATE OR REPLACE FUNCTION cep_validate_evidence_review_decision_lineage()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    review_row evidence_reviews%ROWTYPE;
    request_row evidence_review_requests%ROWTYPE;
    prior_scope varchar(160);
    current_items uuid[];
    prior_items uuid[];
BEGIN
    SELECT * INTO review_row
      FROM evidence_reviews
     WHERE id = NEW.review_id;
    SELECT * INTO request_row
      FROM evidence_review_requests
     WHERE id = review_row.review_request_id;

    IF review_row.id IS NULL OR review_row.reviewer_id <> NEW.decided_by THEN
        RAISE EXCEPTION 'Evidence Review Decision requires the assigned reviewer';
    END IF;

    IF review_row.review_scope_key <> NEW.review_scope_key
       OR review_row.evidence_id <> NEW.evidence_id
       OR review_row.evidence_revision_id <> NEW.evidence_revision_id
       OR request_row.prior_decision_id IS DISTINCT FROM NEW.supersedes_decision_id THEN
        RAISE EXCEPTION 'Evidence Review Decision lineage does not match its governed Review Request';
    END IF;

    IF EXISTS (
        SELECT 1
          FROM jsonb_array_elements_text(review_row.criterion_refs) AS criterion(value)
         WHERE NOT EXISTS (
             SELECT 1
               FROM evidence_review_findings AS finding
              WHERE finding.review_id = NEW.review_id
                AND finding.criterion_key = criterion.value
         )
    ) THEN
        RAISE EXCEPTION 'Evidence Review Decision requires complete pinned criterion Findings';
    END IF;

    SELECT array_agg(item.evidence_id ORDER BY item.evidence_id)
      INTO current_items
      FROM evidence_review_decision_items AS item
     WHERE item.decision_id = NEW.id;

    IF current_items IS NULL THEN
        RAISE EXCEPTION 'Evidence Review Decision requires canonical Evidence scope items';
    END IF;

    IF NEW.supersedes_decision_id IS NOT NULL THEN
        SELECT decision.review_scope_key
          INTO prior_scope
          FROM evidence_review_decisions AS decision
         WHERE decision.id = NEW.supersedes_decision_id;
        SELECT array_agg(item.evidence_id ORDER BY item.evidence_id)
          INTO prior_items
          FROM evidence_review_decision_items AS item
         WHERE item.decision_id = NEW.supersedes_decision_id;

        IF prior_scope IS NULL OR prior_scope <> NEW.review_scope_key
           OR prior_items IS DISTINCT FROM current_items THEN
            RAISE EXCEPTION 'Superseding Review Decision must preserve exact scope and Evidence membership';
        END IF;
    ELSIF EXISTS (
        SELECT 1
          FROM evidence_review_decisions AS prior
         WHERE prior.id <> NEW.id
           AND prior.review_scope_key = NEW.review_scope_key
           AND NOT EXISTS (
               SELECT 1
                 FROM evidence_review_decisions AS successor
                WHERE successor.supersedes_decision_id = prior.id
           )
           AND (
               SELECT array_agg(item.evidence_id ORDER BY item.evidence_id)
                 FROM evidence_review_decision_items AS item
                WHERE item.decision_id = prior.id
           ) IS NOT DISTINCT FROM current_items
    ) THEN
        RAISE EXCEPTION 'New Review Decision must supersede the current exact-scope lineage tip';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE CONSTRAINT TRIGGER evidence_review_decisions_lineage_validate
AFTER INSERT ON evidence_review_decisions
DEFERRABLE INITIALLY DEFERRED
FOR EACH ROW EXECUTE FUNCTION cep_validate_evidence_review_decision_lineage();

CREATE OR REPLACE FUNCTION cep_validate_effective_review_decision()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    IF NOT EXISTS (
        SELECT 1
          FROM evidence_review_decisions AS decision
          INNER JOIN evidence_review_decision_items AS item
                  ON item.decision_id = decision.id
          INNER JOIN governed_evidence AS evidence
                  ON evidence.id = item.evidence_id
          INNER JOIN governed_evidence_revisions AS revision
                  ON revision.id = item.evidence_revision_id
                 AND revision.evidence_id = evidence.id
         WHERE decision.id = NEW.decision_id
           AND decision.review_scope_key = NEW.review_scope_key
           AND decision.decision = NEW.decision
           AND decision.decided_at = NEW.decided_at
           AND item.evidence_id = NEW.evidence_id
           AND item.evidence_revision_id = NEW.evidence_revision_id
           AND evidence.current_revision_number = revision.revision
    ) THEN
        RAISE EXCEPTION 'Effective Review Decision must reference the exact current scoped Decision item';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE TRIGGER evidence_effective_decisions_validate
BEFORE INSERT OR UPDATE ON evidence_effective_review_decisions
FOR EACH ROW EXECUTE FUNCTION cep_validate_effective_review_decision();
SQL);

        DB::statement(<<<'SQL'
INSERT INTO evidence_effective_review_decisions (
    evidence_id,
    review_scope_key,
    evidence_revision_id,
    decision_id,
    decision,
    decided_at,
    projected_at
)
SELECT DISTINCT ON (item.evidence_id, decision.review_scope_key)
       item.evidence_id,
       decision.review_scope_key,
       item.evidence_revision_id,
       decision.id,
       decision.decision,
       decision.decided_at,
       CURRENT_TIMESTAMP
  FROM evidence_review_decisions AS decision
  INNER JOIN evidence_review_decision_items AS item
          ON item.decision_id = decision.id
  INNER JOIN governed_evidence AS evidence
          ON evidence.id = item.evidence_id
  INNER JOIN governed_evidence_revisions AS revision
          ON revision.id = item.evidence_revision_id
         AND revision.evidence_id = evidence.id
 WHERE evidence.current_revision_number = revision.revision
   AND NOT EXISTS (
       SELECT 1
         FROM evidence_review_decisions AS successor
        WHERE successor.supersedes_decision_id = decision.id
   )
 ORDER BY item.evidence_id, decision.review_scope_key, decision.decided_at DESC, decision.id DESC
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS evidence_effective_decisions_validate ON evidence_effective_review_decisions;
DROP FUNCTION IF EXISTS cep_validate_effective_review_decision();
DROP TRIGGER IF EXISTS evidence_review_decisions_lineage_validate ON evidence_review_decisions;
DROP FUNCTION IF EXISTS cep_validate_evidence_review_decision_lineage();
DROP TRIGGER IF EXISTS evidence_review_decisions_authority_validate ON evidence_review_decisions;
DROP FUNCTION IF EXISTS cep_validate_evidence_review_decision_authority();
DROP TRIGGER IF EXISTS evidence_review_findings_scope_validate ON evidence_review_findings;
DROP FUNCTION IF EXISTS cep_validate_evidence_review_finding();
DROP TRIGGER IF EXISTS evidence_review_requests_governance_validate ON evidence_review_requests;
DROP FUNCTION IF EXISTS cep_validate_evidence_review_request_governance();
DROP TRIGGER IF EXISTS evidence_reviews_assignment_validate ON evidence_reviews;
DROP FUNCTION IF EXISTS cep_validate_evidence_review_assignment();
SQL);

        Schema::dropIfExists('evidence_effective_review_decisions');
        DB::statement('ALTER TABLE evidence_portfolios DROP CONSTRAINT IF EXISTS evidence_portfolio_grouping_check');
        DB::statement("UPDATE evidence_portfolios SET grouping = 'MASTERY' WHERE grouping = 'MASTERY_JUDGMENT'");
    }
};
