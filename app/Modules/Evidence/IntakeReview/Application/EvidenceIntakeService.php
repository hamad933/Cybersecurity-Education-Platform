<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState;
use App\Modules\Evidence\IntakeReview\Domain\EvidenceLifecycle;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewAuthorizer;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\IntakeReview\Domain\ReviewStatus;
use App\Modules\Evidence\Models\EvidenceAdmissionRecord;
use App\Modules\Evidence\Models\EvidenceCandidateIntakeEvent;
use App\Modules\Evidence\Models\EvidenceSourceHandoffReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EvidenceIntakeService
{
    public function __construct(
        private readonly CandidateLifecycle $lifecycle,
        private readonly IntakeReviewAuthorizer $authorizer,
        private readonly ProvenanceDigest $digest,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function receive(string $subjectActorId, string $submittedBy, array $payload): array
    {
        $this->authorizer->assertSubjectActor($subjectActorId, $submittedBy);

        if (empty($payload['handoff_receipt_id'])) {
            throw new IntakeReviewException('Missing Handoff Receipt ID.');
        }

        $receipt = EvidenceSourceHandoffReceipt::query()->where('id', $payload['handoff_receipt_id'])->first();
        if (! $receipt || $receipt->subject_actor_id !== $subjectActorId) {
            throw new IntakeReviewException('Verified source Handoff receipt is outside the actor boundary.');
        }

        $candidate = IntakeCandidateData::fromArray($payload, $receipt);
        $semanticDigest = $candidate->semanticIdentityDigest($this->digest, $subjectActorId);

        $existing = DB::table('evidence_candidates')
            ->where('subject_actor_id', $subjectActorId)
            ->where('semantic_identity_digest', $semanticDigest)
            ->first();

        if ($existing !== null) {
            if (! hash_equals((string) $existing->source_digest, $candidate->sourceDigest)) {
                throw new IntakeReviewException('Candidate Evidence semantic identity conflicts with source integrity.');
            }

            return (array) $existing;
        }

        $id = (string) Str::uuid7();
        $now = now();

        DB::transaction(function () use ($id, $subjectActorId, $submittedBy, $candidate, $semanticDigest, $now): void {
            DB::table('evidence_candidates')->insert([
                'id' => $id,
                'handoff_receipt_id' => $candidate->handoffReceiptId,
                'subject_actor_id' => $subjectActorId,
                'submitted_by' => $submittedBy,
                'source_type' => $candidate->sourceType,
                'source_id' => $candidate->sourceId,
                'source_revision' => $candidate->sourceRevision,
                'source_digest' => $candidate->sourceDigest,
                'selected_material_refs' => $this->json($candidate->selectedMaterialRefs),
                'capability_id' => $candidate->capabilityId,
                'evidence_claim' => $candidate->evidenceClaim,
                'criterion_scope' => $this->json($candidate->criterionScope),
                'governed_purpose' => $candidate->governedPurpose,
                'semantic_identity_digest' => $semanticDigest,
                'proposed_title' => $candidate->title,
                'proposed_summary' => $candidate->summary,
                'proposed_facts' => $this->json($candidate->facts),
                'metadata' => $this->json($candidate->metadata),
                'state' => CandidateEvidenceState::RECEIVED->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->appendCandidateEvent(
                $id,
                $submittedBy,
                null,
                CandidateEvidenceState::RECEIVED->value,
                'Candidate Evidence received into governed intake.',
            );
        });

        return $this->candidate($id);
    }

    /** @return array<string, mixed> */
    public function transitionCandidate(string $candidateId, string $actorId, string $targetState, string $reason = ''): array
    {
        if ($targetState === CandidateEvidenceState::ADMITTED->value) {
            throw new IntakeReviewException('Admission must use the governed admitCandidate operation.');
        }

        DB::transaction(function () use ($candidateId, $actorId, $targetState, $reason): void {
            $candidate = DB::table('evidence_candidates')->where('id', $candidateId)->lockForUpdate()->first();

            if ($candidate === null) {
                throw new IntakeReviewException('Candidate Evidence was not found.');
            }

            $this->authorizer->assertSubjectActor((string) $candidate->subject_actor_id, $actorId);
            $this->lifecycle->assertCanTransition((string) $candidate->state, $targetState);

            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => $targetState,
                'updated_at' => now(),
            ]);

            $this->appendCandidateEvent(
                $candidateId,
                $actorId,
                (string) $candidate->state,
                $targetState,
                $reason !== '' ? $reason : 'Governed Candidate Evidence lifecycle transition.',
            );
        });

        return $this->candidate($candidateId);
    }

    /** @return array{candidate:array<string,mixed>,evidence:array<string,mixed>,revision:array<string,mixed>,admission:array<string,mixed>} */
    public function admitCandidate(string $candidateId, string $actorId): array
    {
        return DB::transaction(function () use ($candidateId, $actorId): array {
            $candidate = DB::table('evidence_candidates')->where('id', $candidateId)->lockForUpdate()->first();

            if ($candidate === null) {
                throw new IntakeReviewException('Candidate Evidence was not found.');
            }

            $this->authorizer->assertSubjectActor((string) $candidate->subject_actor_id, $actorId);
            $this->lifecycle->assertCanTransition(
                (string) $candidate->state,
                CandidateEvidenceState::ADMITTED->value,
            );

            if (DB::table('governed_evidence')->where('candidate_id', $candidateId)->exists()) {
                throw new IntakeReviewException('Candidate Evidence has already produced canonical Evidence.');
            }

            $evidenceId = (string) Str::uuid7();
            $revisionId = (string) Str::uuid7();
            $admissionId = (string) Str::uuid7();
            $now = now();

            DB::table('governed_evidence')->insert([
                'id' => $evidenceId,
                'candidate_id' => $candidateId,
                'subject_actor_id' => $candidate->subject_actor_id,
                'capability_id' => $candidate->capability_id,
                'evidence_claim' => $candidate->evidence_claim,
                'governed_purpose' => $candidate->governed_purpose,
                'lifecycle_state' => EvidenceLifecycle::ACTIVE->value,
                'review_status' => ReviewStatus::UNREVIEWED->value,
                'effective_review_decision' => 'NONE',
                'effective_review_decision_id' => null,
                'current_revision_number' => 1,
                'admitted_by' => $actorId,
                'admitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $revisionPayload = [
                'evidence_id' => $evidenceId,
                'previous_revision_id' => null,
                'revision' => 1,
                'title' => $candidate->proposed_title,
                'summary' => $candidate->proposed_summary,
                'facts' => $this->decodeJson($candidate->proposed_facts),
                'selected_material_refs' => $this->decodeJson($candidate->selected_material_refs),
                'criterion_scope' => $this->decodeJson($candidate->criterion_scope),
                'source_type' => $candidate->source_type,
                'source_id' => $candidate->source_id,
                'source_revision' => $candidate->source_revision,
                'source_digest' => $candidate->source_digest,
                'revision_reason' => 'INITIAL_ADMISSION',
                'sealed_by' => $actorId,
                'sealed_at' => $now->toISOString(),
            ];
            $contentDigest = $this->digest->digest($revisionPayload);

            DB::table('governed_evidence_revisions')->insert([
                'id' => $revisionId,
                'evidence_id' => $evidenceId,
                'previous_revision_id' => null,
                'revision' => 1,
                'title' => $candidate->proposed_title,
                'summary' => $candidate->proposed_summary,
                'facts' => $candidate->proposed_facts,
                'selected_material_refs' => $candidate->selected_material_refs,
                'criterion_scope' => $candidate->criterion_scope,
                'source_type' => $candidate->source_type,
                'source_id' => $candidate->source_id,
                'source_revision' => $candidate->source_revision,
                'source_digest' => $candidate->source_digest,
                'revision_reason' => 'INITIAL_ADMISSION',
                'content_digest' => $contentDigest,
                'sealed_by' => $actorId,
                'sealed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => CandidateEvidenceState::ADMITTED->value,
                'admitted_evidence_id' => $evidenceId,
                'admitted_at' => $now,
                'updated_at' => $now,
            ]);

            $this->appendCandidateEvent(
                $candidateId,
                $actorId,
                (string) $candidate->state,
                CandidateEvidenceState::ADMITTED->value,
                'Candidate Evidence admitted as canonical Evidence; formal Review has not started.',
            );

            $provenanceDigest = $this->digest->digest([
                'candidate_id' => $candidateId,
                'evidence_id' => $evidenceId,
                'evidence_revision_id' => $revisionId,
                'subject_actor_id' => (string) $candidate->subject_actor_id,
                'source_type' => (string) $candidate->source_type,
                'source_id' => (string) $candidate->source_id,
                'source_revision' => (string) $candidate->source_revision,
                'source_digest' => (string) $candidate->source_digest,
            ]);

            EvidenceAdmissionRecord::query()->insert([
                'id' => $admissionId,
                'candidate_id' => $candidateId,
                'evidence_id' => $evidenceId,
                'evidence_revision_id' => $revisionId,
                'admitted_by' => $actorId,
                'admitted_at' => $now,
                'provenance_digest' => $provenanceDigest,
                'content_digest' => $this->digest->digest([
                    'provenance_digest' => $provenanceDigest,
                    'admitted_by' => $actorId,
                    'admitted_at' => $now->toISOString(),
                ]),
                'created_at' => $now,
            ]);

            return [
                'candidate' => $this->candidate($candidateId),
                'evidence' => (array) DB::table('governed_evidence')->where('id', $evidenceId)->firstOrFail(),
                'revision' => (array) DB::table('governed_evidence_revisions')->where('id', $revisionId)->firstOrFail(),
                'admission' => (array) EvidenceAdmissionRecord::query()->where('id', $admissionId)->firstOrFail(),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function candidate(string $candidateId): array
    {
        $candidate = DB::table('evidence_candidates')->where('id', $candidateId)->first();

        if ($candidate === null) {
            throw new IntakeReviewException('Candidate Evidence was not found.');
        }

        return (array) $candidate;
    }

    private function appendCandidateEvent(
        string $candidateId,
        string $actorId,
        ?string $fromState,
        string $toState,
        string $reason,
    ): void {
        $sequence = ((int) EvidenceCandidateIntakeEvent::query()
            ->where('candidate_id', $candidateId)
            ->max('sequence')) + 1;
        $occurredAt = now();
        $contentDigest = $this->digest->digest([
            'candidate_id' => $candidateId,
            'sequence' => $sequence,
            'actor_id' => $actorId,
            'from_state' => $fromState,
            'to_state' => $toState,
            'reason' => $reason,
            'occurred_at' => $occurredAt->toISOString(),
        ]);

        EvidenceCandidateIntakeEvent::query()->insert([
            'id' => (string) Str::uuid7(),
            'candidate_id' => $candidateId,
            'sequence' => $sequence,
            'actor_id' => $actorId,
            'from_state' => $fromState,
            'to_state' => $toState,
            'reason' => $reason,
            'occurred_at' => $occurredAt,
            'content_digest' => $contentDigest,
            'created_at' => $occurredAt,
        ]);
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
