<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CandidateLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function candidate_lifecycle_is_governed_and_admission_stops_before_review_or_mastery(): void
    {
        $owner = $this->owner();
        $service = app(EvidenceIntakeService::class);
        $candidate = $service->receive($owner->id, $owner->id, $this->handoff('candidate-lifecycle'));
        $this->assertSame('RECEIVED', $candidate['state']);

        $candidate = $service->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $this->assertSame('PREPARED', $candidate['state']);
        $candidate = $service->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');
        $this->assertSame('SUBMITTED_FOR_INTAKE', $candidate['state']);

        $admitted = $service->admitCandidate($candidate['id'], $owner->id);
        $this->assertSame('ADMITTED', $admitted['candidate']['state']);
        $this->assertSame('ACTIVE', $admitted['evidence']['lifecycle_state']);
        $this->assertSame('UNREVIEWED', $admitted['evidence']['review_status']);
        $this->assertSame('NONE', $admitted['evidence']['effective_review_decision']);
        $this->assertDatabaseCount('evidence_review_requests', 0);
        $this->assertDatabaseCount('evidence_review_decisions', 0);
        $this->assertDatabaseCount('evidence_mastery_states', 0);

        $timeline = app(\App\Modules\Evidence\IntakeReview\Application\IntakeReviewReadModel::class)
            ->candidateTimeline($candidate['id']);
        $this->assertSame(
            ['RECEIVED', 'PREPARED', 'SUBMITTED_FOR_INTAKE', 'ADMITTED'],
            array_column($timeline, 'to_state'),
        );
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'W04 C01 owner',
            'w04-c01-lifecycle@example.test',
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
            'capability_id' => 'CAP-APPSEC-INPUT',
            'evidence_claim' => 'The subject demonstrated controlled input-validation reasoning.',
            'criterion_scope' => ['CRIT-INPUT-VALIDATION'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => 'Governed Evidence fixture',
            'summary' => 'Synthetic W04-C01 Candidate Evidence fixture.',
            'facts' => ['fixture' => $key],
            'metadata' => ['synthetic' => true],
        ];
    }
}
