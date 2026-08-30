<?php

namespace Tests\Feature\Learning;

use App\Modules\Learning\Application\AssessmentService;
use App\Modules\Learning\Domain\AssessmentResultDto;
use App\Modules\Learning\Models\AssessmentAttempt;
use App\Modules\Learning\Models\AssessmentDefinition;
use App\Modules\Learning\Models\AssessmentResult;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AssessmentDomainTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssessmentService();
    }

    public function test_definition_digest_is_canonical_and_stable_under_key_reordering(): void
    {
        $def1 = $this->service->createDefinition(
            'ASSESS-001', 'CAP-001', 'KU-001',
            ['expected_answers' => ['b' => 2, 'a' => 1]]
        );
        $def2 = $this->service->createDefinition(
            'ASSESS-002', 'CAP-001', 'KU-001',
            ['expected_answers' => ['a' => 1, 'b' => 2]]
        );

        $this->assertEquals($def1->digest, $def2->digest);
    }

    public function test_definition_is_immutable_app_and_db_level(): void
    {
        $def = $this->service->createDefinition('ASSESS-001', 'CAP-001', 'KU-001', ['expected_answers' => ['a' => 1]]);
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Assessment definitions are immutable');
        $def->update(['capability_id' => 'CAP-NEW']);
    }

    public function test_definition_db_level_update_rejection(): void
    {
        $def = $this->service->createDefinition('ASSESS-001', 'CAP-001', 'KU-001', ['expected_answers' => ['a' => 1]]);
        
        $this->expectException(QueryException::class);
        \Illuminate\Support\Facades\DB::table('assessment_definitions')
            ->where('id', $def->id)
            ->update(['capability_id' => 'CAP-NEW']);
    }

    public function test_definition_db_level_delete_rejection(): void
    {
        $def = $this->service->createDefinition('ASSESS-001', 'CAP-001', 'KU-001', ['expected_answers' => ['a' => 1]]);
        
        $this->expectException(QueryException::class);
        \Illuminate\Support\Facades\DB::table('assessment_definitions')->where('id', $def->id)->delete();
    }

    public function test_attempt_binds_exact_definition_and_lifecycle(): void
    {
        $def = $this->service->createDefinition('ASSESS-002', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        
        $actorId = (string) Str::uuid7();
        $attempt = $this->service->startAttempt((string)$def->id, $actorId);
        
        $this->assertEquals('in_progress', $attempt->status);
        $this->assertEquals($def->id, $attempt->assessment_definition_id);
        
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        $this->assertEquals('submitted', $attempt->status);
    }

    public function test_terminal_attempt_cannot_be_mutated(): void
    {
        $def = $this->service->createDefinition('ASSESS-002', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        
        $this->expectException(LogicException::class);
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
    }

    public function test_terminal_attempt_db_level_update_rejection(): void
    {
        $def = $this->service->createDefinition('ASSESS-002', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        $this->service->submitAttempt($attempt, ['q1' => 'A']);

        $this->expectException(QueryException::class);
        \Illuminate\Support\Facades\DB::table('assessment_attempts')->where('id', $attempt->id)->update(['status' => 'in_progress']);
    }

    public function test_concurrent_submission_fails(): void
    {
        $def = $this->service->createDefinition('ASSESS-002', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        // First submission succeeds
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        
        // Second submission attempt with stale object fails concurrently via DB update constraint
        $staleAttempt = AssessmentAttempt::query()->find($attempt->id);
        if ($staleAttempt) {
            $staleAttempt->status = 'in_progress'; // Artificial stale state
            $this->expectException(LogicException::class);
            $this->service->submitAttempt($staleAttempt, ['q1' => 'A']);
        }
    }

    public function test_fail_closed_validation_missing_expected_answers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition is malformed or missing a valid associative 'expected_answers' map.");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['other' => 'data']);
    }

    public function test_fail_closed_validation_empty_expected_answers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition is malformed or missing a valid associative 'expected_answers' map.");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => []]);
    }

    public function test_fail_closed_validation_list_expected_answers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition is malformed or missing a valid associative 'expected_answers' map.");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => ['A', 'B']]);
    }

    public function test_fail_closed_validation_unsupported_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition 'expected_answers' values must be scalar (string, int, bool).");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => ['nested' => 'val']]]);
    }
    
    public function test_fail_closed_validation_empty_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition 'expected_answers' keys must be non-empty strings.");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => ['' => 'val']]);
    }

    public function test_fail_closed_validation_overlong_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition 'expected_answers' keys must not exceed 100 characters.");
        
        $longKey = str_repeat('a', 101);
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => [$longKey => 'val']]);
    }

    public function test_fail_closed_validation_invalid_character_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment definition 'expected_answers' keys must contain only alphanumeric characters, dashes, and underscores.");
        
        $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => ['invalid key!' => 'val']]);
    }

    public function test_evaluate_rejects_corrupt_legacy_definition_with_same_contract(): void
    {
        $defId = (string) Str::uuid7();
        $attemptId = (string) Str::uuid7();
        
        \Illuminate\Support\Facades\DB::table('assessment_definitions')->insert([
            'id' => $defId,
            'assessment_id' => 'ASSESS-CORRUPT',
            'revision' => 1,
            'capability_id' => 'CAP-001',
            'knowledge_unit_id' => 'KU-001',
            'definition' => json_encode(['corrupt' => 'data']), // Missing expected_answers
            'digest' => 'fake_digest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('assessment_attempts')->insert([
            'id' => $attemptId,
            'assessment_definition_id' => $defId,
            'actor_id' => (string) Str::uuid7(),
            'status' => 'submitted',
            'answers' => json_encode(['q1' => 'A']),
            'started_at' => now(),
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $attempt = AssessmentAttempt::query()->find($attemptId);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Assessment definition is malformed and unscoreable: Assessment definition is malformed or missing a valid associative 'expected_answers' map.");
        
        $this->service->evaluateAttempt($attempt);
    }

    public function test_evaluate_is_transaction_safe_and_idempotent(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        $this->service->submitAttempt($attempt, ['q1' => 'A']); 
        
        $result = $this->service->evaluateAttempt($attempt);
        
        $this->assertEquals('passed', $result->outcome);
        $this->assertEquals(1, \Illuminate\Support\Facades\DB::table('assessment_results')->where('assessment_attempt_id', $attempt->id)->count());
    }

    public function test_submit_rejects_unknown_answer_keys_asserts_state(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        try {
            $this->service->submitAttempt($attempt, ['is_correct' => true]);
            $this->fail("Expected InvalidArgumentException was not thrown.");
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Submitted answer contains unauthorized or unknown key: 'is_correct'.", $e->getMessage());
        }
        
        $attempt->refresh();
        $this->assertEquals('in_progress', $attempt->status);
        $this->assertNull($attempt->submitted_at);
        $this->assertDatabaseMissing('assessment_results', ['assessment_attempt_id' => $attempt->id]);
    }

    public function test_submit_rejects_unknown_answer_keys(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Submitted answer contains unauthorized or unknown key: 'is_correct'.");
        
        $this->service->submitAttempt($attempt, ['is_correct' => true]);
    }

    public function test_submit_rejects_list_answer_payload(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Submitted answers must be an associative map.");
        
        $this->service->submitAttempt($attempt, ['A']);
    }

    public function test_submit_rejects_non_scalar_answer_values(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Submitted answer value for 'q1' must be a supported scalar type (string, int, bool).");
        
        $this->service->submitAttempt($attempt, ['q1' => ['nested' => 'val']]);
    }

    public function test_submit_rejects_type_incompatible_answer_values(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 1]]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Submitted answer type for 'q1' is incompatible with the expected definition type.");
        
        // Expected an int, sending a string
        $this->service->submitAttempt($attempt, ['q1' => '1']);
    }

    public function test_submit_allows_missing_keys_and_scores_deterministically(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A', 'q2' => 'B']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        
        // Submitting only one valid key. The other is explicitly allowed to be missing.
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        
        $attempt->refresh();
        $this->assertEquals('submitted', $attempt->status);
        
        $result = AssessmentResult::query()->where('assessment_attempt_id', $attempt->id)->first();
        $this->assertNotNull($result);
        $this->assertEquals('failed', $result->outcome);
        $this->assertEquals(['score' => 1, 'total' => 2], $result->score_details);
    }

    public function test_result_immutability(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        $result = clone $this->service->evaluateAttempt($attempt);

        $this->expectException(LogicException::class);
        $result->update(['outcome' => 'failed']);
    }

    public function test_result_db_level_update_rejection(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-RES', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        $result = clone $this->service->evaluateAttempt($attempt);

        $this->expectException(QueryException::class);
        \Illuminate\Support\Facades\DB::table('assessment_results')->where('id', $result->id)->update(['outcome' => 'failed']);
    }

    public function test_dto_boundary_semantics_with_provenance_facts(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-DTO', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $actorId = (string) Str::uuid7();
        $attempt = $this->service->startAttempt((string)$def->id, $actorId);
        $this->service->submitAttempt($attempt, ['q1' => 'A']);
        $result = clone $this->service->evaluateAttempt($attempt);
        
        $dto = clone $this->service->getCandidateEvidence($result);
        
        $this->assertInstanceOf(AssessmentResultDto::class, $dto);
        $this->assertEquals('passed', $dto->outcome);
        $this->assertEquals($def->digest, $dto->payload['definition_digest']);
        $this->assertEquals($result->id, $dto->payload['result_id']);
        $this->assertEquals($attempt->id, $dto->payload['attempt_id']);
        $this->assertArrayHasKey('started_at', $dto->payload);
        $this->assertArrayHasKey('submitted_at', $dto->payload);
        $this->assertArrayHasKey('evaluated_at', $dto->payload);
        $this->assertArrayNotHasKey('ALLOW', $dto->payload);
        $this->assertArrayNotHasKey('DENY', $dto->payload);
        $this->assertEquals('MANUAL_ASSESSMENT', $dto->origin);
    }

    public function test_evaluating_in_progress_attempt_fails(): void
    {
        $def = clone $this->service->createDefinition('ASSESS-ERR', 'CAP-001', 'KU-001', ['expected_answers' => ['q1' => 'A']]);
        $attempt = $this->service->startAttempt((string)$def->id, (string) Str::uuid7());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot evaluate an attempt that is not submitted.");
        
        $this->service->evaluateAttempt($attempt);
    }
}
