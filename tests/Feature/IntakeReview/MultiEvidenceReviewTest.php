<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MultiEvidenceReviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function one_formal_review_request_can_pin_multiple_canonical_evidence_items_for_one_subject(): void
    {
        $owner = $this->owner();
        $first = $this->admit($owner, 'multi-review-a');
        $second = $this->admit($owner, 'multi-review-b');
        $service = app(EvidenceReviewService::class);

        $request = $service->requestReview([
            ['evidence_id' => $first['evidence']['id'], 'evidence_revision_id' => $first['revision']['id']],
            ['evidence_id' => $second['evidence']['id'], 'evidence_revision_id' => $second['revision']['id']],
        ], $owner->id, 'CAP-APPSEC-REVIEW', ['CRIT-A', 'CRIT-B'], 'Formal multi-Evidence review.', $owner->id);

        $this->assertDatabaseCount('evidence_review_requests', 1);
        $this->assertDatabaseCount('evidence_review_scope_items', 2);
        $this->assertDatabaseHas('governed_evidence', ['id' => $first['evidence']['id'], 'review_status' => 'IN_REVIEW']);
        $this->assertDatabaseHas('governed_evidence', ['id' => $second['evidence']['id'], 'review_status' => 'IN_REVIEW']);

        $review = $service->startReview($request['id'], $owner->id);
        $this->assertSame($request['id'], $review['review_request_id']);
        $this->assertSame('IN_REVIEW', $review['status']);
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
            'W04 C01 review owner',
            'w04-c01-review@example.test',
            'W04-C01!Pass9',
            (string) Str::uuid7(),
        );
    }

    /** @return array<string, mixed> */
    private function handoff(string $key): array
    {
        return [
            'source_type' => 'ASSESSMENT_RESULT',
            'source_id' => "fixture:{$key}",
            'source_revision' => '1',
            'source_digest' => hash('sha256', $key),
            'selected_material_refs' => ["artifact:{$key}"],
            'capability_id' => 'CAP-APPSEC-REVIEW',
            'evidence_claim' => "Governed claim {$key}.",
            'criterion_scope' => ['CRIT-A', 'CRIT-B'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => "Evidence {$key}",
            'summary' => 'Synthetic formal-review fixture.',
            'facts' => ['fixture' => $key],
            'metadata' => ['synthetic' => true],
        ];
    }
}
