<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_candidate_intake_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->unsignedBigInteger('sequence');
            $table->uuid('actor_id');
            $table->string('from_state', 32)->nullable();
            $table->string('to_state', 32);
            $table->text('reason');
            $table->timestampTz('occurred_at');
            $table->char('content_digest', 64);
            $table->timestampTz('created_at');
            $table->unique(['candidate_id', 'sequence'], 'evidence_candidate_intake_event_sequence_unique');
            $table->foreign('candidate_id')->references('id')->on('evidence_candidates')->restrictOnDelete();
        });

        DB::statement(
            "ALTER TABLE evidence_candidate_intake_events
             ADD CONSTRAINT evidence_candidate_intake_event_from_state_check
             CHECK (from_state IS NULL OR from_state IN ('RECEIVED','DRAFT','PREPARED','SUBMITTED_FOR_INTAKE','RETURNED_FOR_CONTEXT','ADMITTED','DECLINED','WITHDRAWN'))",
        );
        DB::statement(
            "ALTER TABLE evidence_candidate_intake_events
             ADD CONSTRAINT evidence_candidate_intake_event_to_state_check
             CHECK (to_state IN ('RECEIVED','DRAFT','PREPARED','SUBMITTED_FOR_INTAKE','RETURNED_FOR_CONTEXT','ADMITTED','DECLINED','WITHDRAWN'))",
        );
        DB::statement(<<<'SQL'
CREATE FUNCTION cep_reject_evidence_candidate_intake_event_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'evidence_candidate_intake_events is append-only';
END;
$$
SQL);
        DB::statement(<<<'SQL'
CREATE TRIGGER evidence_candidate_intake_events_immutable
BEFORE UPDATE OR DELETE ON evidence_candidate_intake_events
FOR EACH ROW
EXECUTE FUNCTION cep_reject_evidence_candidate_intake_event_mutation()
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS evidence_candidate_intake_events_immutable ON evidence_candidate_intake_events');
        DB::statement('DROP FUNCTION IF EXISTS cep_reject_evidence_candidate_intake_event_mutation()');
        Schema::dropIfExists('evidence_candidate_intake_events');
    }
};
