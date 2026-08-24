<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EvidenceAdmissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admission_creates_one_canonical_evidence_revision_and_immutable_provenance_record(): void
    {
        $owner = $this->owner();
        $service = app(EvidenceIntakeService::class);
        $candidate = $service->receive($owner->id, $owner->id, $this->handoff('admission'));
        $service->transitionCandidate($candidate['id'], $owner->id, 'PREPARED');
        $service->transitionCandidate($candidate['id'], $owner->id, 'SUBMITTED_FOR_INTAKE');
        $bundle = $service->admitCandidate($candidate['id'], $owner->id);

        $this->assertSame($candidate['id'], $bundle['evidence']['candidate_id']);
        $this->assertSame($bundle['evidence']['id'], $bundle['revision']['evidence_id']);
        $this->assertSame(1, (int) $bundle['revision']['revision']);
        $this->assertSame('INITIAL_ADMISSION', $bundle['revision']['revision_reason']);
        $this->assertSame($bundle['revision']['id'], $bundle['admission']['evidence_revision_id']);
        $this->assertDatabaseCount('governed_evidence', 1);
        $this->assertDatabaseCount('governed_evidence_revisions', 1);
        $this->assertDatabaseCount('evidence_admission_records', 1);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'W04 C01 admission owner',
            'w04-c01-admission@example.test',
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
            'selected_material_refs' => ["artifact:{$key}:one", "artifact:{$key}:two"],
            'capability_id' => 'CAP-IR-001',
            'facts' => [['key' => 'fixture', 'value' => $key]],
            'metadata' => ['synthetic' => true],
        ];
        $receipt = app(ProgressEvidenceService::class)
            ->registerSourceHandoffReceipt($owner->id, $owner->id, $handoff);

        return [
            'handoff_receipt_id' => $receipt['id'],
            'evidence_claim' => 'The subject demonstrated an investigation capability with pinned source truth.',
            'criterion_scope' => ['CRIT-IR-CHAIN'],
            'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
            'title' => 'Admission fixture',
            'summary' => 'Synthetic admission provenance fixture.',
        ];
    }
}
