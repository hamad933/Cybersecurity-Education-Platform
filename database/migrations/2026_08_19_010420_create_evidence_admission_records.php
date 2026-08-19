<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_admission_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->unique();
            $table->uuid('evidence_id')->unique();
            $table->uuid('evidence_revision_id')->unique();
            $table->uuid('admitted_by');
            $table->timestampTz('admitted_at');
            $table->char('provenance_digest', 64);
            $table->char('content_digest', 64);
            $table->timestampTz('created_at');
            $table->foreign('candidate_id')->references('id')->on('evidence_candidates')->restrictOnDelete();
            $table->foreign('evidence_id')->references('id')->on('governed_evidence')->restrictOnDelete();
            $table->foreign('evidence_revision_id')->references('id')->on('governed_evidence_revisions')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
CREATE FUNCTION cep_validate_evidence_admission_record()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM governed_evidence AS evidence
        INNER JOIN governed_evidence_revisions AS revision
            ON revision.evidence_id = evidence.id
        INNER JOIN evidence_candidates AS candidate
            ON candidate.id = evidence.candidate_id
        WHERE evidence.id = NEW.evidence_id
          AND evidence.candidate_id = NEW.candidate_id
          AND revision.id = NEW.evidence_revision_id
          AND candidate.state = 'ADMITTED'
          AND candidate.admitted_evidence_id = evidence.id
    ) THEN
        RAISE EXCEPTION 'Evidence admission provenance does not match the canonical Candidate/Evidence/Revision chain';
    END IF;

    RETURN NEW;
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_admission_records_validate
BEFORE INSERT ON evidence_admission_records
FOR EACH ROW
EXECUTE FUNCTION cep_validate_evidence_admission_record()
SQL);
        DB::statement(<<<'SQL'
CREATE FUNCTION cep_reject_evidence_admission_record_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'evidence_admission_records is immutable';
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_admission_records_immutable
BEFORE UPDATE OR DELETE ON evidence_admission_records
FOR EACH ROW
EXECUTE FUNCTION cep_reject_evidence_admission_record_mutation()
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS evidence_admission_records_immutable ON evidence_admission_records');
        DB::statement('DROP FUNCTION IF EXISTS cep_reject_evidence_admission_record_mutation()');
        DB::statement('DROP TRIGGER IF EXISTS evidence_admission_records_validate ON evidence_admission_records');
        DB::statement('DROP FUNCTION IF EXISTS cep_validate_evidence_admission_record()');
        Schema::dropIfExists('evidence_admission_records');
    }
};
