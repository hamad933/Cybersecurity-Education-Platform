<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('simulation_run_result_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Canonical Binding to sealed Result lineage
            $table->uuid('result_id')->index();
            $table->foreign('result_id')->references('id')->on('simulation_run_results')->restrictOnDelete();

            // Contract allowed nullability for actor identity, but max 120
            $table->string('actor_identity', 120)->nullable();

            // Effective Derived Fields (Runtime Truth is NOT replicated here)
            $table->string('outcome', 32)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('summary_ar')->nullable();

            // Correction Lineage
            $table->string('revision_digest', 64)->index();
            $table->uuid('base_revision_id')->nullable()->index();
            $table->text('correction_reason')->nullable(); // Required if base_revision_id is set

            $table->timestamp('created_at')->useCurrent();

            // Unique constraint must be declared before creating a composite foreign key referencing it
            $table->unique(['result_id', 'id'], 'sim_run_res_rev_result_id_id_unique');

            // Nullable composite self-FK referencing same-result lineage
            $table->foreign(['result_id', 'base_revision_id'])
                  ->references(['result_id', 'id'])
                  ->on('simulation_run_result_revisions')
                  ->restrictOnDelete();
        });

        // Postgres-specific partial unique indexes for race-safe idempotency handling NULL values distinctively
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared("
                CREATE UNIQUE INDEX sim_run_res_rev_idem_initial_idx
                ON simulation_run_result_revisions (result_id, revision_digest)
                WHERE base_revision_id IS NULL;

                CREATE UNIQUE INDEX sim_run_res_rev_idem_super_idx
                ON simulation_run_result_revisions (result_id, revision_digest, base_revision_id)
                WHERE base_revision_id IS NOT NULL;
            ");
        } else {
            Schema::table('simulation_run_result_revisions', function (Blueprint $table) {
                $table->unique(['result_id', 'revision_digest', 'base_revision_id'], 'sim_run_res_rev_idem_idx');
            });
        }

        // Postgres Triggers to reject direct UPDATE and DELETE on run result revisions
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION cep_reject_update_delete_revisions()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'Immutable record: direct UPDATE and DELETE operations are rejected.';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER trg_no_update_run_result_revisions
                BEFORE UPDATE OR DELETE ON simulation_run_result_revisions
                FOR EACH ROW EXECUTE FUNCTION cep_reject_update_delete_revisions();
            ");
        }
    }

    public function down(): void
    {
        // Fail-closed migration rollback safety (R7)
        if (DB::table('simulation_run_result_revisions')->exists()) {
            throw new \RuntimeException('Cannot rollback migration: simulation_run_result_revisions contains governed data. Manual intervention required to prevent data loss.');
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared("
                DROP TRIGGER IF EXISTS trg_no_update_run_result_revisions ON simulation_run_result_revisions;
                DROP FUNCTION IF EXISTS cep_reject_update_delete_revisions();
                DROP INDEX IF EXISTS sim_run_res_rev_idem_super_idx;
                DROP INDEX IF EXISTS sim_run_res_rev_idem_initial_idx;
            ");
        }

        Schema::table('simulation_run_result_revisions', function (Blueprint $table) {
            $table->dropForeign(['result_id', 'base_revision_id']);
            $table->dropUnique('sim_run_res_rev_result_id_id_unique');
        });

        Schema::dropIfExists('simulation_run_result_revisions');
    }
};
