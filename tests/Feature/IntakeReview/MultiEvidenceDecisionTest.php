<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\Evidence\IntakeReview\Application\IntakeReviewReadModel;
use App\Modules\Evidence\IntakeReview\Application\ReviewDecisionService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MultiEvidenceDecisionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function one_review_decision_preserves_exact_multi_evidence_items_through_the_decision_relation(): void
    {
        $owner = $this->owner();
        $first = $this->admit($owner, 'decision-a');
        $second = $this->admit($owner, 'decision-b');
        $reviewService = app(EvidenceReviewService::class);

        $request = $reviewService->requestReview([
            ['evidence_id' => $first['evidence']['id'], 'evidence_revision_id' => $first['revision']['id']],
            ['evidence_id' => $second['evidence']['id'], 'evidence_revision_id' => $second['revision']['id']],
        ], $owner->id, 'CAP-DECISION', ['CRIT-1'], 'Multi-Evidence decision fixture.', $owner->id);
        $review = $reviewService->startReview($request['id'], $owner->id);
        $reviewService->recordFinding(
            $review['id'],
            $owner->id,
            'CRIT-1',
            'SATISFIED',
            'Both exact sealed Evidence Revisions support the formal finding.',
            [$first['revision']['id'], $second['revision']['id']],
        );

        $decision = app(ReviewDecisionService::class)->recordDecision(
            $review['id'],
            $owner->id,
            'ACCEPT',
            'The formal decision applies to the exact two Evidence items in scope.',
        );

        $this->assertDatabaseCount('evidence_review_decisions', 1);
        $this->assertDatabaseCount('evidence_review_decision_items', 2);
        $this->assertDatabaseHas('evidence_review_decision_items', [
            'decision_id' => $decision['id'],
            'evidence_id' => $first['evidence']['id'],
            'evidence_revision_id' => $first['revision']['id'],
        ]);
        $this->assertDatabaseHas('evidence_review_decision_items', [
            'decision_id' => $decision['id'],
            'evidence_id' => $second['evidence']['id'],
            'evidence_revision_id' => $second['revision']['id'],
        ]);
        $this->assertCount(2, app(IntakeReviewReadModel::class)->reviewDecision($decision['id'])['items']);
        $this->assertDatabaseHas('governed_evidence', [
            'id' => $first['evidence']['id'],
            'review_status' => 'REVIEWED',
            'effective_review_decision' => 'ACCEPT',
        ]);
        $this->assertDatabaseHas('governed_evidence', [
            'id' => $second['evidence']['id'],
            'review_status' => 'REVIEWED',
            'effective_review_decision' => 'ACCEPT',
        ]);
    }

    /** @return array{evidence:array<string,mixed>,revision:array<string,mixed>} */
    private function admit(OwnerAccount $owner, string $key): array
    {
        $service = app(EvidenceIntakeService::class);
        $candidate = $service->receive($owner->id, $owner->id, $this->handoff($key));
        $service->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $service->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');

        return $service->admitCandidate($candidate['id'], $owner->id);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'W04 C01 decision owner',
            'w04-c01-decision@example.test',
            'W04-C01!Pass9',
            (string) Str::uuid7(),
        );
    }

    /** @return array<string, mixed> */
    private function handoff(string $key): array
    {
        $owner = OwnerAccount::first() ?? $this->owner();
        $handoff = [
            'source_type' => 'RUN_RESULT',
            'source_id' => "fixture:{$key}",
            'source_revision' => '1',
            'source_digest' => hash('sha256', $key),
            'selected_material_refs' => ["artifact:{$key}"],
            'capability_id' => 'CAP-DECISION',
            'facts' => ['fixture' => $key],
            'metadata' => ['synthetic' => true],
        ];
        $receipt = app(ProgressEvidenceService::class)
            ->registerSourceHandoffReceipt($owner->id, $owner->id, $handoff);

        return [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => "Governed formal-decision claim {$key}.",
            'criterion_scope' => ['CRIT-1'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => "Decision evidence {$key}",
            'summary' => 'Synthetic decision fixture.',
        ];
    }
}
