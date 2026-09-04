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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EvidenceIntakeService
{
    public function __construct(
        private readonly CandidateLifecycle $lifecycle,
        private readonly IntakeReviewAuthorizer $authorizer,
        private readonly ProvenanceDigest $digest,
    ) {}

    /** @param array<string, mixed> $payload @return array<string, mixed> */
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
            $this->assertExistingReceiveMatches($existing, $candidate->sourceDigest);
            return $this->candidate((string) $existing->id);
        }

        // Initial facts/metadata are governed by the verified source handoff receipt.
        // Compatibility payload copies are bounded/validated but never authoritative.
        foreach (['facts', 'metadata'] as $field) {
            if (array_key_exists($field, $payload)) {
                $this->boundedJson($payload[$field]);
            }
        }
        $factsJson = $this->canonicalJson($candidate->facts);
        $metadataJson = $this->canonicalJson($candidate->metadata);
        $this->assertJsonBoundary($factsJson, $metadataJson);

        $id = (string) Str::uuid7();
        $now = now();
        try {
            DB::transaction(function () use ($id, $subjectActorId, $submittedBy, $candidate, $semanticDigest, $now, $factsJson, $metadataJson): void {
                $revisionId = (string) Str::uuid7();
                DB::table('evidence_candidates')->insert([
                    'id' => $id,
                    'target_evidence_id' => $candidate->targetEvidenceId,
                    'target_evidence_revision_id' => $candidate->targetEvidenceRevisionId,
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
                    'proposed_facts' => $factsJson,
                    'metadata' => $metadataJson,
                    'preparation_revision' => 1,
                    'state' => CandidateEvidenceState::RECEIVED->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('evidence_candidate_revisions')->insert([
                    'id' => $revisionId,
                    'candidate_id' => $id,
                    'preparation_revision' => 1,
                    'proposed_title' => $candidate->title,
                    'proposed_summary' => $candidate->summary,
                    'proposed_facts' => $factsJson,
                    'metadata' => $metadataJson,
                    'content_digest' => $this->candidateContentDigest($id, $candidate->title, $candidate->summary, $factsJson, $metadataJson),
                    'created_by' => $submittedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->appendCandidateEvent($id, $submittedBy, null, CandidateEvidenceState::RECEIVED->value, 'Candidate Evidence received into governed intake.');
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }
            // Read-after-conflict only; never retry the mutation.
            $winner = DB::table('evidence_candidates')
                ->where('subject_actor_id', $subjectActorId)
                ->where('semantic_identity_digest', $semanticDigest)
                ->first();
            if ($winner === null) {
                throw $exception;
            }
            $this->assertExistingReceiveMatches($winner, $candidate->sourceDigest);
            return $this->candidate((string) $winner->id);
        }

        return $this->candidate($id);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function amendCandidate(string $candidateId, string $actorId, array $payload): array
    {
        return DB::transaction(function () use ($candidateId, $actorId, $payload): array {
            $candidate = DB::table('evidence_candidates')->where('id', $candidateId)->lockForUpdate()->first();
            if ($candidate === null) {
                throw new IntakeReviewException('Candidate Evidence was not found.');
            }
            $this->authorizer->assertSubjectActor((string) $candidate->subject_actor_id, $actorId);
            if ((string) $candidate->state !== CandidateEvidenceState::RECEIVED->value) {
                throw new IntakeReviewException('Cannot amend Candidate Evidence unless it is in RECEIVED state.');
            }

            $currentPrep = DB::table('evidence_candidate_revisions')
                ->where('candidate_id', $candidateId)
                ->where('preparation_revision', (int) $candidate->preparation_revision)
                ->lockForUpdate()
                ->first();
            if ($currentPrep === null) {
                throw new IntakeReviewException('Candidate Evidence missing current prep revision.');
            }

            $proposedTitle = trim((string) ($payload['title'] ?? ''));
            $proposedSummary = trim((string) ($payload['summary'] ?? ''));
            if ($proposedTitle === '' || $proposedSummary === '') {
                throw new IntakeReviewException('Missing Candidate Evidence field: title or summary.');
            }
            if (mb_strlen($proposedTitle) > 180 || mb_strlen($proposedSummary) > 4000) {
                throw new IntakeReviewException('Text exceeds maximum length limits.');
            }

            $factsJson = array_key_exists('facts', $payload)
                ? $this->canonicalJson($this->boundedJson($payload['facts']))
                : $this->canonicalJson($this->decodeJson($currentPrep->proposed_facts));
            $metadataJson = array_key_exists('metadata', $payload)
                ? $this->canonicalJson($this->boundedJson($payload['metadata']))
                : $this->canonicalJson($this->decodeJson($currentPrep->metadata));
            $this->assertJsonBoundary($factsJson, $metadataJson);

            $canonicalContentDigest = $this->candidateContentDigest($candidateId, $proposedTitle, $proposedSummary, $factsJson, $metadataJson);
            if (hash_equals((string) $currentPrep->content_digest, $canonicalContentDigest)) {
                return $this->candidate($candidateId);
            }

            $now = now();
            $newRevisionNumber = ((int) $candidate->preparation_revision) + 1;
            $revisionId = (string) Str::uuid7();
            DB::table('evidence_candidate_revisions')->insert([
                'id' => $revisionId,
                'candidate_id' => $candidateId,
                'preparation_revision' => $newRevisionNumber,
                'proposed_title' => $proposedTitle,
                'proposed_summary' => $proposedSummary,
                'proposed_facts' => $factsJson,
                'metadata' => $metadataJson,
                'content_digest' => $canonicalContentDigest,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'preparation_revision' => $newRevisionNumber,
                'proposed_title' => $proposedTitle,
                'proposed_summary' => $proposedSummary,
                'proposed_facts' => $factsJson,
                'metadata' => $metadataJson,
                'updated_at' => $now,
            ]);
            return $this->candidate($candidateId);
        });
    }

    /** @return array<string, mixed> */
    public function transitionCandidate(string $candidateId, string $actorId, string $targetState, string $reason = ''): array
    {
        if ($targetState === CandidateEvidenceState::ADMITTED->value) {
            throw new IntakeReviewException('Admission must use the governed admitCandidate operation.');
        }
        return DB::transaction(function () use ($candidateId, $actorId, $targetState, $reason): array {
            $candidate = DB::table('evidence_candidates')->where('id', $candidateId)->lockForUpdate()->first();
            if ($candidate === null) {
                throw new IntakeReviewException('Candidate Evidence was not found.');
            }
            $this->authorizer->assertSubjectActor((string) $candidate->subject_actor_id, $actorId);
            if ((string) $candidate->state === $targetState) {
                return $this->candidate($candidateId);
            }
            $this->lifecycle->assertCanTransition((string) $candidate->state, $targetState);
            DB::table('evidence_candidates')->where('id', $candidateId)->update(['state' => $targetState, 'updated_at' => now()]);
            $this->appendCandidateEvent($candidateId, $actorId, (string) $candidate->state, $targetState, $reason !== '' ? $reason : 'Governed Candidate Evidence lifecycle transition.');
            return $this->candidate($candidateId);
        });
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

            if ((string) $candidate->state === CandidateEvidenceState::ADMITTED->value) {
                if ($candidate->admitted_evidence_id === null) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but missing admitted_evidence_id.');
                }
                $admissionCount = EvidenceAdmissionRecord::query()->where('candidate_id', $candidateId)->count();
                if ($admissionCount !== 1) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but does not have exactly one admission record.');
                }
                $admission = EvidenceAdmissionRecord::query()->where('candidate_id', $candidateId)->first();
                if (! $admission) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but missing admission record.');
                }
                if ((string) $candidate->admitted_evidence_id !== (string) $admission->evidence_id) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but evidence_id mismatch in admission.');
                }
                $prepLinkCount = DB::table('evidence_admission_candidate_revisions')->where('admission_id', $admission->id)->count();
                if ($prepLinkCount !== 1) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but does not have exactly one prep link.');
                }
                $prepLink = DB::table('evidence_admission_candidate_revisions')->where('admission_id', $admission->id)->first();
                if (! $prepLink) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but missing admission prep link.');
                }
                $linkedPrep = DB::table('evidence_candidate_revisions')->where('id', $prepLink->candidate_revision_id)->first();
                if (! $linkedPrep || (string) $linkedPrep->candidate_id !== $candidateId) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but prep link points to wrong candidate.');
                }
                $evidence = DB::table('governed_evidence')->where('id', $candidate->admitted_evidence_id)->first();
                if (! $evidence) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but missing admitted evidence.');
                }
                $revision = DB::table('governed_evidence_revisions')->where('id', $admission->evidence_revision_id)->where('evidence_id', $evidence->id)->first();
                if (! $revision) {
                    throw new IntakeReviewException('Candidate Evidence ADMITTED but missing admission revision.');
                }
                return ['candidate' => $this->candidate($candidateId), 'evidence' => (array) $evidence, 'revision' => (array) $revision, 'admission' => $admission->attributesToArray()];
            }

            if ((string) $candidate->state !== CandidateEvidenceState::SUBMITTED_FOR_INTAKE->value) {
                throw new IntakeReviewException('Candidate Evidence must be SUBMITTED_FOR_INTAKE before Admission.');
            }
            $this->lifecycle->assertCanTransition((string) $candidate->state, CandidateEvidenceState::ADMITTED->value);
            $currentPrep = DB::table('evidence_candidate_revisions')->where('candidate_id', $candidateId)->where('preparation_revision', (int) $candidate->preparation_revision)->first();
            if (! $currentPrep) {
                throw new IntakeReviewException('Candidate Evidence missing current prep revision.');
            }

            $now = now();
            $evidenceId = null;
            $revisionId = (string) Str::uuid7();
            $admissionId = (string) Str::uuid7();
            $targetEvidence = null;
            $newRevisionNumber = 1;
            if ($candidate->target_evidence_id !== null && $candidate->target_evidence_revision_id !== null) {
                $targetEvidence = DB::table('governed_evidence')->where('id', $candidate->target_evidence_id)->lockForUpdate()->first();
                if (! $targetEvidence) {
                    throw new IntakeReviewException('Target evidence not found.');
                }
                if ((string) $targetEvidence->subject_actor_id !== (string) $candidate->subject_actor_id) {
                    throw new IntakeReviewException('Cannot append to evidence outside actor boundary.');
                }
                $targetRevision = DB::table('governed_evidence_revisions')->where('evidence_id', $candidate->target_evidence_id)->where('revision', $targetEvidence->current_revision_number)->lockForUpdate()->first();
                if (! $targetRevision) {
                    throw new IntakeReviewException('Target evidence current revision not found.');
                }
                if ((string) $targetRevision->id !== (string) $candidate->target_evidence_revision_id) {
                    throw new IntakeReviewException('Target evidence current revision does not match candidate target revision.');
                }
                $evidenceId = (string) $candidate->target_evidence_id;
                $newRevisionNumber = ((int) $targetEvidence->current_revision_number) + 1;
            } else {
                if (DB::table('governed_evidence')->where('candidate_id', $candidateId)->exists()) {
                    throw new IntakeReviewException('Candidate Evidence has already produced canonical Evidence.');
                }
                $evidenceId = (string) Str::uuid7();
                DB::table('governed_evidence')->insert([
                    'id' => $evidenceId, 'candidate_id' => $candidateId, 'subject_actor_id' => $candidate->subject_actor_id,
                    'capability_id' => $candidate->capability_id, 'evidence_claim' => $candidate->evidence_claim,
                    'governed_purpose' => $candidate->governed_purpose, 'lifecycle_state' => EvidenceLifecycle::ACTIVE->value,
                    'review_status' => ReviewStatus::UNREVIEWED->value, 'effective_review_decision' => 'NONE',
                    'effective_review_decision_id' => null, 'current_revision_number' => 1, 'admitted_by' => $actorId,
                    'admitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $factsJson = $this->canonicalJson($this->decodeJson($currentPrep->proposed_facts));
            $metadataJson = $this->canonicalJson($this->decodeJson($currentPrep->metadata));
            $canonicalContentDigest = $this->candidateContentDigest($candidateId, (string) $currentPrep->proposed_title, (string) $currentPrep->proposed_summary, $factsJson, $metadataJson);
            DB::table('governed_evidence_revisions')->insert([
                'id' => $revisionId, 'evidence_id' => $evidenceId, 'previous_revision_id' => $candidate->target_evidence_revision_id,
                'revision' => $newRevisionNumber, 'title' => $currentPrep->proposed_title, 'summary' => $currentPrep->proposed_summary,
                'facts' => $factsJson, 'selected_material_refs' => $candidate->selected_material_refs, 'criterion_scope' => $candidate->criterion_scope,
                'source_type' => $candidate->source_type, 'source_id' => $candidate->source_id, 'source_revision' => $candidate->source_revision,
                'source_digest' => $candidate->source_digest, 'handoff_receipt_id' => $candidate->handoff_receipt_id,
                'revision_reason' => $targetEvidence === null ? 'INITIAL_ADMISSION' : 'APPEND_ADMISSION',
                'content_digest' => $canonicalContentDigest, 'sealed_by' => $actorId, 'sealed_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($targetEvidence !== null) {
                DB::table('governed_evidence')->where('id', $evidenceId)->update(['current_revision_number' => $newRevisionNumber, 'updated_at' => $now]);
            }
            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => CandidateEvidenceState::ADMITTED->value, 'admitted_evidence_id' => $evidenceId, 'admitted_at' => $now, 'updated_at' => $now,
            ]);
            $this->appendCandidateEvent($candidateId, $actorId, (string) $candidate->state, CandidateEvidenceState::ADMITTED->value, 'Candidate Evidence admitted as canonical Evidence; formal Review has not started.');

            $provenanceDigest = $this->digest->digest([
                'candidate_id' => $candidateId, 'evidence_id' => $evidenceId, 'evidence_revision_id' => $revisionId,
                'subject_actor_id' => (string) $candidate->subject_actor_id, 'source_type' => (string) $candidate->source_type,
                'source_id' => (string) $candidate->source_id, 'source_revision' => (string) $candidate->source_revision,
                'source_digest' => (string) $candidate->source_digest,
            ]);
            EvidenceAdmissionRecord::query()->insert([
                'id' => $admissionId, 'candidate_id' => $candidateId, 'evidence_id' => $evidenceId, 'evidence_revision_id' => $revisionId,
                'admitted_by' => $actorId, 'admitted_at' => $now, 'provenance_digest' => $provenanceDigest,
                'content_digest' => $this->digest->digest(['provenance_digest' => $provenanceDigest, 'admitted_by' => $actorId, 'admitted_at' => $now->toISOString()]),
                'created_at' => $now,
            ]);
            DB::table('evidence_admission_candidate_revisions')->insert(['admission_id' => $admissionId, 'candidate_revision_id' => $currentPrep->id, 'created_at' => $now]);
            $admission = EvidenceAdmissionRecord::query()->where('id', $admissionId)->firstOrFail();
            return [
                'candidate' => $this->candidate($candidateId),
                'evidence' => (array) DB::table('governed_evidence')->where('id', $evidenceId)->firstOrFail(),
                'revision' => (array) DB::table('governed_evidence_revisions')->where('id', $revisionId)->firstOrFail(),
                'admission' => $admission->attributesToArray(),
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
        $result = (array) $candidate;
        foreach (['selected_material_refs', 'criterion_scope', 'proposed_facts', 'metadata'] as $field) {
            $result[$field] = $this->decodeJson($result[$field] ?? null);
        }
        return $result;
    }

    private function appendCandidateEvent(string $candidateId, string $actorId, ?string $fromState, string $toState, string $reason): void
    {
        $sequence = ((int) EvidenceCandidateIntakeEvent::query()->where('candidate_id', $candidateId)->max('sequence')) + 1;
        $occurredAt = now();
        $contentDigest = $this->digest->digest([
            'candidate_id' => $candidateId, 'sequence' => $sequence, 'actor_id' => $actorId,
            'from_state' => $fromState, 'to_state' => $toState, 'reason' => $reason, 'occurred_at' => $occurredAt->toISOString(),
        ]);
        EvidenceCandidateIntakeEvent::query()->insert([
            'id' => (string) Str::uuid7(), 'candidate_id' => $candidateId, 'sequence' => $sequence, 'actor_id' => $actorId,
            'from_state' => $fromState, 'to_state' => $toState, 'reason' => $reason, 'occurred_at' => $occurredAt,
            'content_digest' => $contentDigest, 'created_at' => $occurredAt,
        ]);
    }

    private function assertExistingReceiveMatches(object $existing, string $sourceDigest): void
    {
        if (! hash_equals((string) $existing->source_digest, $sourceDigest)) {
            throw new IntakeReviewException('Candidate Evidence semantic identity conflicts with source integrity.');
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return (string) ($exception->errorInfo[0] ?? $exception->getCode()) === '23505';
    }

    private function candidateContentDigest(string $candidateId, string $title, string $summary, string $factsJson, string $metadataJson): string
    {
        return $this->digest->digest([
            'candidate_id' => $candidateId, 'proposed_title' => $title, 'proposed_summary' => $summary,
            'proposed_facts' => $factsJson, 'metadata' => $metadataJson,
        ]);
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @param array<mixed> $value */
    private function canonicalJson(array $value): string
    {
        return $this->digest->canonicalJson($value);
    }

    /** @return array<mixed> */
    private function boundedJson(mixed $value): array
    {
        if (is_string($value) && strlen($value) > 65536) {
            throw new IntakeReviewException('JSON payload exceeds 64KiB boundary.');
        }
        $decoded = $this->decodeJson($value);
        if (strlen($this->canonicalJson($decoded)) > 65536) {
            throw new IntakeReviewException('JSON payload exceeds 64KiB boundary.');
        }
        return $decoded;
    }

    private function assertJsonBoundary(string $factsJson, string $metadataJson): void
    {
        if (strlen($factsJson) > 65536 || strlen($metadataJson) > 65536) {
            throw new IntakeReviewException('JSON payload exceeds 64KiB boundary.');
        }
    }

    /** @return array<mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value)) {
            throw new IntakeReviewException('Invalid or non-associative JSON.');
        }
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new IntakeReviewException('Invalid or non-associative JSON.');
        }
        return $decoded;
    }
}
