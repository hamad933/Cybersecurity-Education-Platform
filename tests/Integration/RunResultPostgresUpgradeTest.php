<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class RunResultPostgresUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private string $enterpriseId;
    private string $twinId;
    private string $twinRevId;
    private string $baselineId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enterpriseId = (string) Str::uuid();
        DB::table('simulation_enterprises')->insert(['id' => $this->enterpriseId, 'slug' => 'ent-1', 'name_ar' => 'Test', 'definition' => '{}']);

        $this->twinId = (string) Str::uuid();
        DB::table('simulation_digital_twins')->insert(['id' => $this->twinId, 'enterprise_id' => $this->enterpriseId, 'slug' => 'twin-1', 'name_ar' => 'Test']);

        $this->twinRevId = (string) Str::uuid();
        DB::table('simulation_digital_twin_revisions')->insert(['id' => $this->twinRevId, 'enterprise_id' => $this->enterpriseId, 'digital_twin_id' => $this->twinId, 'revision' => 1, 'status' => 'PUBLISHED', 'topology' => '{}', 'behavior_model' => '{}', 'digest' => 'test']);

        $this->baselineId = (string) Str::uuid();
        DB::table('simulation_baselines')->insert(['id' => $this->baselineId, 'enterprise_id' => $this->enterpriseId, 'digital_twin_id' => $this->twinId, 'digital_twin_revision_id' => $this->twinRevId, 'revision' => 1, 'status' => 'PUBLISHED', 'state' => '{}', 'digest' => 'test']);
    }

    private function createCanonicalResult(): string
    {
        $runId = (string) Str::uuid();

        $labId = (string) Str::uuid();
        DB::table('simulation_lab_definitions')->insertOrIgnore([
            'id' => $labId,
            'enterprise_id' => $this->enterpriseId,
            'baseline_id' => $this->baselineId,
            'slug' => 'test-lab-' . Str::random(6),
            'title_ar' => 'Test Lab',
            'revision' => 1,
            'status' => 'PUBLISHED',
            'configuration' => '{}',
            'validation' => '{}',
            'digest' => 'lab-digest-' . Str::random(6),
            'created_by' => 'tester',
        ]);

        DB::table('simulation_runs')->insert([
            'id' => $runId,
            'enterprise_id' => $this->enterpriseId,
            'digital_twin_id' => $this->twinId,
            'digital_twin_revision_id' => $this->twinRevId,
            'baseline_id' => $this->baselineId,
            'scenario_definition_id' => null,
            'standalone_lab_definition_id' => $labId,
            'run_type' => 'Standalone Lab Run',
            'lifecycle' => 'COMPLETED',
            'execution_policies' => '{}',
            'seed' => 1,
            'runtime_state' => '{}',
            'input_digest' => (string) Str::uuid(),
            'provenance' => 'SIMULATED',
            'created_by' => 'tester'
        ]);

        $resultId = (string) Str::uuid();
        DB::table('simulation_run_results')->insert([
            'id' => $resultId,
            'run_id' => $runId,
            'outcome' => 'ACHIEVED',
            'summary_ar' => 'test',
            'sealed_payload' => '{}',
            'replay_timeline' => '{}',
            'artifacts' => '{}',
            'result_revision' => 1,
            'result_digest' => (string) Str::uuid(),
            'provenance' => 'SIMULATED',
            'sealed_by' => 'tester',
            'sealed_at' => now(),
        ]);

        return $resultId;
    }

    public function test_rejects_duplicate_initial_revisions_via_partial_index(): void
    {
        $resultId = $this->createCanonicalResult();
        $digest = 'test-digest-initial';

        DB::table('simulation_run_result_revisions')->insert([
            'id' => (string) Str::uuid(),
            'result_id' => $resultId,
            'revision_digest' => $digest,
            'base_revision_id' => null,
        ]);

        $exceptionThrown = false;
        try {
            DB::table('simulation_run_result_revisions')->insert([
                'id' => (string) Str::uuid(),
                'result_id' => $resultId,
                'revision_digest' => $digest,
                'base_revision_id' => null,
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('sim_run_res_rev_idem_initial_idx', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected QueryException on duplicate initial revision insertion.');
    }

    public function test_rejects_duplicate_superseding_revisions_via_partial_index(): void
    {
        $resultId = $this->createCanonicalResult();
        $digest = 'test-digest-super';

        // F3: Fix PostgreSQL Partial-Index test accurately
        // Construct valid base row natively mapping the base_revision_id without foreign key bounds failing
        $baseRevId = (string) Str::uuid();
        DB::table('simulation_run_result_revisions')->insert([
            'id' => $baseRevId,
            'result_id' => $resultId,
            'revision_digest' => 'base-digest',
            'base_revision_id' => null, // Legitimate base
        ]);

        // Legitimate superseding insertion natively
        DB::table('simulation_run_result_revisions')->insert([
            'id' => (string) Str::uuid(),
            'result_id' => $resultId,
            'revision_digest' => $digest,
            'base_revision_id' => $baseRevId,
        ]);

        // Attempt duplicate duplicate
        $exceptionThrown = false;
        try {
            DB::table('simulation_run_result_revisions')->insert([
                'id' => (string) Str::uuid(),
                'result_id' => $resultId,
                'revision_digest' => $digest,
                'base_revision_id' => $baseRevId,
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('sim_run_res_rev_idem_super_idx', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected QueryException on duplicate superseding revision insertion.');
    }

    public function test_rejects_wrong_result_dangling_base_revision_id_via_foreign_key_constraint(): void
    {
        $resultId1 = $this->createCanonicalResult();
        $resultId2 = $this->createCanonicalResult();

        $baseRevId = (string) Str::uuid();
        DB::table('simulation_run_result_revisions')->insert([
            'id' => $baseRevId,
            'result_id' => $resultId1,
            'revision_digest' => 'base-digest',
            'base_revision_id' => null,
        ]);

        $exceptionThrown = false;
        try {
            DB::table('simulation_run_result_revisions')->insert([
                'id' => (string) Str::uuid(),
                'result_id' => $resultId2,
                'revision_digest' => 'test-digest-super-dangling',
                'base_revision_id' => $baseRevId, // Fails composite self-FK because result_id2 != result_id1
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('simulation_run_result_revisions_result_id_base_revision_id_foreign', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected QueryException on wrong-result self-FK reference.');
    }

    public function test_rejects_dangling_base_revision_id_via_foreign_key_constraint(): void
    {
        $resultId = $this->createCanonicalResult();
        $digest = 'test-digest-super-dangling';
        $danglingBaseRevId = (string) Str::uuid();

        $exceptionThrown = false;
        try {
            DB::table('simulation_run_result_revisions')->insert([
                'id' => (string) Str::uuid(),
                'result_id' => $resultId,
                'revision_digest' => $digest,
                'base_revision_id' => $danglingBaseRevId, // Fails self-FK dynamically
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('simulation_run_result_revisions_result_id_base_revision_id_foreign', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected QueryException on dangling base_revision_id.');
    }

    public function test_rejects_direct_update_and_delete_on_run_result_revisions(): void
    {
        $resultId = $this->createCanonicalResult();
        $id = (string) Str::uuid();
        DB::table('simulation_run_result_revisions')->insert([
            'id' => $id,
            'result_id' => $resultId,
            'revision_digest' => 'test-digest',
        ]);

        // Use separate independent PostgreSQL connections for UPDATE and DELETE tests,
        // because PG will abort the current transaction if an exception occurs inside of it.
        // We use PDO to ensure a fresh, independent connection outside Laravel's transaction state.

        $dsn = 'pgsql:host=' . config('database.connections.pgsql.host') . ';port=' . config('database.connections.pgsql.port') . ';dbname=' . config('database.connections.pgsql.database');

        $independentPdo = new \PDO($dsn, config('database.connections.pgsql.username'), config('database.connections.pgsql.password'));
        $independentPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $updateExceptionThrown = false;
        try {
            $stmt = $independentPdo->prepare('UPDATE simulation_run_result_revisions SET score = 5 WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            $this->assertStringContainsString('Immutable record', $e->getMessage());
            $updateExceptionThrown = true;
        }
        $this->assertTrue($updateExceptionThrown, 'Expected PDOException on UPDATE.');

        $deleteExceptionThrown = false;
        try {
            $stmt = $independentPdo->prepare('DELETE FROM simulation_run_result_revisions WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            $this->assertStringContainsString('Immutable record', $e->getMessage());
            $deleteExceptionThrown = true;
        }
        $this->assertTrue($deleteExceptionThrown, 'Expected PDOException on DELETE.');
    }

    public function test_migration_down_fails_closed_if_governed_data_exists(): void
    {
        $resultId = $this->createCanonicalResult();
        DB::table('simulation_run_result_revisions')->insert([
            'id' => (string) Str::uuid(),
            'result_id' => $resultId,
            'revision_digest' => 'test-digest',
        ]);

        $migration = require database_path('migrations/2026_08_19_020000_add_simulation_run_result_revisions.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot rollback migration: simulation_run_result_revisions contains governed data');

        $migration->down();
    }
}
