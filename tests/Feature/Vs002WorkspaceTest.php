<?php

namespace Tests\Feature;

use App\Modules\Evidence\Models\SecurityFinding;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Simulator\Models\AuthorizationPolicyRevision;
use App\Modules\Simulator\Models\ScenarioRun;
use Database\Seeders\Vs002Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Vs002WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs002Seeder::class);
    }

    #[Test]
    public function all_real_workspaces_are_owner_only_and_use_seeded_revisions(): void
    {
        foreach (['/vs002/sources', '/vs002/lesson/editor', '/vs002/lesson', '/vs002/practice', '/vs002/lab', '/vs002/evidence'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $owner = $this->owner();
        $this->actingAs($owner)->get('/vs002/sources')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/SourceAuthority')->has('sources', 7));
        $this->actingAs($owner)->get('/vs002/lesson/editor')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/LessonEditor')->has('revisions', 1));
        $this->actingAs($owner)->get('/vs002/lesson')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/LessonReader')->where('lesson.state', 'published'));
        $this->actingAs($owner)->get('/vs002/practice')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/MicroPractice'));
        $this->actingAs($owner)->get('/vs002/lab')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/GuidedRequestLab')->has('scenario.cases', 12)->has('policies', 2));
        $this->actingAs($owner)->get('/vs002/evidence')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs002/EvidenceMastery'));
    }

    #[Test]
    public function request_validation_and_same_actor_idempotency_are_enforced(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/vs002/lab/run', ['case_id' => 'CASE-WEB-999', 'seed' => 0, 'idempotency_key' => '../../escape'])->assertSessionHasErrors(['case_id', 'seed', 'idempotency_key']);
        $request = ['case_id' => 'CASE-WEB-002', 'seed' => 8002, 'idempotency_key' => 'workspace.case.web.002'];
        $this->actingAs($owner)->post('/vs002/lab/run', $request)->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/vs002/lab/run', $request)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('scenario_runs', 1);
        $this->assertDatabaseHas('scenario_runs', ['actor_id' => $owner->id, 'case_id' => 'CASE-WEB-002', 'outcome' => 'ALLOW']);
        $this->assertDatabaseHas('security_findings', ['actor_id' => $owner->id, 'category' => 'access_control']);
        $this->actingAs($owner)->post('/vs002/lab/run', array_replace($request, ['seed' => 8003]))->assertSessionHasErrors('run');
        $this->assertDatabaseCount('scenario_runs', 1);
    }

    #[Test]
    public function structured_practice_is_server_evaluated_and_failure_specific(): void
    {
        $owner = $this->owner();
        $answer = ['actor' => 'SIM-BOB', 'resource_owner' => 'SIM-ALICE', 'requested_action' => 'case_file.read', 'missing_trust_boundary' => 'authorization_policy', 'expected_policy_decision' => 'DENY', 'expected_http_response_class' => '4xx', 'decisive_rule' => 'WEB-RULE-CROSS-OWNER-DENY', 'safe_detection_field' => 'trace_digest', 'rationale' => 'الملكية تفحص على الخادم مع server-side default deny.'];
        $this->actingAs($owner)->post('/vs002/practice', $answer)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('practice_attempts', ['actor_id' => $owner->id, 'outcome' => 'correct', 'failure_class' => null]);
        $this->actingAs($owner)->post('/vs002/practice', array_replace($answer, ['safe_detection_field' => 'session_token']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('practice_attempts', ['actor_id' => $owner->id, 'outcome' => 'incorrect', 'failure_class' => 'unsafe_logging']);
        $this->assertDatabaseHas('review_triggers', ['actor_id' => $owner->id, 'failure_class' => 'unsafe_logging', 'source_type' => 'practice_attempt']);
    }

    #[Test]
    public function lesson_workflow_allows_inert_code_but_rejects_active_prose(): void
    {
        $owner = $this->owner();
        $published = LessonRevision::query()->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->where('state', 'published')->sole();
        $this->actingAs($owner)->post("/vs002/lesson/{$published->id}/restore")->assertSessionHasNoErrors();
        $draft = LessonRevision::query()->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->where('state', 'draft')->sole();
        $marker = '<img src=x onerror="document.documentElement.dataset.xssProbe=\'executed\'">';
        $payload = ['lock_version' => 1, 'blocks' => [['type' => 'code', 'body' => $marker], ['type' => 'paragraph', 'body' => 'Safe reviewed authorization lesson.']], 'citations' => config('vs002.required_claim_ids')];
        $this->actingAs($owner)->post("/vs002/lesson/{$draft->id}/update", $payload)->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs002/lesson/{$draft->id}/update", array_replace($payload, ['lock_version' => 2, 'blocks' => [['type' => 'paragraph', 'body' => $marker]]]))->assertSessionHasErrors('revision');
        $this->actingAs($owner)->post("/vs002/lesson/{$draft->id}/submit")->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs002/lesson/{$draft->id}/approve")->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs002/lesson/{$draft->id}/publish")->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_revisions', ['id' => $draft->id, 'state' => 'published', 'authority_baseline_id' => config('vs002.authority_baseline_id')]);
        $this->assertDatabaseHas('audit_records', ['action' => 'lesson.published', 'actor_identifier' => $owner->id]);
    }

    #[Test]
    public function remediation_and_verification_routes_link_the_vulnerable_request(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/vs002/lab/run', ['case_id' => 'CASE-WEB-002', 'seed' => 8002, 'idempotency_key' => 'route:vulnerable'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/vs002/remediation')->assertSessionHasNoErrors();
        $run = ScenarioRun::query()->where('case_id', 'CASE-WEB-002')->sole();
        $finding = SecurityFinding::query()->where('scenario_run_id', $run->id)->where('category', 'access_control')->sole();
        $policy = AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->where('revision', 2)->sole();
        $this->actingAs($owner)->post("/vs002/findings/{$finding->id}/verify", ['vulnerable_run_id' => $run->id, 'remediation_policy_revision_id' => $policy->id, 'idempotency_key' => 'route:verification'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('finding_verifications', ['security_finding_id' => $finding->id, 'vulnerable_run_id' => $run->id, 'remediation_policy_revision_id' => $policy->id, 'status' => 'VERIFIED_FIXED']);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute('VS2 owner', 'owner-vs2@example.test', 'ReviewReady!Pass9', (string) Str::uuid7());
    }
}
