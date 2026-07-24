<?php

namespace Tests\Feature;

use App\Application\Vs002\Vs002Lifecycle;
use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Simulator\Application\IdempotencyConflict;
use App\Modules\Simulator\Models\AuthorizationPolicyRevision;
use Database\Seeders\Vs002Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Vs002LifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Vs002Lifecycle $lifecycle;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs002Seeder::class);
        $this->owner = app(CreateOwner::class)->execute('VS2 reviewer', 'vs2@example.test', 'ReviewReady!Pass9', (string) Str::uuid7());
        $this->lifecycle = app(Vs002Lifecycle::class);
    }

    #[Test]
    public function vulnerable_request_creates_actor_bound_finding_and_is_idempotent(): void
    {
        $baseline = EnterpriseBaselineRevision::query()->where('baseline_id', 'ENT-BASELINE-VS002')->sole();
        $first = $this->lifecycle->runCase('CASE-WEB-002', 8002, 'vs2:case:002', $this->owner->id);
        $second = $this->lifecycle->runCase('CASE-WEB-002', 8002, 'vs2:case:002', $this->owner->id);

        $this->assertSame($first['run']['id'], $second['run']['id']);
        $this->assertSame('ALLOW', $first['run']['outcome']);
        $this->assertSame('access_control', $first['findings'][0]['category']);
        $this->assertSame('object_ownership_check_missing', $first['findings'][0]['decisive_missing_check']);
        $this->assertSame($this->owner->id, $first['evidence']['actor_id']);
        $this->assertSame('SIMULATED', $first['evidence']['origin']);
        $this->assertSame($baseline->snapshot_digest, $baseline->fresh()->snapshot_digest);
        $this->assertDatabaseCount('scenario_runs', 1);
        $this->assertDatabaseCount('evidence_records', 1);
        $this->assertDatabaseCount('security_findings', 1);
        $this->assertDatabaseCount('outbox_messages', 1);

        $this->expectException(IdempotencyConflict::class);
        $this->lifecycle->runCase('CASE-WEB-002', 8003, 'vs2:case:002', $this->owner->id);
    }

    #[Test]
    public function remediation_is_new_immutable_revision_and_verification_links_both_runs(): void
    {
        $vulnerable = $this->lifecycle->runCase('CASE-WEB-002', 8002, 'vs2:vulnerable', $this->owner->id);
        $policy = $this->lifecycle->remediate();
        $verified = $this->lifecycle->verify($vulnerable['findings'][0]['id'], $vulnerable['run']['id'], $policy['id'], 'vs2:verify', $this->owner->id);

        $this->assertSame(2, $policy['revision']);
        $this->assertSame('secure', $policy['mode']);
        $this->assertSame('DENY', $verified['run']['outcome']);
        $this->assertSame($vulnerable['run']['id'], $verified['run']['verification_of_run_id']);
        $this->assertSame($policy['id'], $verified['run']['remediation_revision_id']);
        $this->assertSame('VERIFIED_FIXED', $verified['verification']['status']);
        $this->assertDatabaseHas('security_findings', ['id' => $vulnerable['findings'][0]['id'], 'status' => 'verified_fixed']);
        $this->assertDatabaseHas('finding_verifications', ['vulnerable_run_id' => $vulnerable['run']['id'], 'verification_run_id' => $verified['run']['id'], 'remediation_policy_revision_id' => $policy['id']]);

        $storedPolicy = AuthorizationPolicyRevision::query()->findOrFail($policy['id']);
        $this->expectException(LogicException::class);
        $storedPolicy->forceFill(['mode' => 'vulnerable'])->save();
    }

    #[Test]
    public function replay_remains_pinned_and_client_role_never_overrides_server_authorization(): void
    {
        $policy = $this->lifecycle->remediate();
        $clientRole = $this->lifecycle->runCase('CASE-WEB-006', 8006, 'vs2:client-role', $this->owner->id);
        $this->assertSame('DENY', $clientRole['run']['outcome']);
        $this->assertSame('admin', $clientRole['trace']['authorization_inputs']['client_supplied_role_ignored']);
        $this->assertSame('user', $clientRole['trace']['baseline_derived_facts']['server_role']);
        $this->assertSame('SIM-ALICE', $clientRole['trace']['baseline_derived_facts']['resource_owner_id']);
        $this->assertSame($policy['id'], $clientRole['run']['policy_revision_id']);

        $original = $this->lifecycle->runCase('CASE-WEB-012', 8012, 'vs2:replay:original', $this->owner->id);
        AuthorizationPolicyRevision::query()->create(['policy_id' => config('vs002.policy_id'), 'revision' => 4, 'state' => 'published', 'mode' => 'secure', 'rules' => ['default' => 'DENY', 'changed' => true], 'source_claim_ids' => ['WEB-AUTH-003'], 'digest' => hash('sha256', 'policy-v4'), 'published_at' => now()]);
        $replay = $this->lifecycle->replay($original['run']['id'], 'vs2:replay:pinned');
        $this->assertTrue($replay['digest_match']);
        $this->assertDatabaseHas('scenario_runs', ['id' => $replay['replay_run_id'], 'policy_revision_id' => $original['run']['policy_revision_id'], 'trace_digest' => $original['run']['trace_digest']]);
    }

    #[Test]
    public function mastery_requires_balanced_actor_bound_remediation_verification_provenance_and_replay(): void
    {
        $allow = $this->lifecycle->runCase('CASE-WEB-001', 8101, 'mastery:allow', $this->owner->id);
        $this->accept($allow['evidence']['id']);
        $this->assertSame('IN_PROGRESS', $this->lifecycle->evaluateMastery($this->owner->id)['status']);

        $vulnerable = $this->lifecycle->runCase('CASE-WEB-002', 8102, 'mastery:vulnerable', $this->owner->id);
        $this->accept($vulnerable['evidence']['id']);
        $policy = $this->lifecycle->remediate();
        $this->assertSame('IN_PROGRESS', $this->lifecycle->evaluateMastery($this->owner->id)['status']);

        $verification = $this->lifecycle->verify($vulnerable['findings'][0]['id'], $vulnerable['run']['id'], $policy['id'], 'mastery:verification', $this->owner->id);
        $safe = $this->lifecycle->runCase('CASE-WEB-011', 8111, 'mastery:safe-rendering', $this->owner->id);
        $this->accept($verification['evidence']['id']);
        $this->accept($safe['evidence']['id']);
        $this->lifecycle->replay($allow['run']['id'], 'mastery:replay');
        $this->assertSame('IN_PROGRESS', $this->lifecycle->evaluateMastery($this->owner->id)['status']);
        $this->lifecycle->submitPractice($this->owner->id, ['actor' => 'SIM-BOB', 'resource_owner' => 'SIM-ALICE', 'requested_action' => 'case_file.read', 'missing_trust_boundary' => 'authorization_policy', 'expected_policy_decision' => 'DENY', 'expected_http_response_class' => '4xx', 'decisive_rule' => 'WEB-RULE-CROSS-OWNER-DENY', 'safe_detection_field' => 'trace_digest', 'rationale' => 'ownership server-side deny']);
        $this->assertSame('MASTERED', $this->lifecycle->evaluateMastery($this->owner->id)['status']);

        $other = OwnerAccount::query()->create(['display_name' => 'Other learner', 'email' => 'other-vs2@example.test', 'password' => 'Other!Pass12345', 'is_active' => false]);
        $this->assertSame('NOT_MASTERED', $this->lifecycle->evaluateMastery($other->id)['status']);
    }

    private function accept(string $evidenceId): void
    {
        $this->lifecycle->decideEvidence($evidenceId, 'ACCEPTED', 'الدليل المحاكى مثبت بالمراجعات والأثر ومناسب للنطاق المحدد.', $this->owner->id);
    }
}
