<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Models\LessonRevision;
use Database\Seeders\Vs001Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Vs001WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs001Seeder::class);
    }

    #[Test]
    public function all_workspaces_are_owner_only_and_render_real_seeded_data(): void
    {
        foreach (['/vs001/sources', '/vs001/lesson/editor', '/vs001/lesson', '/vs001/practice', '/vs001/lab', '/vs001/evidence'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $owner = $this->owner();
        $this->actingAs($owner)->get('/vs001/sources')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/SourceReview')->has('sources', 7));
        $this->actingAs($owner)->get('/vs001/lesson/editor')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/LessonEditor')->has('revisions', 1));
        $this->actingAs($owner)->get('/vs001/lesson')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/LessonReader')->where('lesson.state', 'published'));
        $this->actingAs($owner)->get('/vs001/practice')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/MicroPractice'));
        $this->actingAs($owner)->get('/vs001/lab')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/GuidedLab')->has('scenario.cases', 12));
        $this->actingAs($owner)->get('/vs001/evidence')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Vs001/EvidenceMastery'));
    }

    #[Test]
    public function lab_requests_are_bounded_actor_scoped_and_idempotency_conflicts_are_controlled(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/vs001/lab/run', [
            'case_id' => 'CASE-DOES-NOT-EXIST',
            'seed' => 0,
            'idempotency_key' => '../../escape',
        ])->assertSessionHasErrors(['case_id', 'seed', 'idempotency_key']);
        $this->assertDatabaseCount('scenario_runs', 0);

        $request = ['case_id' => 'CASE-001-EXPLICIT-ALLOW', 'seed' => 77, 'idempotency_key' => 'feature.case.001'];
        $this->actingAs($owner)->post('/vs001/lab/run', $request)->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/vs001/lab/run', $request)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('scenario_runs', 1);
        $this->assertDatabaseCount('evidence_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseHas('scenario_runs', ['actor_id' => $owner->id, 'case_id' => 'CASE-001-EXPLICIT-ALLOW', 'outcome' => 'ALLOW']);
        $this->assertDatabaseHas('evidence_records', ['actor_id' => $owner->id, 'origin' => 'SIMULATED', 'result' => 'ALLOW']);

        $this->actingAs($owner)->post('/vs001/lab/run', array_replace($request, ['seed' => 78]))
            ->assertRedirect()
            ->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseCount('scenario_runs', 1);
    }

    /** @param array<string,string> $changes */
    #[Test]
    #[DataProvider('practiceAnswers')]
    public function structured_practice_is_evaluated_server_side(array $changes, ?string $failureClass): void
    {
        $owner = $this->owner();
        $answer = array_replace([
            'selected_outcome' => 'DENY',
            'decisive_step_id' => 'ace-step-1',
            'decisive_ace_id' => 'ACE-DENY-001',
            'relevant_requested_mask' => '0x00000001',
            'remaining_mask' => '0x00000001',
            'rationale' => 'The explicit deny is ordered before the later allow.',
        ], $changes);

        $this->actingAs($owner)->post('/vs001/practice', $answer)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('practice_attempts', [
            'actor_id' => $owner->id,
            'outcome' => $failureClass === null ? 'correct' : 'incorrect',
            'failure_class' => $failureClass,
        ]);
        if ($failureClass === null) {
            $this->assertDatabaseCount('review_triggers', 0);
        } else {
            $this->assertDatabaseHas('review_triggers', [
                'actor_id' => $owner->id,
                'failure_class' => $failureClass,
                'source_type' => 'practice_attempt',
            ]);
        }
    }

    /** @return iterable<string,array{array<string,string>,?string}> */
    public static function practiceAnswers(): iterable
    {
        yield 'correct structured answer' => [[], null];
        yield 'missing rationale' => [['rationale' => ''], 'rationale_missing'];
        yield 'unsupported-state guess' => [['selected_outcome' => 'UNSUPPORTED_STATE'], 'unsupported_state_guess'];
        yield 'wrong decision' => [['selected_outcome' => 'ALLOW'], 'incorrect_decision'];
        yield 'wrong decisive ACE' => [['decisive_ace_id' => 'ACE-ALLOW-002'], 'missed_decisive_ace'];
        yield 'wrong requested mask' => [['relevant_requested_mask' => '0x00000002'], 'requested_mask_error'];
        yield 'wrong rationale' => [['rationale' => 'No grounded reason is supplied.'], 'incorrect_decision'];
    }

    #[Test]
    public function oversized_practice_payload_is_rejected_without_persistence(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/vs001/practice', [
            'selected_outcome' => 'DENY',
            'decisive_step_id' => 'ace-step-1',
            'decisive_ace_id' => 'ACE-DENY-001',
            'relevant_requested_mask' => '0x00000001',
            'remaining_mask' => '0x00000001',
            'rationale' => str_repeat('x', 1001),
        ])->assertSessionHasErrors('rationale');
        $this->assertDatabaseCount('practice_attempts', 0);
    }

    #[Test]
    public function lesson_editor_routes_complete_bounded_review_publication_and_restore(): void
    {
        $owner = $this->owner();
        $published = LessonRevision::query()->where('state', 'published')->firstOrFail();
        $this->actingAs($owner)->post("/vs001/lesson/{$published->id}/restore")->assertRedirect()->assertSessionHasNoErrors();
        $draft = LessonRevision::query()->where('state', 'draft')->latest('revision')->firstOrFail();
        $payload = [
            'lock_version' => 1,
            'blocks' => [['type' => 'paragraph', 'body' => 'Bounded reviewed lesson revision.']],
            'citations' => config('vs001.required_claim_ids'),
        ];

        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/update", $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/update", $payload)->assertRedirect()->assertSessionHasErrors('revision');
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/update", array_replace($payload, [
            'lock_version' => 2,
            'blocks' => [['type' => 'paragraph', 'body' => '<img src=x onerror=alert(1)>']],
        ]))->assertRedirect()->assertSessionHasErrors('revision');

        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/submit")->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/return", ['rationale' => 'Clarify the decisive ordered ACE and requested mask.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_revisions', ['id' => $draft->id, 'state' => 'draft', 'review_decision' => 'RETURNED']);
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/submit")->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/approve")->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/publish")->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_revisions', ['id' => $draft->id, 'state' => 'published', 'published_by' => $owner->id]);
        $this->actingAs($owner)->post("/vs001/lesson/{$draft->id}/restore")->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lesson_revisions', ['revision' => 3, 'state' => 'draft', 'derived_from_revision_id' => $draft->id]);
        $this->assertDatabaseHas('audit_records', ['action' => 'lesson.published', 'actor_identifier' => $owner->id]);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'Reviewer',
            'reviewer@example.test',
            'ReviewReady!Pass9',
            (string) Str::uuid7(),
        );
    }
}
