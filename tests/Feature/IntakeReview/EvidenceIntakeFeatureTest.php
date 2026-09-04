<?php

namespace Tests\Feature\IntakeReview;

use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceIntakeFeatureTest extends TestCase
{
    use DatabaseMigrations;

    public function test_receive_uses_verified_receipt_facts_and_metadata_not_payload_overrides(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId, facts: '{"trusted":true,"nested":{"b":2,"a":1}}', metadata: '{"source":"verified","rank":1}');
        $candidate = $this->service()->receive($actorId, $actorId, $this->payload($receiptId, [
            'facts' => '{"trusted":false,"injected":true}',
            'metadata' => '{"source":"payload"}',
        ]));

        $this->assertSame(['nested' => ['a' => 1, 'b' => 2], 'trusted' => true], $candidate['proposed_facts']);
        $this->assertSame(['rank' => 1, 'source' => 'verified'], $candidate['metadata']);
        $revision = DB::table('evidence_candidate_revisions')->where('candidate_id', $candidate['id'])->firstOrFail();
        $this->assertSame('{"nested":{"a":1,"b":2},"trusted":true}', $revision->proposed_facts);
        $this->assertSame('{"rank":1,"source":"verified"}', $revision->metadata);
    }

    public function test_receive_rejects_oversize_or_invalid_receipt_json(): void
    {
        $actorId = (string) Str::uuid7();
        $oversizeReceiptId = $this->insertReceipt($actorId, facts: '{"a":"'.str_repeat('x', 65536).'"}', sourceId: 'oversize');
        try {
            $this->service()->receive($actorId, $actorId, $this->payload($oversizeReceiptId));
            $this->fail('Oversize verified receipt facts must fail closed.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('64KiB', $exception->getMessage());
        }

        $invalidReceiptId = $this->insertReceipt($actorId, facts: '"scalar"', sourceId: 'invalid');
        try {
            $this->service()->receive($actorId, $actorId, $this->payload($invalidReceiptId));
            $this->fail('Scalar receipt JSON must fail closed.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('Invalid or non-associative JSON', $exception->getMessage());
        }
    }

    public function test_receive_payload_json_is_bounded_but_never_authoritative(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId, facts: '{"trusted":1}');
        try {
            $this->service()->receive($actorId, $actorId, $this->payload($receiptId, ['facts' => '{"a":"'.str_repeat('x', 65536).'"}']));
            $this->fail('Oversize compatibility payload must fail closed.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('64KiB', $exception->getMessage());
        }
    }

    public function test_same_content_noop_on_amend_is_json_key_order_independent(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId, facts: '{"b":2,"a":1,"list":[3,2,1]}', metadata: '{"z":9,"a":1}');
        $service = $this->service();
        $candidate = $service->receive($actorId, $actorId, $this->payload($receiptId));
        $this->assertSame(1, DB::table('evidence_candidate_revisions')->where('candidate_id', $candidate['id'])->count());

        $service->amendCandidate($candidate['id'], $actorId, [
            'title' => 'Title',
            'summary' => 'Summary',
            'facts' => '{"list":[3,2,1],"a":1,"b":2}',
            'metadata' => '{"a":1,"z":9}',
        ]);
        $this->assertSame(1, DB::table('evidence_candidate_revisions')->where('candidate_id', $candidate['id'])->count());
    }

    public function test_partial_amend_preserves_omitted_facts_and_metadata(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId, facts: '{"fact":"trusted"}', metadata: '{"meta":"trusted"}');
        $service = $this->service();
        $candidate = $service->receive($actorId, $actorId, $this->payload($receiptId));
        $amended = $service->amendCandidate($candidate['id'], $actorId, ['title' => 'Title', 'summary' => 'Changed summary']);
        $this->assertSame(['fact' => 'trusted'], $amended['proposed_facts']);
        $this->assertSame(['meta' => 'trusted'], $amended['metadata']);
        $this->assertSame(2, $amended['preparation_revision']);
    }

    public function test_invalid_or_oversize_amend_json_fails(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId);
        $service = $this->service();
        $candidate = $service->receive($actorId, $actorId, $this->payload($receiptId));
        try {
            $service->amendCandidate($candidate['id'], $actorId, ['title' => 'Title', 'summary' => 'Summary', 'facts' => '"scalar"']);
            $this->fail('Scalar amend JSON must fail.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('Invalid or non-associative JSON', $exception->getMessage());
        }
        try {
            $service->amendCandidate($candidate['id'], $actorId, ['title' => 'Title', 'summary' => 'Summary', 'metadata' => '{"a":"'.str_repeat('x', 65536).'"}']);
            $this->fail('Oversize amend JSON must fail.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('64KiB', $exception->getMessage());
        }
    }

    public function test_target_pair_requires_both(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId);
        try {
            $this->service()->receive($actorId, $actorId, $this->payload($receiptId, ['target_evidence_id' => (string) Str::uuid7()]));
            $this->fail('Incomplete target pair must fail.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('Target evidence ID and revision ID must be provided together.', $exception->getMessage());
        }
    }

    public function test_lifecycle_transition_noop_and_direct_admitted_transition_rejected(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId = $this->insertReceipt($actorId);
        $service = $this->service();
        $candidate = $service->receive($actorId, $actorId, $this->payload($receiptId));
        $events = DB::table('evidence_candidate_intake_events')->where('candidate_id', $candidate['id'])->count();
        $service->transitionCandidate($candidate['id'], $actorId, CandidateEvidenceState::RECEIVED->value);
        $this->assertSame($events, DB::table('evidence_candidate_intake_events')->where('candidate_id', $candidate['id'])->count());
        try {
            $service->transitionCandidate($candidate['id'], $actorId, CandidateEvidenceState::ADMITTED->value);
            $this->fail('Admission must require admitCandidate.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('Admission must use the governed admitCandidate operation', $exception->getMessage());
        }
    }

    public function test_target_pair_valid_append_has_explicit_revision_reason(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId1 = $this->insertReceipt($actorId, facts: '{"v":1}', sourceId: 'base', sourceDigest: str_repeat('a', 64));
        $receiptId2 = $this->insertReceipt($actorId, facts: '{"v":2}', sourceId: 'append', sourceDigest: str_repeat('c', 64));
        $service = $this->service();
        $base = $service->receive($actorId, $actorId, $this->payload($receiptId1, ['evidence_claim' => 'base']));
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedBase = $service->admitCandidate($base['id'], $actorId);
        $this->assertSame('INITIAL_ADMISSION', $admittedBase['revision']['revision_reason']);

        $append = $service->receive($actorId, $actorId, $this->payload($receiptId2, [
            'evidence_claim' => 'append',
            'target_evidence_id' => $admittedBase['evidence']['id'],
            'target_evidence_revision_id' => $admittedBase['revision']['id'],
        ]));
        $service->transitionCandidate($append['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($append['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedAppend = $service->admitCandidate($append['id'], $actorId);
        $this->assertSame(2, (int) $admittedAppend['evidence']['current_revision_number']);
        $this->assertSame($admittedBase['revision']['id'], $admittedAppend['revision']['previous_revision_id']);
        $this->assertSame('APPEND_ADMISSION', $admittedAppend['revision']['revision_reason']);
        $this->cleanupAppendAdmission($admittedAppend['admission']['id']);
    }

    public function test_target_pair_stale_rejection(): void
    {
        $actorId = (string) Str::uuid7();
        $receiptId1 = $this->insertReceipt($actorId, sourceId: 's1', sourceDigest: str_repeat('a', 64));
        $receiptId2 = $this->insertReceipt($actorId, sourceId: 's2', sourceDigest: str_repeat('c', 64));
        $receiptId3 = $this->insertReceipt($actorId, sourceId: 's3', sourceDigest: str_repeat('e', 64));
        $service = $this->service();
        $base = $service->receive($actorId, $actorId, $this->payload($receiptId1, ['evidence_claim' => 'base']));
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($base['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedBase = $service->admitCandidate($base['id'], $actorId);

        $append = $service->receive($actorId, $actorId, $this->payload($receiptId2, [
            'evidence_claim' => 'append', 'target_evidence_id' => $admittedBase['evidence']['id'], 'target_evidence_revision_id' => $admittedBase['revision']['id'],
        ]));
        $service->transitionCandidate($append['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($append['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        $admittedAppend = $service->admitCandidate($append['id'], $actorId);

        $stale = $service->receive($actorId, $actorId, $this->payload($receiptId3, [
            'evidence_claim' => 'stale', 'target_evidence_id' => $admittedBase['evidence']['id'], 'target_evidence_revision_id' => $admittedBase['revision']['id'],
        ]));
        $service->transitionCandidate($stale['id'], $actorId, CandidateEvidenceState::PREPARED->value);
        $service->transitionCandidate($stale['id'], $actorId, CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value);
        try {
            $service->admitCandidate($stale['id'], $actorId);
            $this->fail('Stale target must be rejected.');
        } catch (IntakeReviewException $exception) {
            $this->assertStringContainsString('does not match candidate target revision', $exception->getMessage());
        }
        $this->cleanupAppendAdmission($admittedAppend['admission']['id']);
    }

    private function service(): EvidenceIntakeService
    {
        return $this->app->make(EvidenceIntakeService::class);
    }

    /** @param array<string,mixed> $overrides */
    private function payload(string $receiptId, array $overrides = []): array
    {
        return array_merge([
            'handoff_receipt_id' => $receiptId,
            'evidence_claim' => 'claim',
            'governed_purpose' => 'GOVERNED_PROVENANCE_ATTESTATION',
            'title' => 'Title',
            'summary' => 'Summary',
        ], $overrides);
    }

    private function insertReceipt(string $actorId, string $facts = '{}', string $metadata = '{}', string $sourceId = 'test-source', ?string $sourceDigest = null): string
    {
        $receiptId = (string) Str::uuid7();
        DB::table('evidence_source_handoff_receipts')->insert([
            'id' => $receiptId, 'subject_actor_id' => $actorId, 'registered_by' => $actorId,
            'source_type' => 'test_type', 'source_id' => $sourceId, 'source_revision' => '1',
            'source_digest' => $sourceDigest ?? str_repeat('a', 64), 'selected_material_refs' => '[]',
            'capability_id' => (string) Str::uuid7(), 'facts' => $facts, 'metadata' => $metadata,
            'receipt_digest' => str_repeat('b', 64), 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $receiptId;
    }

    private function cleanupAppendAdmission(string $admissionId): void
    {
        DB::statement('ALTER TABLE evidence_admission_records DISABLE TRIGGER evidence_admission_records_immutable');
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions DISABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::table('evidence_admission_candidate_revisions')->where('admission_id', $admissionId)->delete();
        DB::table('evidence_admission_records')->where('id', $admissionId)->delete();
        DB::statement('ALTER TABLE evidence_admission_candidate_revisions ENABLE TRIGGER trg_evidence_admission_candidate_revisions_immutable');
        DB::statement('ALTER TABLE evidence_admission_records ENABLE TRIGGER evidence_admission_records_immutable');
    }
}
