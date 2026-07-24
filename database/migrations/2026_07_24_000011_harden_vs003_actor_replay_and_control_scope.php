<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vs003_containment_proposals', function (Blueprint $table): void {
            // Nullable only for pre-hardening WIP rows. New writes require this in the service.
            $table->uuid('triage_record_id')->nullable();
        });
        Schema::table('vs003_control_revisions', function (Blueprint $table): void {
            $table->uuid('actor_id')->nullable();
            $table->uuid('proposal_id')->nullable();
            $table->uuid('triage_record_id')->nullable();
        });
        Schema::table('vs003_verification_replays', function (Blueprint $table): void {
            $table->uuid('actor_id')->nullable();
            $table->uuid('triage_record_id')->nullable();
        });

        // Best-effort preservation of any Task-009 WIP data created before this
        // migration. Historical rows that cannot be proven remain nullable and
        // are excluded from mastery; they are never silently invented.
        DB::statement(<<<'SQL'
            UPDATE vs003_containment_proposals AS proposal
            SET triage_record_id = triage.id
            FROM vs003_triage_records AS triage
            WHERE proposal.triage_record_id IS NULL
              AND triage.scenario_run_id = proposal.scenario_run_id
              AND triage.actor_id = proposal.actor_id
        SQL);
        DB::statement(<<<'SQL'
            UPDATE vs003_control_revisions
            SET proposal_id = (definition ->> 'proposal_id')::uuid
            WHERE proposal_id IS NULL
              AND jsonb_exists(definition, 'proposal_id')
              AND (definition ->> 'proposal_id') ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'
        SQL);
        DB::statement(<<<'SQL'
            UPDATE vs003_control_revisions AS control
            SET actor_id = proposal.actor_id,
                triage_record_id = proposal.triage_record_id
            FROM vs003_containment_proposals AS proposal
            WHERE control.proposal_id = proposal.id
              AND (control.actor_id IS NULL OR control.triage_record_id IS NULL)
        SQL);
        DB::statement(<<<'SQL'
            UPDATE vs003_verification_replays AS replay
            SET actor_id = run.actor_id,
                triage_record_id = control.triage_record_id
            FROM scenario_runs AS run,
                 vs003_control_revisions AS control
            WHERE replay.verification_run_id = run.id
              AND replay.control_revision_id = control.id
              AND (replay.actor_id IS NULL OR replay.triage_record_id IS NULL)
        SQL);

        Schema::table('vs003_containment_proposals', function (Blueprint $table): void {
            $table->foreign('triage_record_id')->references('id')->on('vs003_triage_records')->restrictOnDelete();
            $table->index(['actor_id', 'scenario_run_id'], 'vs003_proposal_actor_run_idx');
        });
        Schema::table('vs003_control_revisions', function (Blueprint $table): void {
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('proposal_id')->references('id')->on('vs003_containment_proposals')->restrictOnDelete();
            $table->foreign('triage_record_id')->references('id')->on('vs003_triage_records')->restrictOnDelete();
            $table->index(['actor_id', 'remediates_run_id'], 'vs003_control_actor_run_idx');
        });
        Schema::table('vs003_verification_replays', function (Blueprint $table): void {
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('triage_record_id')->references('id')->on('vs003_triage_records')->restrictOnDelete();
            $table->index(['actor_id', 'original_run_id'], 'vs003_replay_actor_run_idx');
        });

        DB::statement('CREATE UNIQUE INDEX vs003_control_proposal_unique ON vs003_control_revisions (proposal_id) WHERE proposal_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX vs003_replay_actor_control_unique ON vs003_verification_replays (actor_id, original_run_id, control_revision_id) WHERE actor_id IS NOT NULL');
        DB::statement(<<<'SQL'
            ALTER TABLE vs003_containment_proposals
            ADD CONSTRAINT vs003_approved_actor_check
            CHECK (
                state <> 'APPROVED'
                OR (approved_by = actor_id AND approved_at IS NOT NULL)
            )
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_vs003_immutable_change()
            RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'VS-003 immutable record cannot be updated or deleted: %', TG_TABLE_NAME;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER vs003_dataset_immutable
            BEFORE UPDATE OR DELETE ON vs003_telemetry_dataset_revisions
            FOR EACH ROW EXECUTE FUNCTION reject_vs003_immutable_change();

            CREATE TRIGGER vs003_triage_immutable
            BEFORE UPDATE OR DELETE ON vs003_triage_records
            FOR EACH ROW EXECUTE FUNCTION reject_vs003_immutable_change();

            CREATE TRIGGER vs003_custody_immutable
            BEFORE UPDATE OR DELETE ON vs003_custody_events
            FOR EACH ROW EXECUTE FUNCTION reject_vs003_immutable_change();

            CREATE TRIGGER vs003_control_immutable
            BEFORE UPDATE OR DELETE ON vs003_control_revisions
            FOR EACH ROW EXECUTE FUNCTION reject_vs003_immutable_change();

            CREATE TRIGGER vs003_replay_immutable
            BEFORE UPDATE OR DELETE ON vs003_verification_replays
            FOR EACH ROW EXECUTE FUNCTION reject_vs003_immutable_change();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS vs003_replay_immutable ON vs003_verification_replays;
            DROP TRIGGER IF EXISTS vs003_control_immutable ON vs003_control_revisions;
            DROP TRIGGER IF EXISTS vs003_custody_immutable ON vs003_custody_events;
            DROP TRIGGER IF EXISTS vs003_triage_immutable ON vs003_triage_records;
            DROP TRIGGER IF EXISTS vs003_dataset_immutable ON vs003_telemetry_dataset_revisions;
            DROP FUNCTION IF EXISTS reject_vs003_immutable_change();
        SQL);
        DB::statement('ALTER TABLE vs003_containment_proposals DROP CONSTRAINT IF EXISTS vs003_approved_actor_check');
        DB::statement('DROP INDEX IF EXISTS vs003_replay_actor_control_unique');
        DB::statement('DROP INDEX IF EXISTS vs003_control_proposal_unique');

        Schema::table('vs003_verification_replays', function (Blueprint $table): void {
            $table->dropIndex('vs003_replay_actor_run_idx');
            $table->dropForeign(['actor_id']);
            $table->dropForeign(['triage_record_id']);
            $table->dropColumn(['actor_id', 'triage_record_id']);
        });
        Schema::table('vs003_control_revisions', function (Blueprint $table): void {
            $table->dropIndex('vs003_control_actor_run_idx');
            $table->dropForeign(['actor_id']);
            $table->dropForeign(['proposal_id']);
            $table->dropForeign(['triage_record_id']);
            $table->dropColumn(['actor_id', 'proposal_id', 'triage_record_id']);
        });
        Schema::table('vs003_containment_proposals', function (Blueprint $table): void {
            $table->dropIndex('vs003_proposal_actor_run_idx');
            $table->dropForeign(['triage_record_id']);
            $table->dropColumn('triage_record_id');
        });
    }
};
