<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Simulator\RunResult\RunResultCapability;
use App\Modules\Simulator\RunResult\RunResultVocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

final class RunResultCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private RunResultCapability $capability;
    private string $enterpriseId;
    private string $twinId;
    private string $twinRevId;
    private string $baselineId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->capability = new RunResultCapability();
        
        $this->enterpriseId = (string) Str::uuid();
        DB::table('simulation_enterprises')->insert(['id' => $this->enterpriseId, 'slug' => 'ent-1', 'name_ar' => 'Test', 'definition' => '{}']);
        
        $this->twinId = (string) Str::uuid();
        DB::table('simulation_digital_twins')->insert(['id' => $this->twinId, 'enterprise_id' => $this->enterpriseId, 'slug' => 'twin-1', 'name_ar' => 'Test']);
        
        $this->twinRevId = (string) Str::uuid();
        DB::table('simulation_digital_twin_revisions')->insert(['id' => $this->twinRevId, 'enterprise_id' => $this->enterpriseId, 'digital_twin_id' => $this->twinId, 'revision' => 1, 'status' => 'PUBLISHED', 'topology' => '{}', 'behavior_model' => '{}', 'digest' => 'test']);
        
        $this->baselineId = (string) Str::uuid();
        DB::table('simulation_baselines')->insert(['id' => $this->baselineId, 'enterprise_id' => $this->enterpriseId, 'digital_twin_id' => $this->twinId, 'digital_twin_revision_id' => $this->twinRevId, 'revision' => 1, 'status' => 'PUBLISHED', 'state' => '{}', 'digest' => 'test']);
    }

    private function createCanonicalResultWithBaselineStructure(array $operationsList, array $timeline = [], float $score = null, string $runIdOverride = null): string
    {
        $runId = $runIdOverride ?? (string) Str::uuid();
        DB::table('simulation_runs')->insertOrIgnore([
            'id' => $runId,
            'enterprise_id' => $this->enterpriseId,
            'digital_twin_id' => $this->twinId,
            'digital_twin_revision_id' => $this->twinRevId,
            'baseline_id' => $this->baselineId,
            'run_type' => 'Standalone Lab Run',
            'lifecycle' => 'COMPLETED',
            'execution_policies' => '{}',
            'seed' => 1,
            'runtime_state' => '{}',
            'input_digest' => (string) Str::uuid(),
            'provenance' => 'SIMULATED',
            'source_fixture' => false,
            'created_by' => 'tester'
        ]);

        $payloadArray = ['operations' => $operationsList];
        $sealedPayload = json_encode($payloadArray, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $outcome = 'ACHIEVED';
        $summaryAr = 'test summary';
        $revision = 1;
        $provenance = 'SIMULATED';
        $sourceFixture = false;
        
        $compositePayload = [
            'runId' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'summaryAr' => $summaryAr,
            'sealedPayload' => $payloadArray,
            'timeline' => $timeline,
            'artifacts' => [],
            'revision' => $revision,
            'provenance' => $provenance,
            'sourceFixture' => $sourceFixture,
        ];

        // Match exactly the digest function required by Baseline
        $resultDigest = hash('sha256', $this->capability->canonicalizeJson(json_encode($compositePayload, JSON_THROW_ON_ERROR)));

        $resultId = (string) Str::uuid();
        DB::table('simulation_run_results')->insert([
            'id' => $resultId,
            'run_id' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'summary_ar' => $summaryAr,
            'sealed_payload' => $sealedPayload,
            'replay_timeline' => json_encode($timeline, JSON_THROW_ON_ERROR),
            'artifacts' => '[]',
            'result_revision' => $revision,
            'result_digest' => $resultDigest,
            'provenance' => $provenance,
            'source_fixture' => $sourceFixture,
            'sealed_by' => 'tester',
            'sealed_at' => now(),
        ]);
        
        return $resultId;
    }

    public function test_project_replay_and_aar_derive_from_canonical_history_directly(): void
    {
        $op1Key = 'valid-op-key-01';
        $op2Key = 'valid-op-key-02';
        
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $input2 = ['operation_key' => $op2Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => false];
        
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        $digest2 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input2, JSON_THROW_ON_ERROR)));
        
        $operations = [
            [
                'operation_key' => $op1Key,
                'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                'input' => $input1,
                'input_digest' => $digest1,
                'pre_state_digest' => 'pre1',
                'post_state_digest' => 'post1',
                'actor_id' => 'actor1'
            ],
            [
                'operation_key' => $op2Key,
                'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                'input' => $input2,
                'input_digest' => $digest2,
                'pre_state_digest' => 'post1',
                'post_state_digest' => 'post2',
                'actor_id' => 'actor1'
            ]
        ];
        
        $timeline = [
            [
                'sequence' => 1,
                'event_type' => 'SIMULATION_OPERATION_APPLIED',
                'actor_id' => 'actor1',
                'payload' => [
                    'operation_key' => $op1Key,
                    'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                    'verb' => 'SET_CONTROL_STATE',
                    'target' => 'IDENTITY_MFA',
                    'value' => true,
                    'pre_state_digest' => 'pre1',
                    'post_state_digest' => 'post1'
                ]
            ],
            [
                'sequence' => 2,
                'event_type' => 'SIMULATION_OPERATION_APPLIED',
                'actor_id' => 'actor1',
                'payload' => [
                    'operation_key' => $op2Key,
                    'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                    'verb' => 'SET_CONTROL_STATE',
                    'target' => 'IDENTITY_MFA',
                    'value' => false,
                    'pre_state_digest' => 'post1',
                    'post_state_digest' => 'post2'
                ]
            ]
        ];
        
        $resultId = $this->createCanonicalResultWithBaselineStructure($operations, $timeline);
        
        $revisionId = $this->capability->createResultRevision($resultId, ['outcome' => 'PARTIAL', 'score' => 50.0]);
        
        $replayState = $this->capability->projectReplayState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $replayState);
        
        $aarState = $this->capability->projectAarState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $aarState['final_state']);
        $this->assertSame(2, $aarState['operation_count']);
        
        $this->assertSame('PARTIAL', $aarState['outcome']);
        $this->assertSame('50', $aarState['score']);
    }
    
    public function test_zero_operation_terminal_result_is_accepted_and_projected_truthfully(): void
    {
        // Pass empty operations and empty timeline exactly natively
        $resultId = $this->createCanonicalResultWithBaselineStructure([], [], 25.0);
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $replayState = $this->capability->projectReplayState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $replayState);
        
        $aarState = $this->capability->projectAarState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $aarState['final_state']);
        $this->assertSame(0, $aarState['operation_count']); // Successfully derived exact 0 operations
        
        $this->assertSame('ACHIEVED', $aarState['outcome']);
        $this->assertSame('25', $aarState['score']);
    }
    
    public function test_tampered_zero_operation_terminal_result_with_applied_event_fails(): void
    {
        // Empty operations but timeline asserts an event occurred (inconsistent/tampered)
        $timeline = [
            [
                'sequence' => 1,
                'event_type' => 'SIMULATION_OPERATION_APPLIED',
                'actor_id' => 'actor1',
                'payload' => [
                    'operation_key' => 'missing-op-key',
                ]
            ]
        ];
        $resultId = $this->createCanonicalResultWithBaselineStructure([], $timeline);
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeline references operations but operations list is empty.');
        
        $this->capability->projectReplayState($revisionId);
    }

    public function test_digit_only_operation_key_avoids_coercion(): void
    {
        // 12-digit string
        $op1Key = '123456789012';
        
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        $operations = [
            [
                'operation_key' => $op1Key,
                'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                'input' => $input1,
                'input_digest' => $digest1,
                'pre_state_digest' => 'pre1',
                'post_state_digest' => 'post1',
                'actor_id' => 'actor1'
            ]
        ];
        
        $timeline = [
            [
                'sequence' => 1,
                'event_type' => 'SIMULATION_OPERATION_APPLIED',
                'actor_id' => 'actor1',
                'payload' => [
                    'operation_key' => $op1Key,
                    'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                    'verb' => 'SET_CONTROL_STATE',
                    'target' => 'IDENTITY_MFA',
                    'value' => true,
                    'pre_state_digest' => 'pre1',
                    'post_state_digest' => 'post1'
                ]
            ]
        ];
        
        $resultId = $this->createCanonicalResultWithBaselineStructure($operations, $timeline);
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $replayState = $this->capability->projectReplayState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $replayState);
    }
    
    public function test_valid_120_character_operation_key_is_accepted(): void
    {
        $op1Key = str_repeat('A', 120);
        
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        $operations = [
            [
                'operation_key' => $op1Key,
                'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                'input' => $input1,
                'input_digest' => $digest1,
                'pre_state_digest' => 'pre1',
                'post_state_digest' => 'post1',
                'actor_id' => 'actor1'
            ]
        ];
        
        $timeline = [
            [
                'sequence' => 1,
                'event_type' => 'SIMULATION_OPERATION_APPLIED',
                'actor_id' => 'actor1',
                'payload' => [
                    'operation_key' => $op1Key,
                    'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1,
                    'verb' => 'SET_CONTROL_STATE',
                    'target' => 'IDENTITY_MFA',
                    'value' => true,
                    'pre_state_digest' => 'pre1',
                    'post_state_digest' => 'post1'
                ]
            ]
        ];
        
        $resultId = $this->createCanonicalResultWithBaselineStructure($operations, $timeline);
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $replayState = $this->capability->projectReplayState($revisionId);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $replayState);
    }

    public function test_missing_canonical_history_throws_dependency_required(): void
    {
        $runId = (string) Str::uuid();
        DB::table('simulation_runs')->insert([
            'id' => $runId,
            'enterprise_id' => $this->enterpriseId,
            'digital_twin_id' => $this->twinId,
            'digital_twin_revision_id' => $this->twinRevId,
            'baseline_id' => $this->baselineId,
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
            'sealed_payload' => json_encode(null),
            'replay_timeline' => '{}',
            'artifacts' => '{}',
            'result_revision' => 1,
            'result_digest' => 'x',
            'provenance' => 'SIMULATED',
            'source_fixture' => false,
            'sealed_by' => 'tester',
            'sealed_at' => now(),
        ]);
        
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEPENDENCY_REQUIRED: Sealed canonical history not found.');
        
        $this->capability->projectReplayState($revisionId);
    }
    
    public function test_project_compare_runs_returns_rich_derivatives_across_distinct_runs(): void
    {
        $op1Key = 'valid-op-key-01';
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        $op2Key = 'valid-op-key-02';
        $input2 = ['operation_key' => $op2Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest2 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input2, JSON_THROW_ON_ERROR)));

        $res1 = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input1, 'input_digest' => $digest1, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]]
        );
        
        $res2 = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op2Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input2, 'input_digest' => $digest2, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op2Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]]
        );
        
        $rev1 = $this->capability->createResultRevision($res1, ['outcome' => 'ACHIEVED']);
        $rev2 = $this->capability->createResultRevision($res2, ['outcome' => 'NOT_ACHIEVED', 'score' => 0.0]);
        
        $comparisons = $this->capability->projectCompareRuns([$rev1, $rev2]);
        
        $this->assertCount(2, $comparisons);
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $comparisons[$rev1]['final_state']);
        $this->assertSame('ACHIEVED', $comparisons[$rev1]['outcome']);
        
        $this->assertSame('REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED', $comparisons[$rev2]['final_state']);
        $this->assertSame('NOT_ACHIEVED', $comparisons[$rev2]['outcome']);
        $this->assertSame('0', $comparisons[$rev2]['score']);
    }
    
    public function test_project_compare_rejects_duplicates_and_requires_two_distinct_runs(): void
    {
        $op1Key = 'valid-op-key-01';
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));

        $op3Key = 'valid-op-key-03';
        $input3 = ['operation_key' => $op3Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest3 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input3, JSON_THROW_ON_ERROR)));

        $runIdA = (string) Str::uuid();
        $resultIdA = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input1, 'input_digest' => $digest1, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]],
            null,
            $runIdA
        );
        
        $resultIdA2 = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op3Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input3, 'input_digest' => $digest3, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op3Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]],
            null,
            $runIdA
        );
        
        $runIdB = (string) Str::uuid();
        $resultIdB = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op3Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input3, 'input_digest' => $digest3, 'actor_id' => 'a3', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a3', 'payload' => ['operation_key' => $op3Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]],
            null,
            $runIdB
        );
        
        $revA1 = $this->capability->createResultRevision($resultIdA, ['outcome' => 'ACHIEVED']);
        $revA2 = $this->capability->createResultRevision($resultIdA, ['outcome' => 'PARTIAL'], null, $revA1, 'Corrected outcome');
        $revA_Alternative = $this->capability->createResultRevision($resultIdA2, ['outcome' => 'NOT_ACHIEVED']);
        
        $revB1 = $this->capability->createResultRevision($resultIdB, ['outcome' => 'NOT_ACHIEVED']);
        
        try {
            $this->capability->projectCompareRuns([$revA1]);
            $this->fail('Should reject single revision');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Must compare two or more distinct canonical Results/Runs', $e->getMessage());
        }
        
        try {
            $this->capability->projectCompareRuns([$revA1, $revA1]);
            $this->fail('Should reject duplicate revision');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cannot compare duplicate Run Revisions.', $e->getMessage());
        }
        
        try {
            $this->capability->projectCompareRuns([$revA1, $revA2]);
            $this->fail('Should reject revisions of the same run');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Must compare two or more distinct canonical Results/Runs', $e->getMessage());
        }
        
        // Fails: distinct Results but SAME canonical Run ID A
        try {
            $this->capability->projectCompareRuns([$revA1, $revA_Alternative]);
            $this->fail('Should reject distinct results mapping to same canonical Run');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Must compare two or more distinct canonical Results/Runs', $e->getMessage());
        }
        
        // Fails: two revisions of Run A + one revision of Run B (duplicate canonical result A)
        try {
            $this->capability->projectCompareRuns([$revA1, $revA2, $revB1]);
            $this->fail('Should reject mixed duplicate canonical run IDs');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Must compare two or more distinct canonical Results/Runs', $e->getMessage());
        }
        
        $comparisons = $this->capability->projectCompareRuns([$revA2, $revB1]);
        $this->assertCount(2, $comparisons);
        $this->assertSame('PARTIAL', $comparisons[$revA2]['outcome']);
        $this->assertSame('NOT_ACHIEVED', $comparisons[$revB1]['outcome']);
    }

    public function test_pure_handoff_builder_creates_envelope_without_competing_database_writes_or_fabrication(): void
    {
        $op1Key = 'valid-op-key-01';
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        $resultId = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input1, 'input_digest' => $digest1, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]]
        );
        
        $revisionId = $this->capability->createResultRevision($resultId, []);
        
        $envelope = $this->capability->generateCandidateEvidenceHandoffEnvelope($revisionId, 'READY_FOR_INTAKE');
        
        $this->assertSame($resultId, $envelope['result_id']);
        $this->assertSame(1, $envelope['source_result_revision']);
        $this->assertSame('SIMULATED', $envelope['provenance']);
        $this->assertFalse($envelope['source_fixture']);
        $this->assertSame('RESULTS_HANDOFF_EXISTING_WRITER_WIRING_REQUIRED', $envelope['_integration_contract']);
        
        $this->assertArrayNotHasKey('submitter_identity', $envelope);
        $this->assertNull(DB::table('simulation_candidate_evidence_handoffs')->first());
    }

    public function test_derived_revision_rejects_unsupported_keys_and_values(): void
    {
        $op1Key = 'valid-op-key-01';
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        $resultId = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input1, 'input_digest' => $digest1, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]]
        );
        
        try {
            $this->capability->createResultRevision($resultId, ['fake_key' => 'val']);
            $this->fail('Should reject fake derived key');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Unsupported derived field key: fake_key', $e->getMessage());
        }
        
        try {
            $this->capability->createResultRevision($resultId, ['outcome' => 'MAGIC']);
            $this->fail('Should reject fake outcome');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Unsupported Result outcome: MAGIC', $e->getMessage());
        }
    }

    public function test_operation_with_invalid_string_identities_rejected(): void
    {
        $validKey = 'valid_key-123';
        
        $invalidInputs = [
            // Wrong verb and target constraints inherently independent checks
            ['operation_key' => $validKey, 'verb' => 'WRONG_VERB', 'target' => 'IDENTITY_MFA', 'value' => true],
            ['operation_key' => $validKey, 'verb' => 'SET_CONTROL_STATE', 'target' => 'WRONG_TARGET', 'value' => true],
            
            // Length constraint checks precisely
            ['operation_key' => 'short123456', 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true], // 11 chars
            ['operation_key' => str_repeat('k', 121), 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true], // 121 chars
            ['operation_key' => 'invalid key spaces 12', 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true],
            
            // Inner key missing or mismatched
            ['operation_key' => null, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true],
            ['operation_key' => '', 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true],
            ['operation_key' => [1,2], 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true],
        ];

        foreach ($invalidInputs as $invalidInput) {
            $invalidKey = $invalidInput['operation_key'] ?? $validKey;
            
            $exceptionThrown = false;
            try {
                // If it fails to hash internally due to being non-canonical structure, it might throw earlier,
                // but we primarily want to ensure that regardless of digestion success, validation rejects it.
                $digest = hash('sha256', $this->capability->canonicalizeJson(json_encode($invalidInput, JSON_THROW_ON_ERROR)));
                
                $resultId = $this->createCanonicalResultWithBaselineStructure(
                    [
                        ['operation_key' => $invalidKey, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $invalidInput, 'input_digest' => $digest, 'actor_id' => 'a1']
                    ],
                    [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $invalidKey, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => $invalidInput['verb'] ?? '', 'target' => $invalidInput['target'] ?? '', 'value' => true]]]
                );

                $revId = $this->capability->createResultRevision($resultId, []);

                $this->capability->projectReplayState($revId);
            } catch (InvalidArgumentException $e) {
                $exceptionThrown = true;
                $this->assertMatchesRegularExpression('/must be exactly|Inner operation_key does not match|must be a bounded string|missing required verb\/target/', $e->getMessage());
            }

            $this->assertTrue($exceptionThrown, 'Failed to reject invalid string constraint or verb/target mismatch.');
        }
    }
    
    public function test_multi_hop_revision_preserves_explicit_null_effective_state_without_resurrecting_canonical_values(): void
    {
        $op1Key = 'valid-op-key-01';
        $input1 = ['operation_key' => $op1Key, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true];
        $digest1 = hash('sha256', $this->capability->canonicalizeJson(json_encode($input1, JSON_THROW_ON_ERROR)));
        
        // Canonical starts with outcome=ACHIEVED, score=100.0, summary_ar='test summary'
        $resultId = $this->createCanonicalResultWithBaselineStructure(
            [
                ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'input' => $input1, 'input_digest' => $digest1, 'actor_id' => 'a1', 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']
            ],
            [['sequence' => 1, 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => 'a1', 'payload' => ['operation_key' => $op1Key, 'grammar_version' => RunResultVocabulary::OPERATION_ENGINE_V1, 'verb' => 'SET_CONTROL_STATE', 'target' => 'IDENTITY_MFA', 'value' => true, 'pre_state_digest' => 'pre', 'post_state_digest' => 'post']]],
            100.0 // Set explicit canonical score
        );
        
        // Revision 1 updates score to NULL
        $rev1 = $this->capability->createResultRevision($resultId, ['score' => null]);
        
        $aar1 = $this->capability->projectAarState($rev1);
        $this->assertSame('ACHIEVED', $aar1['outcome']);
        $this->assertNull($aar1['score']); // Effectively null
        $this->assertSame('test summary', $aar1['summary_ar']);
        
        // Revision 2 updates ONLY outcome to PARTIAL, using Rev1 as base. It MUST preserve score=null.
        $rev2 = $this->capability->createResultRevision($resultId, ['outcome' => 'PARTIAL'], null, $rev1, 'Corrected outcome');
        
        $aar2 = $this->capability->projectAarState($rev2);
        $this->assertSame('PARTIAL', $aar2['outcome']);
        $this->assertNull($aar2['score'], 'Score resurrected from canonical data when it should be preserved as null effectively.');
        $this->assertSame('test summary', $aar2['summary_ar']);
    }
}
