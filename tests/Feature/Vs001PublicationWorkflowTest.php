<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use App\Modules\Simulator\Authorization\WindowsAuthorizationDecisionEngine;
use Database\Seeders\Vs001Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class Vs001PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private LessonRevisionWorkflow $workflow;

    private string $actorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Vs001Seeder::class);
        $this->workflow = app(LessonRevisionWorkflow::class);
        $this->actorId = (string) app(CreateOwner::class)->execute(
            'Lesson Reviewer',
            'lesson-reviewer@example.test',
            'ReviewReady!Pass9',
            (string) Str::uuid7(),
        )->id;
    }

    #[Test]
    public function draft_update_uses_optimistic_lock_and_stable_digest(): void
    {
        $draft = $this->workflow->createDraft('KU-AD-02', $this->blocks('First draft'), config('vs001.required_claim_ids'), actorId: $this->actorId);
        $updated = $this->workflow->updateDraft($draft->id, 1, $this->blocks('Second draft'), config('vs001.required_claim_ids'), $this->actorId);
        $sameContent = $this->workflow->updateDraft($updated->id, 2, $this->blocks('Second draft'), config('vs001.required_claim_ids'), $this->actorId);

        $this->assertSame(2, $updated->lock_version);
        $this->assertSame($updated->content_digest, $sameContent->content_digest);
        $this->assertDatabaseHas('audit_records', ['action' => 'lesson.draft.updated', 'actor_identifier' => $this->actorId]);

        $this->expectException(RuntimeException::class);
        $this->workflow->updateDraft($draft->id, 2, $this->blocks('Conflicting edit'), config('vs001.required_claim_ids'), $this->actorId);
    }

    #[Test]
    public function publication_is_blocked_without_approved_authority(): void
    {
        $draft = $this->workflow->createDraft('KU-AD-02', $this->blocks('No authority baseline'), config('vs001.required_claim_ids'), actorId: $this->actorId);
        $this->workflow->submitForReview($draft->id, $this->actorId);
        $this->workflow->review($draft->id, 'APPROVED', $this->actorId);

        $this->expectException(LogicException::class);
        $this->workflow->publish($draft->id, $this->actorId, config('vs001.required_claim_ids'));
    }

    #[Test]
    public function unsafe_or_unregistered_typed_content_is_rejected(): void
    {
        try {
            $this->workflow->createDraft('KU-AD-02', [['type' => 'paragraph', 'body' => '<img src=x onerror=alert(1)>']], config('vs001.required_claim_ids'), actorId: $this->actorId);
            $this->fail('Active lesson content must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Unsafe active lesson content', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->workflow->createDraft('KU-AD-02', [['type' => 'html', 'body' => 'unregistered']], config('vs001.required_claim_ids'), actorId: $this->actorId);
    }

    #[Test]
    public function return_approve_publish_immutability_restore_and_audit_are_enforced(): void
    {
        $draft = $this->workflow->createDraft('KU-AD-02', $this->blocks('Review candidate'), config('vs001.required_claim_ids'), actorId: $this->actorId);
        $draft = $this->workflow->bindAuthorityBaseline($draft->id, 1, WindowsAuthorizationDecisionEngine::AUTHORITY_BASELINE, $this->actorId);
        $this->workflow->submitForReview($draft->id, $this->actorId);
        $returned = $this->workflow->review($draft->id, 'RETURNED', $this->actorId, 'Clarify why ordered explicit deny is decisive.');
        $this->assertSame('draft', $returned->state);
        $this->assertSame('Clarify why ordered explicit deny is decisive.', $returned->review_rationale);

        $this->workflow->submitForReview($draft->id, $this->actorId);
        $reviewed = $this->workflow->review($draft->id, 'APPROVED', $this->actorId);
        $this->assertSame('reviewed', $reviewed->state);
        $published = $this->workflow->publish($draft->id, $this->actorId, config('vs001.required_claim_ids'));
        $this->assertSame('published', $published->state);

        try {
            $published->forceFill(['blocks' => $this->blocks('Forbidden mutation')])->save();
            $this->fail('Published revisions must be immutable.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        $restored = $this->workflow->restoreAsDraft($published->id, $this->actorId);
        $this->assertSame('draft', $restored->state);
        $this->assertSame($published->id, $restored->derived_from_revision_id);
        $this->assertGreaterThan($published->revision, $restored->revision);
        foreach (['lesson.draft.created', 'lesson.authority.bound', 'lesson.review.submitted', 'lesson.review.decided', 'lesson.published'] as $action) {
            $this->assertDatabaseHas('audit_records', ['action' => $action, 'actor_identifier' => $this->actorId]);
        }
    }

    /** @return list<array{type:string,body:string}> */
    private function blocks(string $body): array
    {
        return [['type' => 'paragraph', 'body' => $body]];
    }
}
