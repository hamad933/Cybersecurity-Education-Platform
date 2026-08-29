<?php

namespace Tests\Feature;

use App\Application\Vs003\Vs003Lifecycle;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Simulator\Application\IdempotencyConflict;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\ScenarioRun;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use Database\Seeders\Vs003Seeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Vs003InvestigationTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $actor;

    private Vs003Lifecycle $flow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs003Seeder::class);
        $this->actor = app(CreateOwner::class)->execute(
            'VS3 owner',
            'vs3@example.test',
            'ReviewReady!Pass9',
            (string) Str::uuid7(),
        );
        $this->flow = app(Vs003Lifecycle::class);
    }

    #[Test]
    public function deterministic_investigation_covers_all_outcomes_and_declared_quality_states(): void
    {
        $expectations = [
            'VS3-BENIGN' => ['BENIGN_EXPLAINED', 'DEGRADED', 'duplicate_count', 1],
            'VS3-SUSPICIOUS' => ['SUSPICIOUS', 'HEALTHY', 'duplicate_count', 0],
            'VS3-INCIDENT' => ['INCIDENT_CONFIRMED', 'DEGRADED', 'late_count', 1],
            'VS3-INSUFFICIENT' => ['INSUFFICIENT_TELEMETRY', 'DEGRADED', 'contradictory_count', 1],
            'VS3-UNSUPPORTED' => ['UNSUPPORTED_STATE', 'UNSUPPORTED', 'unsupported_count', 1],
        ];

        foreach ($expectations as $caseId => [$outcome, $health, $qualityKey, $qualityValue]) {
            $result = $this->flow->runCase(
                $caseId,
                9003,
                "vs003:test:outcome:{$caseId}",
                (string) $this->actor->id,
            );

            $this->assertSame($outcome, $result['run']['outcome']);
            $this->assertSame($outcome, $result['trace']['outcome']);
            $this->assertSame($health, $result['trace']['telemetry_health']);
            $this->assertSame($qualityValue, $result['trace']['quality'][$qualityKey]);
            $this->assertSame('SIMULATED', $result['trace']['evidence_origin']);
            $this->assertSame('SIMULATED', $result['evidence']['origin']);
            $this->assertSame($this->actor->id, $result['evidence']['actor_id']);
            $this->assertTrue($result['evidence']['locked']);
            $this->assertSame('UTC', $result['trace']['normalized_input']['timezone']);
            $this->assertSame(config('vs003.behavior_version'), $result['trace']['detection_rationale']['behavior_version']);
            $this->assertSame($result['run']['trace_digest'], $result['trace']['timeline_digest']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['trace']['timeline_digest']);

            $ordered = array_map(
                static fn (array $event): array => [$event['occurred_at'], $event['id']],
                $result['trace']['events'],
            );
            $sorted = $ordered;
            sort($sorted);
            $this->assertSame($sorted, $ordered);
        }

        $first = $this->flow->runCase(
            'VS3-BENIGN',
            9003,
            'vs003:test:semantic:first',
            (string) $this->actor->id,
        );
        $second = $this->flow->runCase(
            'VS3-BENIGN',
            9003,
            'vs003:test:semantic:second',
            (string) $this->actor->id,
        );

        $this->assertNotSame($first['run']['id'], $second['run']['id']);
        $this->assertSame($first['trace']['timeline_digest'], $second['trace']['timeline_digest']);
        $this->assertSame(4, count($first['trace']['events']));
        $this->assertSame(2, $first['trace']['detection_rationale']['failed_logons']);
        $this->assertDatabaseCount('evidence_records', 7);
    }

    #[Test]
    public function idempotency_returns_the_historical_pinned_run_after_a_newer_revision_and_rejects_conflicts(): void
    {
        $key = 'vs003:test:idempotency:pinned';
        $original = $this->flow->runCase('VS3-SUSPICIOUS', 9003, $key, (string) $this->actor->id);

        $currentRule = SimulatorRuleRevision::query()
            ->where('rule_set_id', config('vs003.rule_set_id'))
            ->latest('revision')
            ->firstOrFail();
        $newRule = SimulatorRuleRevision::query()->create([
            'rule_set_id' => config('vs003.rule_set_id'),
            'revision' => 2,
            'authority_baseline_id' => $currentRule->authority_baseline_id,
            'state' => 'approved',
            'rules' => $currentRule->rules,
            'digest' => $currentRule->digest,
            'approved_at' => now(),
        ]);
        $currentScenario = ScenarioRevision::query()
            ->where('scenario_id', config('vs003.scenario_id'))
            ->latest('revision')
            ->firstOrFail();
        ScenarioRevision::query()->create([
            'scenario_id' => config('vs003.scenario_id'),
            'revision' => 2,
            'state' => 'published',
            'rule_set_revision_id' => $newRule->id,
            'enterprise_baseline_revision_id' => $currentScenario->enterprise_baseline_revision_id,
            'cases' => $currentScenario->cases,
            'digest' => $currentScenario->digest,
            'published_at' => now(),
        ]);

        $replayed = $this->flow->runCase('VS3-SUSPICIOUS', 9003, $key, (string) $this->actor->id);
        $this->assertSame($original['run']['id'], $replayed['run']['id']);
        $this->assertSame($original['run']['scenario_revision_id'], $replayed['run']['scenario_revision_id']);
        $this->assertSame($original['run']['rule_set_revision_id'], $replayed['run']['rule_set_revision_id']);
        $this->assertSame($original['trace']['timeline_digest'], $replayed['trace']['timeline_digest']);
        $this->assertDatabaseCount('scenario_runs', 1);
        $this->assertDatabaseCount('evidence_records', 1);

        $this->assertOperationThrows(
            fn () => $this->flow->runCase('VS3-SUSPICIOUS', 9004, $key, (string) $this->actor->id),
            IdempotencyConflict::class,
        );
        $other = $this->inactiveActor('vs3-idempotency-other@example.test');
        $this->assertOperationThrows(
            fn () => $this->flow->runCase('VS3-SUSPICIOUS', 9003, $key, (string) $other->id),
            IdempotencyConflict::class,
        );
    }

    #[Test]
    public function triage_is_actor_bound_exact_and_immutable(): void
    {
        $run = $this->flow->runCase(
            'VS3-SUSPICIOUS',
            9003,
            'vs003:test:triage:run',
            (string) $this->actor->id,
        );
        $rationale = 'Evidence supports the bounded suspicious disposition and records alternatives.';
        $triage = $this->flow->triage($run['run']['id'], (string) $this->actor->id, 'SUSPICIOUS', $rationale);
        $same = $this->flow->triage($run['run']['id'], (string) $this->actor->id, 'SUSPICIOUS', $rationale);

        $this->assertSame($triage['id'], $same['id']);
        $this->assertSame($this->actor->id, $triage['actor_id']);
        $this->assertSame('SUSPICIOUS', $triage['outcome']);
        $this->assertDatabaseCount('vs003_triage_records', 1);

        $this->assertOperationThrows(
            fn () => $this->flow->triage(
                $run['run']['id'],
                (string) $this->actor->id,
                'SUSPICIOUS',
                'A different rationale conflicts with the immutable triage record.',
            ),
            LogicException::class,
        );
        $this->assertOperationThrows(
            fn () => $this->flow->triage(
                $run['run']['id'],
                (string) $this->actor->id,
                'BENIGN_EXPLAINED',
                $rationale,
            ),
            LogicException::class,
        );
        $other = $this->inactiveActor('vs3-triage-other@example.test');
        $this->assertOperationThrows(
            fn () => $this->flow->triage($run['run']['id'], (string) $other->id, 'SUSPICIOUS', $rationale),
            LogicException::class,
        );
    }

    #[Test]
    public function incident_control_workflow_enforces_order_actor_binding_and_revision_pinned_replay(): void
    {
        $incident = $this->flow->runCase(
            'VS3-INCIDENT',
            9003,
            'vs003:test:incident:run',
            (string) $this->actor->id,
        );

        $this->assertOperationThrows(
            fn () => $this->flow->preserveEvidence($incident['run']['id'], (string) $this->actor->id),
            LogicException::class,
        );
        $this->flow->triage(
            $incident['run']['id'],
            (string) $this->actor->id,
            'INCIDENT_CONFIRMED',
            'The late synthetic failures support a bounded incident declaration and escalation.',
        );
        $this->assertOperationThrows(
            fn () => $this->flow->proposeContainment(
                $incident['run']['id'],
                (string) $this->actor->id,
                'Reduce continued synthetic authentication path exposure.',
                'The synthetic path may become unavailable during verification.',
                'Rollback when the verification replay does not observe the intended effect.',
            ),
            LogicException::class,
        );

        $custody = $this->flow->preserveEvidence($incident['run']['id'], (string) $this->actor->id);
        $sameCustody = $this->flow->preserveEvidence($incident['run']['id'], (string) $this->actor->id);
        $this->assertSame($custody['id'], $sameCustody['id']);
        $this->assertSame('PRESERVED_ORIGINAL', $custody['copy_kind']);
        $this->assertSame('SIMULATED', $custody['origin']);

        $proposal = $this->flow->proposeContainment(
            $incident['run']['id'],
            (string) $this->actor->id,
            'Reduce continued synthetic authentication path exposure.',
            'The synthetic path may become unavailable during verification.',
            'Rollback when the verification replay does not observe the intended effect.',
        );
        $sameProposal = $this->flow->proposeContainment(
            $incident['run']['id'],
            (string) $this->actor->id,
            'Reduce continued synthetic authentication path exposure.',
            'The synthetic path may become unavailable during verification.',
            'Rollback when the verification replay does not observe the intended effect.',
        );
        $this->assertSame($proposal['id'], $sameProposal['id']);
        $this->assertNotEmpty($proposal['triage_record_id']);
        $this->assertOperationThrows(
            fn () => $this->flow->proposeContainment(
                $incident['run']['id'],
                (string) $this->actor->id,
                'A conflicting synthetic effect must not replace the original proposal.',
                'The synthetic path may become unavailable during verification.',
                'Rollback when the verification replay does not observe the intended effect.',
            ),
            LogicException::class,
        );

        $other = $this->inactiveActor('vs3-control-other@example.test');
        $this->assertOperationThrows(
            fn () => $this->flow->approveContainment($proposal['id'], (string) $other->id),
            LogicException::class,
        );
        $approved = $this->flow->approveContainment($proposal['id'], (string) $this->actor->id);
        $sameApproval = $this->flow->approveContainment($proposal['id'], (string) $this->actor->id);
        $this->assertSame('APPROVED', $approved['state']);
        $this->assertSame($approved['id'], $sameApproval['id']);
        $this->assertSame($this->actor->id, $approved['approved_by']);

        $verified = $this->flow->verifyApprovedControl(
            $proposal['id'],
            $incident['run']['id'],
            (string) $this->actor->id,
            'vs003:test:verification:primary',
        );
        $sameKey = $this->flow->verifyApprovedControl(
            $proposal['id'],
            $incident['run']['id'],
            (string) $this->actor->id,
            'vs003:test:verification:primary',
        );
        $differentKey = $this->flow->verifyApprovedControl(
            $proposal['id'],
            $incident['run']['id'],
            (string) $this->actor->id,
            'vs003:test:verification:second',
        );

        $this->assertSame('published', $verified['control']['state']);
        $this->assertSame($this->actor->id, $verified['control']['actor_id']);
        $this->assertSame($proposal['id'], $verified['control']['proposal_id']);
        $this->assertSame($incident['run']['id'], $verified['control']['remediates_run_id']);
        $this->assertSame($incident['run']['id'], $verified['verification']['run']['verification_of_run_id']);
        $this->assertSame('BENIGN_EXPLAINED', $verified['verification']['run']['outcome']);
        $this->assertSame(
            $incident['trace']['normalized_input']['dataset_revision_id'],
            $verified['verification']['run']['normalized_input']['dataset_revision_id'],
        );
        $this->assertSame(
            $verified['control']['id'],
            $verified['verification']['run']['normalized_input']['control_revision_id'],
        );
        $this->assertTrue((bool) $verified['replay']['passed']);
        $this->assertSame($this->actor->id, $verified['replay']['actor_id']);
        $this->assertSame($incident['trace']['timeline_digest'], $verified['replay']['original_timeline_digest']);
        $this->assertSame($verified['control']['id'], $verified['replay']['control_revision_id']);
        $this->assertSame($verified['replay']['id'], $sameKey['replay']['id']);
        $this->assertSame($verified['replay']['id'], $differentKey['replay']['id']);
        $this->assertSame($verified['verification']['run']['id'], $differentKey['verification']['run']['id']);
        $this->assertDatabaseCount('vs003_control_revisions', 1);
        $this->assertDatabaseCount('vs003_verification_replays', 1);
        $this->assertSame(1, ScenarioRun::query()->where('verification_of_run_id', $incident['run']['id'])->count());

        $secondIncident = $this->flow->runCase(
            'VS3-INCIDENT',
            9003,
            'vs003:test:incident:second-run',
            (string) $this->actor->id,
        );
        $this->flow->triage(
            $secondIncident['run']['id'],
            (string) $this->actor->id,
            'INCIDENT_CONFIRMED',
            'A second bounded incident establishes an independent control and replay identity.',
        );
        $this->flow->preserveEvidence($secondIncident['run']['id'], (string) $this->actor->id);
        $secondProposal = $this->flow->proposeContainment(
            $secondIncident['run']['id'],
            (string) $this->actor->id,
            'Reduce the second synthetic authentication path exposure.',
            'The second synthetic path may become unavailable during verification.',
            'Rollback when the second verification replay does not observe the intended effect.',
        );
        $this->flow->approveContainment($secondProposal['id'], (string) $this->actor->id);

        $this->assertOperationThrows(
            fn () => $this->flow->verifyApprovedControl(
                $secondProposal['id'],
                $secondIncident['run']['id'],
                (string) $this->actor->id,
                'vs003:test:verification:primary',
            ),
            IdempotencyConflict::class,
        );
        $this->assertDatabaseCount('vs003_control_revisions', 1);
        $this->assertDatabaseCount('vs003_verification_replays', 1);
        $this->assertSame(0, ScenarioRun::query()->where('verification_of_run_id', $secondIncident['run']['id'])->count());

        $this->assertOperationThrows(
            fn () => $this->flow->verifyApprovedControl(
                $proposal['id'],
                $incident['run']['id'],
                (string) $other->id,
                'vs003:test:verification:other',
            ),
            LogicException::class,
        );
    }

    #[Test]
    public function mastery_uses_only_persisted_same_actor_facts_and_review_triggers_derive_from_actual_failures(): void
    {
        $suspicious = $this->flow->runCase(
            'VS3-SUSPICIOUS',
            9003,
            'vs003:test:mastery:suspicious',
            (string) $this->actor->id,
        );
        $this->flow->triage(
            $suspicious['run']['id'],
            (string) $this->actor->id,
            'SUSPICIOUS',
            'The bounded failures remain suspicious while legitimate user error stays an alternative.',
        );

        $incident = $this->flow->runCase(
            'VS3-INCIDENT',
            9003,
            'vs003:test:mastery:incident',
            (string) $this->actor->id,
        );
        $this->flow->triage(
            $incident['run']['id'],
            (string) $this->actor->id,
            'INCIDENT_CONFIRMED',
            'The late synthetic failures support incident declaration in this bounded case.',
        );
        $this->flow->preserveEvidence($incident['run']['id'], (string) $this->actor->id);
        $proposal = $this->flow->proposeContainment(
            $incident['run']['id'],
            (string) $this->actor->id,
            'Reduce continued synthetic authentication path exposure.',
            'The synthetic path may become unavailable during verification.',
            'Rollback when verification does not observe the intended synthetic effect.',
        );
        $this->flow->approveContainment($proposal['id'], (string) $this->actor->id);
        $this->flow->verifyApprovedControl(
            $proposal['id'],
            $incident['run']['id'],
            (string) $this->actor->id,
            'vs003:test:mastery:verification',
        );
        foreach (['VS3-INSUFFICIENT', 'VS3-UNSUPPORTED'] as $caseId) {
            $this->flow->runCase(
                $caseId,
                9003,
                "vs003:test:mastery:{$caseId}",
                (string) $this->actor->id,
            );
        }

        $failure = $this->flow->submitPractice((string) $this->actor->id, [
            'outcome' => 'BENIGN_EXPLAINED',
            'telemetry_health' => 'HEALTHY',
            'alternative_hypothesis' => 'legitimate_user_error',
        ]);
        $this->assertSame('wrong_triage', $failure['failure_class']);
        $this->flow->submitPractice((string) $this->actor->id, [
            'outcome' => 'BENIGN_EXPLAINED',
            'telemetry_health' => 'HEALTHY',
            'alternative_hypothesis' => 'legitimate_user_error',
        ]);
        $this->assertDatabaseCount('review_triggers', 1);
        $this->assertDatabaseHas('review_triggers', [
            'actor_id' => $this->actor->id,
            'failure_class' => 'wrong_triage',
            'source_type' => 'practice_attempt',
            'status' => 'scheduled',
        ]);

        $inProgress = $this->flow->evaluateMastery((string) $this->actor->id);
        $this->assertSame('IN_PROGRESS', $inProgress['status']);
        $this->assertFalse($inProgress['checks']['correct_practice']);

        $correct = $this->flow->submitPractice((string) $this->actor->id, [
            'outcome' => 'SUSPICIOUS',
            'telemetry_health' => 'HEALTHY',
            'alternative_hypothesis' => 'legitimate_user_error',
        ]);
        $this->assertNull($correct['failure_class']);
        $mastery = $this->flow->evaluateMastery((string) $this->actor->id);
        $this->assertSame('MASTERED', $mastery['status']);
        foreach ($mastery['checks'] as $check) {
            $this->assertTrue($check);
        }
        $this->assertDatabaseCount('review_triggers', 1);

        $other = $this->inactiveActor('vs3-mastery-other@example.test');
        $otherMastery = $this->flow->evaluateMastery((string) $other->id);
        $this->assertSame('NOT_MASTERED', $otherMastery['status']);
        $this->assertFalse($otherMastery['checks']['same_actor_and_provenance']);
        $this->assertFalse($otherMastery['checks']['verification_passed']);
        $this->assertDatabaseMissing('evidence_records', ['actor_id' => $other->id]);
    }

    #[Test]
    public function arabic_rtl_application_is_authenticated_bounded_and_does_not_accept_mastery_replay_input(): void
    {
        $this->get('/vs003/lab')->assertRedirect('/login');
        $this->actingAs($this->actor)
            ->get('/vs003/lab')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Vs003/AuthenticationInvestigation')
                ->where('evidenceOrigin', 'SIMULATED')
                ->where('baseline', config('vs003.authority_baseline_id'))
                ->has('cases', 5)
                ->has('workspace.simulation.runs')
                ->has('workspace.evidence.evidence')
                ->has('workspace.learning.practice'));

        $this->actingAs($this->actor)
            ->post('/vs003/lab/run', [
                'case_id' => 'invalid',
                'seed' => 9003,
                'idempotency_key' => 'vs003:test:ui:invalid',
            ])
            ->assertSessionHasErrors('case_id');
        $this->actingAs($this->actor)
            ->post('/vs003/lab/run', [
                'case_id' => 'VS3-SUSPICIOUS',
                'seed' => 9003,
            ])
            ->assertSessionHasErrors('idempotency_key');
        $legacyTruthBefore = [
            'evidence_records' => DB::table('evidence_records')->count(),
            'evidence_decisions' => DB::table('evidence_decisions')->count(),
            'mastery_states' => DB::table('mastery_states')->count(),
            'review_triggers' => DB::table('review_triggers')->count(),
        ];

        $this->actingAs($this->actor)
            ->post('/vs003/mastery/evaluate', [
                'passed' => true,
                'actor_id' => (string) Str::uuid7(),
                'verification_replay' => ['passed' => true],
            ])
            ->assertStatus(410);
        $this->assertSame($legacyTruthBefore, [
            'evidence_records' => DB::table('evidence_records')->count(),
            'evidence_decisions' => DB::table('evidence_decisions')->count(),
            'mastery_states' => DB::table('mastery_states')->count(),
            'review_triggers' => DB::table('review_triggers')->count(),
        ]);
        $this->assertDatabaseMissing('mastery_states', [
            'actor_id' => $this->actor->id,
            'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
        ]);

        $source = file_get_contents(resource_path('js/pages/Vs003/AuthenticationInvestigation.vue'));
        $this->assertStringContainsString('dir="rtl"', $source);
        $this->assertStringContainsString('dir="ltr"', $source);
        $this->assertStringContainsString('focus-ring', $source);
        $this->assertStringContainsString('SIMULATED', $source);
        $this->assertStringNotContainsString('v-html', $source);
    }

    #[Test]
    public function database_enforces_append_only_vs003_final_records(): void
    {
        $run = $this->flow->runCase(
            'VS3-SUSPICIOUS',
            9003,
            'vs003:test:immutable:run',
            (string) $this->actor->id,
        );
        $this->flow->triage(
            $run['run']['id'],
            (string) $this->actor->id,
            'SUSPICIOUS',
            'The persisted triage record is immutable after its first exact write.',
        );

        $triggerNames = DB::table('pg_trigger')
            ->whereIn('tgname', [
                'vs003_dataset_immutable',
                'vs003_triage_immutable',
                'vs003_custody_immutable',
                'vs003_control_immutable',
                'vs003_replay_immutable',
            ])
            ->pluck('tgname')
            ->sort()
            ->values()
            ->all();
        $this->assertCount(5, $triggerNames);

        $this->expectException(QueryException::class);
        DB::table('vs003_triage_records')
            ->where('scenario_run_id', $run['run']['id'])
            ->update(['rationale' => 'Mutation must be rejected.']);
    }

    private function inactiveActor(string $email): OwnerAccount
    {
        return OwnerAccount::query()->create([
            'display_name' => 'Inactive test actor',
            'email' => $email,
            'password' => 'ReviewReady!Pass9',
            'is_active' => false,
        ]);
    }

    /** @param class-string<\Throwable> $exception */
    private function assertOperationThrows(callable $operation, string $exception): void
    {
        try {
            $operation();
            $this->fail("Expected {$exception} was not thrown.");
        } catch (\Throwable $caught) {
            $this->assertInstanceOf($exception, $caught);
        }
    }
}
