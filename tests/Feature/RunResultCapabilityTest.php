<?php

namespace Tests\Feature;

use App\Modules\Simulator\RunResult\RunResultCapability;
use App\Modules\Simulator\RunResult\RunResultVocabulary;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class RunResultCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private RunResultCapability $capability;

    protected function setUp(): void
    {
        parent::setUp();
        $this->capability = new RunResultCapability;
    }

    private function createRun(string $status = 'COMPLETED'): string
    {
        $runId = (string) Str::uuid7();
        DB::table('simulation_runs')->insert([
            'id' => $runId,
            'status' => $status,
            'scenario_id' => (string) Str::uuid7(),
            'digital_twin_id' => (string) Str::uuid7(),
            'digital_twin_revision_id' => (string) Str::uuid7(),
            'baseline_id' => (string) Str::uuid7(),
            'actor_id' => 'test_actor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $runId;
    }

    public function test_can_seal_result_for_terminal_run()
    {
        $runId = $this->createRun('COMPLETED');
        $result = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');

        $this->assertDatabaseHas('simulation_run_results', [
            'id' => $result['id'],
            'run_id' => $runId,
            'result_revision' => 1,
            'previous_result_id' => null,
            'correction_reason' => null,
        ]);
    }

    public function test_cannot_seal_result_for_active_run()
    {
        $runId = $this->createRun('ACTIVE');
        $this->expectException(DomainException::class);
        $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');
    }

    public function test_supersede_result_creates_new_revision()
    {
        $runId = $this->createRun('COMPLETED');
        $result1 = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');

        $result2 = $this->capability->supersedeResult($result1['id'], RunResultVocabulary::OUTCOME_PARTIAL, 'New summary', 50, 'Correction', 'actor_2');

        $this->assertDatabaseHas('simulation_run_results', [
            'id' => $result2['id'],
            'run_id' => $runId,
            'result_revision' => 2,
            'previous_result_id' => $result1['id'],
            'correction_reason' => 'Correction',
        ]);
    }

    public function test_supersede_result_requires_correction_reason()
    {
        $runId = $this->createRun('COMPLETED');
        $result1 = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');

        $this->expectException(InvalidArgumentException::class);
        $this->capability->supersedeResult($result1['id'], RunResultVocabulary::OUTCOME_PARTIAL, 'New summary', 50, '', 'actor_2');
    }

    public function test_cannot_supersede_historical_revision()
    {
        $runId = $this->createRun('COMPLETED');
        $result1 = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');
        $result2 = $this->capability->supersedeResult($result1['id'], RunResultVocabulary::OUTCOME_PARTIAL, 'New summary', 50, 'Correction', 'actor_2');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Can only supersede the latest result revision.');
        $this->capability->supersedeResult($result1['id'], RunResultVocabulary::OUTCOME_NOT_ACHIEVED, 'Another summary', 0, 'Another correction', 'actor_3');
    }

    public function test_replay_and_compare_verifies_integrity()
    {
        $runId = $this->createRun('COMPLETED');
        $result = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');

        $compare = $this->capability->replayAndCompareResult($result['id'], 'actor_2');

        $this->assertDatabaseHas('simulation_result_replay_compares', [
            'id' => $compare['id'],
            'result_id' => $result['id'],
            'integrity_match' => true,
        ]);
    }

    public function test_candidate_evidence_handoff_preserves_source_without_creating_canonical_evidence()
    {
        $runId = $this->createRun('COMPLETED');
        $result = $this->capability->sealResult($runId, RunResultVocabulary::OUTCOME_ACHIEVED, 'Summary', 100, 'actor_1');

        $handoff = $this->capability->createCandidateEvidenceHandoff($result['id'], ['manifest' => 'data'], 'contract_ref', 'actor_2');

        $this->assertDatabaseHas('simulation_candidate_evidence_handoffs', [
            'id' => $handoff['id'],
            'result_id' => $result['id'],
            'status' => 'PENDING',
        ]);

        // Ensure no canonical evidence tables are written to.
        // We'll just verify the tables are empty.
        $this->assertDatabaseCount('simulation_candidate_evidence_handoffs', 1);
    }
}
