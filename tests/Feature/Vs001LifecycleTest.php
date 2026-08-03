<?php

namespace Tests\Feature;

use App\Application\Vs001\Vs001Lifecycle;
use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\Evidence\Models\EvidenceRecord;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use Database\Seeders\Vs001Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Vs001LifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Vs001Lifecycle $lifecycle;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs001Seeder::class);
        $this->owner = app(CreateOwner::class)->execute('Reviewer', 'reviewer@example.test', 'ReviewReady!Pass9', (string) Str::uuid7());
        $this->lifecycle = app(Vs001Lifecycle::class);
    }

    #[Test]
    public function run_is_idempotent_actor_bound_isolated_and_issues_one_evidence_and_outbox_message(): void
    {
        $baseline = EnterpriseBaselineRevision::query()->sole();
        $before = $baseline->snapshot_digest;
        $first = $this->lifecycle->runCase('CASE-001-EXPLICIT-ALLOW', 7001, 'test:case-001', $this->owner->id);
        $second = $this->lifecycle->runCase('CASE-001-EXPLICIT-ALLOW', 7001, 'test:case-001', $this->owner->id);

        $this->assertSame($first['run']['id'], $second['run']['id']);
        $this->assertSame('ALLOW', $first['run']['outcome']);
        $this->assertSame($this->owner->id, $first['run']['actor_id']);
        $this->assertSame($this->owner->id, $first['evidence']['actor_id']);
        $this->assertSame('SIMULATED', $first['evidence']['origin']);
        $this->assertSame($first['run']['trace_digest'], $first['evidence']['trace_digest']);
        $this->assertSame($before, $baseline->fresh()->snapshot_digest);
        $this->assertDatabaseCount('scenario_runs', 1);
        $this->assertDatabaseCount('evidence_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);

        $this->expectException(LogicException::class);
        $this->lifecycle->runCase('CASE-003-DENY-BEFORE-ALLOW', 7003, 'test:case-001', $this->owner->id);
    }

    #[Test]
    public function replay_stays_pinned_after_a_newer_revision_is_published(): void
    {
        $baseline = EnterpriseBaselineRevision::query()->sole();
        $before = $baseline->snapshot_digest;
        $original = $this->lifecycle->runCase('CASE-003-DENY-BEFORE-ALLOW', 7003, 'test:pin:original', $this->owner->id);
        $originalScenarioId = $original['run']['scenario_revision_id'];
        $originalRulesId = $original['run']['rule_set_revision_id'];

        $rulesV1 = SimulatorRuleRevision::query()->findOrFail($originalRulesId);
        $rulesV2 = SimulatorRuleRevision::query()->create([
            'rule_set_id' => $rulesV1->rule_set_id,
            'revision' => 2,
            'authority_baseline_id' => $rulesV1->authority_baseline_id,
            'state' => 'approved',
            'rules' => ['scope' => 'new revision used only by new runs'],
            'digest' => hash('sha256', 'rules-v2'),
            'approved_at' => now(),
        ]);
        $scenarioV1 = ScenarioRevision::query()->findOrFail($originalScenarioId);
        $cases = $scenarioV1->caseDefinitions();
        foreach ($cases as &$case) {
            if ($case['case_id'] === 'CASE-003-DENY-BEFORE-ALLOW') {
                $case['input']['security_descriptor']['dacl'] = [[
                    'ace_id' => 'ACE-ALLOW-V2',
                    'type' => 'ACCESS_ALLOWED',
                    'trustee_sid' => $case['input']['token_user_sid'],
                    'access_mask' => '0x00000001',
                ]];
                $case['expected'] = 'ALLOW';
            }
        }
        unset($case);
        $scenarioV2 = ScenarioRevision::query()->create([
            'scenario_id' => $scenarioV1->scenario_id,
            'revision' => 2,
            'state' => 'published',
            'rule_set_revision_id' => $rulesV2->id,
            'enterprise_baseline_revision_id' => $scenarioV1->enterprise_baseline_revision_id,
            'cases' => $cases,
            'digest' => hash('sha256', json_encode($cases, JSON_THROW_ON_ERROR)),
            'published_at' => now(),
        ]);

        $replay = $this->lifecycle->replay($original['run']['id'], 'test:pin:replay');
        $this->assertTrue($replay['digest_match']);
        $this->assertDatabaseHas('scenario_runs', [
            'id' => $replay['replay_run_id'],
            'scenario_revision_id' => $originalScenarioId,
            'rule_set_revision_id' => $originalRulesId,
            'trace_digest' => $original['run']['trace_digest'],
        ]);

        $newRun = $this->lifecycle->runCase('CASE-003-DENY-BEFORE-ALLOW', 7003, 'test:pin:new', $this->owner->id);
        $this->assertSame($scenarioV2->id, $newRun['run']['scenario_revision_id']);
        $this->assertSame($rulesV2->id, $newRun['run']['rule_set_revision_id']);
        $this->assertSame('ALLOW', $newRun['run']['outcome']);
        $this->assertSame($before, $baseline->fresh()->snapshot_digest);
    }

    #[Test]
    public function mastery_reads_only_evidence_bound_to_the_requested_actor(): void
    {
        $other = OwnerAccount::query()->create([
            'display_name' => 'Historical learner',
            'email' => 'historical@example.test',
            'password' => 'Historical!Pass9',
            'is_active' => false,
        ]);
        $allow = $this->lifecycle->runCase('CASE-001-EXPLICIT-ALLOW', 1, 'other:allow', $other->id);
        $deny = $this->lifecycle->runCase('CASE-003-DENY-BEFORE-ALLOW', 2, 'other:deny', $other->id);
        $unsupported = $this->lifecycle->runCase('CASE-010-UNSUPPORTED-ACE', 3, 'other:unsupported', $other->id);
        foreach ([$allow, $deny, $unsupported] as $item) {
            $this->lifecycle->decideEvidence($item['evidence']['id'], 'ACCEPTED', 'الدليل مرتبط بالمراجعات والمصدر ومناسب للنطاق.', $this->owner->id);
        }
        $this->lifecycle->replay($allow['run']['id'], 'other:allow:replay');

        $this->assertSame('MASTERED', $this->lifecycle->evaluateMastery($other->id)['status']);
        $this->assertSame('NOT_MASTERED', $this->lifecycle->evaluateMastery($this->owner->id)['status']);
    }

    #[Test]
    public function accepted_evidence_is_immutable_and_improvement_is_proposal_only(): void
    {
        $baseline = EnterpriseBaselineRevision::query()->sole();
        $result = $this->lifecycle->runCase('CASE-001-EXPLICIT-ALLOW', 5, 'immutable:allow', $this->owner->id);
        $this->lifecycle->decideEvidence($result['evidence']['id'], 'ACCEPTED', 'الدليل مرتبط بالمراجعات والمصدر ومناسب للنطاق.', $this->owner->id);
        $proposal = $this->lifecycle->proposeImprovement($result['run']['id'], $baseline->id, ['summary' => 'اقتراح فقط.']);
        $this->assertSame('proposed', $proposal['status']);
        $this->assertSame($baseline->snapshot_digest, $baseline->fresh()->snapshot_digest);

        $locked = EvidenceRecord::query()->findOrFail($result['evidence']['id']);
        $this->expectException(LogicException::class);
        $locked->forceFill(['result' => 'DENY'])->save();
    }

    #[DataProvider('observedFailures')]
    #[Test]
    public function non_practice_failure_classes_are_derived_from_actual_observations(string $expectedClass, array $observation): void
    {
        $first = $this->lifecycle->recordObservedFailure($this->owner->id, 'CASE-003-DENY-BEFORE-ALLOW', 'lab_result', (string) Str::uuid7(), (string) Str::uuid7(), $observation);
        $second = $this->lifecycle->recordObservedFailure($this->owner->id, 'CASE-003-DENY-BEFORE-ALLOW', 'lab_result', (string) Str::uuid7(), (string) Str::uuid7(), $observation);
        $this->assertSame($expectedClass, $first['failure_class']);
        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('review_triggers', 1);
    }

    /** @return iterable<string,array{string,array<string,mixed>}> */
    public static function observedFailures(): iterable
    {
        yield 'replay mismatch' => ['replay_mismatch', ['replay_match' => false]];
        yield 'missing provenance' => ['missing_provenance', ['provenance_present' => false]];
        yield 'retention failure' => ['failed_retention', ['retention_passed' => false]];
        yield 'group attribute failure' => ['wrong_group_attribute', ['expected_group_attribute' => 'enabled', 'actual_group_attribute' => 'deny_only']];
    }
}
