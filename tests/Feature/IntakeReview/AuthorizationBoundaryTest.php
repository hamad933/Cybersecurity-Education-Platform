<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function candidate_mutation_rejects_an_actor_outside_the_governed_subject_boundary(): void
    {
        $owner = app(CreateOwner::class)->execute(
            'W04 C01 actor owner',
            'w04-c01-actor@example.test',
            'W04-C01!Pass9',
            (string) Str::uuid7(),
        );
        $otherActorId = (string) Str::uuid7();
        $service = app(EvidenceIntakeService::class);
        $handoff = [
            'source_type' => 'ASSESSMENT_RESULT',
            'source_id' => 'fixture:actor-boundary',
            'source_revision' => '1',
            'source_digest' => hash('sha256', 'actor-boundary'),
            'selected_material_refs' => ['artifact:actor-boundary'],
            'capability_id' => 'CAP-ACTOR',
            'facts' => [['key' => 'synthetic', 'value' => 'true']],
            'metadata' => [],
        ];
        $receipt = app(ProgressEvidenceService::class)
            ->registerSourceHandoffReceipt($otherActorId, $otherActorId, $handoff);

        $candidate = $service->receive($otherActorId, $otherActorId, [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => 'Evidence owned by a different subject actor.',
            'criterion_scope' => ['CRIT-ACTOR'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => 'Actor-boundary fixture',
            'summary' => 'Synthetic actor-boundary fixture.',
        ]);

        $this->expectException(IntakeReviewException::class);
        $this->expectExceptionMessage('outside the governed Evidence subject boundary');
        $service->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
    }
}
