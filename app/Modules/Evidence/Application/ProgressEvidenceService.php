<?php

namespace App\Modules\Evidence\Application;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class ProgressEvidenceService
{
    private const GOVERNED_PURPOSES = [
        'FORMAL_CAPABILITY_EVIDENCE' => true,
        'GOVERNED_PROVENANCE_ATTESTATION' => false,
    ];

    private const CANDIDATE_TRANSITIONS = [
        'RECEIVED' => ['DRAFT', 'PREPARED', 'WITHDRAWN'],
        'DRAFT' => ['PREPARED', 'WITHDRAWN'],
        'PREPARED' => ['SUBMITTED_FOR_INTAKE', 'WITHDRAWN'],
        'SUBMITTED_FOR_INTAKE' => ['RETURNED_FOR_CONTEXT', 'DECLINED', 'WITHDRAWN'],
        'RETURNED_FOR_CONTEXT' => ['PREPARED', 'DECLINED', 'WITHDRAWN'],
        'ADMITTED' => [],
        'DECLINED' => [],
        'WITHDRAWN' => [],
    ];

    private const FINDINGS = [
        'SATISFIED',
        'PARTIALLY_SATISFIED',
        'NOT_SATISFIED',
        'NOT_ASSESSABLE',
    ];

    private const DECISIONS = [
        'ACCEPT',
        'ACCEPT_WITH_LIMITATIONS',
        'MORE_EVIDENCE_REQUIRED',
        'REJECT',
    ];

    private const MASTERY_QUALIFYING_DECISIONS = [
        'ACCEPT',
        'ACCEPT_WITH_LIMITATIONS',
    ];

    private const JUDGMENTS = [
        'NOT_EVALUATED',
        'INSUFFICIENT_EVIDENCE',
        'INCONCLUSIVE',
        'NOT_MASTERED',
        'MASTERED',
    ];

    private const FRESHNESS = ['CURRENT', 'REVALIDATION_REQUIRED'];

    /**
     * Trusted application boundary for source-domain Handoff/Submission producers.
     * This method intentionally has no HTTP route.
     *
     * @param  array<string, mixed>  $handoff
     * @return array<string, mixed>
     */
    public function registerSourceHandoffReceipt(string $subjectId, string $registeredBy, array $handoff): array
    {
        foreach ([
            'source_type',
            'source_id',
            'source_revision',
            'source_digest',
            'selected_material_refs',
            'capability_id',
        ] as $key) {
            if (! array_key_exists($key, $handoff) || (is_string($handoff[$key]) && trim($handoff[$key]) === '')) {
                throw new InvalidArgumentException("Missing source handoff field: {$key}.");
            }
        }

        $sourceType = $this->text((string) $handoff['source_type'], 64, 'Source type');
        $sourceId = $this->text((string) $handoff['source_id'], 160, 'Source ID');
        $sourceRevision = $this->text((string) $handoff['source_revision'], 80, 'Source revision');
        $sourceDigest = $this->sourceDigest((string) $handoff['source_digest']);
        $materials = $this->stringList($handoff['selected_material_refs'] ?? null, 'selected material references', true);
        $capabilityId = $this->text((string) $handoff['capability_id'], 100, 'Capability ID');
        $facts = $this->boundedFacts($handoff['facts'] ?? []);
        $metadata = $this->boundedMetadata($handoff['metadata'] ?? []);

        $conflict = DB::table('evidence_source_handoff_receipts')
            ->where('subject_actor_id', $subjectId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('source_revision', $sourceRevision)
            ->where('source_digest', '!=', $sourceDigest)
            ->exists();
        if ($conflict) {
            throw new LogicException('Source Handoff revision conflicts with an immutable source integrity digest.');
        }

        $body = [
            'subject_actor_id' => $subjectId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_revision' => $sourceRevision,
            'source_digest' => $sourceDigest,
            'selected_material_refs' => $materials,
            'capability_id' => $capabilityId,
            'facts' => $facts,
            'metadata' => $metadata,
        ];
        $receiptDigest = $this->digest($body);
        $existing = DB::table('evidence_source_handoff_receipts')
            ->where('subject_actor_id', $subjectId)
            ->where('receipt_digest', $receiptDigest)
            ->first();
        if ($existing) {
            return $this->array($existing, ['selected_material_refs', 'facts', 'metadata']);
        }

        $id = (string) Str::uuid7();
        $now = now();
        DB::table('evidence_source_handoff_receipts')->insert([
            ...$body,
            'id' => $id,
            'registered_by' => $registeredBy,
            'selected_material_refs' => $this->json($materials),
            'facts' => $this->json($facts),
            'metadata' => $this->json($metadata),
            'receipt_digest' => $receiptDigest,
            'registered_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('evidence_source_handoff_receipts', $id, ['selected_material_refs', 'facts', 'metadata']);
    }

    /** @return array<string, mixed> */
    public function registerMasteryPolicyRevision(
        string $policyKey,
        int $revision,
        array $qualifyingDecisions,
        string $approvedBy,
    ): array {
        $policyKey = $this->text($policyKey, 100, 'Mastery Policy key');
        if ($revision < 1) {
            throw new InvalidArgumentException('Mastery Policy revision must be positive.');
        }
        $qualifying = $this->stringList($qualifyingDecisions, 'qualifying Review Decisions', true, 2, 40);
        foreach ($qualifying as $decision) {
            if (! in_array($decision, self::MASTERY_QUALIFYING_DECISIONS, true)) {
                throw new InvalidArgumentException('Mastery Policy contains a non-qualifying Review Decision outcome.');
            }
        }

        $body = [
            'policy_key' => $policyKey,
            'revision' => $revision,
            'qualifying_review_decisions' => $qualifying,
            'state' => 'APPROVED',
        ];
        $digest = $this->digest($body);
        $existing = DB::table('evidence_mastery_policy_revisions')
            ->where('policy_key', $policyKey)
            ->where('revision', $revision)
            ->first();
        if ($existing) {
            if ($existing->content_digest !== $digest) {
                throw new LogicException('Mastery Policy Revision already exists with different governed content.');
            }

            return $this->array($existing, ['qualifying_review_decisions']);
        }

        $id = (string) Str::uuid7();
        $now = now();
        DB::table('evidence_mastery_policy_revisions')->insert([
            ...$body,
            'id' => $id,
            'qualifying_review_decisions' => $this->json($qualifying),
            'content_digest' => $digest,
            'approved_by' => $approvedBy,
            'approved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('evidence_mastery_policy_revisions', $id, ['qualifying_review_decisions']);
    }

    /**
     * @param  array<string, mixed>  $candidateInput
     * @return array<string, mixed>
     */
    public function intakeCandidate(
        string $subjectId,
        string $submittedBy,
        string $handoffReceiptId,
        array $candidateInput,
    ): array {
        $receipt = DB::table('evidence_source_handoff_receipts')->where('id', $handoffReceiptId)->first();
        if (! $receipt || $receipt->subject_actor_id !== $subjectId) {
            throw new LogicException('Verified source Handoff receipt is outside the actor boundary.');
        }

        $claim = $this->text((string) ($candidateInput['evidence_claim'] ?? ''), 4000, 'Evidence claim');
        $criteria = $this->stringList($candidateInput['criterion_scope'] ?? [], 'criterion scope', false, 50, 120);
        $purpose = $this->governedPurpose((string) ($candidateInput['governed_purpose'] ?? ''), $criteria);
        $title = $this->text((string) ($candidateInput['title'] ?? ''), 180, 'Title');
        $summary = $this->text((string) ($candidateInput['summary'] ?? ''), 4000, 'Summary');
        $materials = $this->decode($receipt->selected_material_refs);
        $facts = $this->decode($receipt->facts);
        $metadata = $this->decode($receipt->metadata);

        $semanticIdentity = $this->digest([
            'subject_actor_id' => $subjectId,
            'source_type' => $this->identityText($receipt->source_type),
            'source_id' => $this->identityText($receipt->source_id),
            'source_revision' => $this->identityText($receipt->source_revision),
            'selected_material_refs' => $materials,
            'capability_id' => $this->identityText($receipt->capability_id),
            'evidence_claim' => $this->identityText($claim),
            'criterion_scope' => $criteria,
            'governed_purpose' => $this->identityText($purpose),
        ]);

        $existing = DB::table('evidence_candidates')
            ->where('subject_actor_id', $subjectId)
            ->where('semantic_identity_digest', $semanticIdentity)
            ->first();

        if ($existing) {
            if ($existing->source_digest !== $receipt->source_digest) {
                throw new LogicException('Semantic duplicate conflicts with the pinned source revision integrity digest.');
            }

            return $this->array($existing, [
                'selected_material_refs',
                'criterion_scope',
                'proposed_facts',
                'metadata',
            ]);
        }

        $id = (string) Str::uuid7();
        $now = now();

        DB::table('evidence_candidates')->insert([
            'id' => $id,
            'handoff_receipt_id' => $receipt->id,
            'subject_actor_id' => $subjectId,
            'submitted_by' => $submittedBy,
            'source_type' => $receipt->source_type,
            'source_id' => $receipt->source_id,
            'source_revision' => $receipt->source_revision,
            'source_digest' => $receipt->source_digest,
            'selected_material_refs' => $this->json($materials),
            'capability_id' => $receipt->capability_id,
            'evidence_claim' => $claim,
            'criterion_scope' => $this->json($criteria),
            'governed_purpose' => $purpose,
            'semantic_identity_digest' => $semanticIdentity,
            'proposed_title' => $title,
            'proposed_summary' => $summary,
            'proposed_facts' => $this->json($facts),
            'metadata' => $this->json($metadata),
            'state' => 'RECEIVED',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->candidate($id);
    }

    /** @return array<string, mixed> */
    public function transitionCandidate(string $candidateId, string $actorId, string $state): array
    {
        if (! array_key_exists($state, self::CANDIDATE_TRANSITIONS) || $state === 'ADMITTED') {
            throw new InvalidArgumentException('Invalid Candidate Evidence transition target.');
        }

        return DB::transaction(function () use ($candidateId, $actorId, $state): array {
            $candidate = $this->lock('evidence_candidates', $candidateId);
            $this->own($candidate, $actorId);

            if ($candidate->state === $state) {
                return $this->candidate($candidateId);
            }

            $allowed = self::CANDIDATE_TRANSITIONS[$candidate->state] ?? null;
            if ($allowed === null || ! in_array($state, $allowed, true)) {
                throw new LogicException("Illegal Candidate Evidence transition: {$candidate->state} -> {$state}.");
            }

            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => $state,
                'updated_at' => now(),
            ]);

            return $this->candidate($candidateId);
        });
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>} */
    public function admitCandidate(string $candidateId, string $actorId): array
    {
        return DB::transaction(function () use ($candidateId, $actorId): array {
            $candidate = $this->lock('evidence_candidates', $candidateId);
            $this->own($candidate, $actorId);

            if ($candidate->state === 'ADMITTED' && $candidate->admitted_evidence_id) {
                $evidence = $this->row('governed_evidence', $candidate->admitted_evidence_id);

                return [
                    'evidence' => $evidence,
                    'revision' => $this->revision($candidate->admitted_evidence_id, (int) $evidence['current_revision_number']),
                ];
            }

            if ($candidate->state !== 'SUBMITTED_FOR_INTAKE') {
                throw new LogicException('Candidate Evidence must be SUBMITTED_FOR_INTAKE before Admission.');
            }

            $evidenceId = (string) Str::uuid7();
            $revisionId = (string) Str::uuid7();
            $now = now();

            DB::table('governed_evidence')->insert([
                'id' => $evidenceId,
                'candidate_id' => $candidateId,
                'subject_actor_id' => $candidate->subject_actor_id,
                'capability_id' => $candidate->capability_id,
                'evidence_claim' => $candidate->evidence_claim,
                'governed_purpose' => $candidate->governed_purpose,
                'lifecycle_state' => 'ACTIVE',
                'review_status' => 'UNREVIEWED',
                'effective_review_decision' => 'NONE',
                'effective_review_decision_id' => null,
                'current_revision_number' => 1,
                'admitted_by' => $actorId,
                'admitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $facts = $this->decode($candidate->proposed_facts);
            $materials = $this->decode($candidate->selected_material_refs);
            $criteria = $this->decode($candidate->criterion_scope);
            $body = [
                'id' => $revisionId,
                'evidence_id' => $evidenceId,
                'handoff_receipt_id' => $candidate->handoff_receipt_id,
                'revision' => 1,
                'previous_revision_id' => null,
                'title' => $candidate->proposed_title,
                'summary' => $candidate->proposed_summary,
                'facts' => $facts,
                'selected_material_refs' => $materials,
                'criterion_scope' => $criteria,
                'source_type' => $candidate->source_type,
                'source_id' => $candidate->source_id,
                'source_revision' => $candidate->source_revision,
                'source_digest' => $candidate->source_digest,
                'revision_reason' => 'INITIAL_ADMISSION',
            ];

            DB::table('governed_evidence_revisions')->insert([
                ...$body,
                'facts' => $this->json($facts),
                'selected_material_refs' => $this->json($materials),
                'criterion_scope' => $this->json($criteria),
                'content_digest' => $this->digest($body),
                'sealed_by' => $actorId,
                'sealed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => 'ADMITTED',
                'admitted_evidence_id' => $evidenceId,
                'admitted_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'evidence' => $this->row('governed_evidence', $evidenceId),
                'revision' => $this->revision($evidenceId, 1),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createRevision(string $evidenceId, string $actorId, array $data): array
    {
        return DB::transaction(function () use ($evidenceId, $actorId, $data): array {
            $evidence = $this->lock('governed_evidence', $evidenceId);
            $this->own($evidence, $actorId);

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Only ACTIVE Evidence can receive a superseding revision.');
            }

            $current = DB::table('governed_evidence_revisions')
                ->where('evidence_id', $evidenceId)
                ->where('revision', $evidence->current_revision_number)
                ->first();

            if (! $current) {
                throw new LogicException('Current Evidence Revision is missing.');
            }

            $reason = $this->text((string) ($data['revision_reason'] ?? ''), 1000, 'Revision reason');
            $title = $this->text((string) ($data['title'] ?? ''), 180, 'Revision title');
            $summary = $this->text((string) ($data['summary'] ?? ''), 4000, 'Revision summary');
            $receipt = null;
            if (isset($data['handoff_receipt_id'])) {
                $receipt = DB::table('evidence_source_handoff_receipts')
                    ->where('id', (string) $data['handoff_receipt_id'])
                    ->first();
                if (! $receipt || $receipt->subject_actor_id !== $actorId || $receipt->capability_id !== $evidence->capability_id) {
                    throw new LogicException('Superseding source Handoff receipt is outside the Evidence ownership/capability boundary.');
                }
            }
            $facts = $receipt ? $this->decode($receipt->facts) : $this->decode($current->facts);
            $materials = $receipt
                ? $this->decode($receipt->selected_material_refs)
                : $this->decode($current->selected_material_refs);
            $criteria = array_key_exists('criterion_scope', $data)
                ? $this->stringList($data['criterion_scope'], 'criterion scope', false, 50, 120)
                : $this->decode($current->criterion_scope);
            $this->governedPurpose($evidence->governed_purpose, $criteria);
            $revisionNumber = ((int) $evidence->current_revision_number) + 1;
            $revisionId = (string) Str::uuid7();
            $now = now();
            $body = [
                'id' => $revisionId,
                'evidence_id' => $evidenceId,
                'handoff_receipt_id' => $receipt?->id ?? $current->handoff_receipt_id,
                'revision' => $revisionNumber,
                'previous_revision_id' => $current->id,
                'title' => $title,
                'summary' => $summary,
                'facts' => $facts,
                'selected_material_refs' => $materials,
                'criterion_scope' => $criteria,
                'source_type' => $receipt?->source_type ?? $current->source_type,
                'source_id' => $receipt?->source_id ?? $current->source_id,
                'source_revision' => $receipt?->source_revision ?? $current->source_revision,
                'source_digest' => $receipt?->source_digest ?? $current->source_digest,
                'revision_reason' => $reason,
            ];

            DB::table('governed_evidence_revisions')->insert([
                ...$body,
                'facts' => $this->json($facts),
                'selected_material_refs' => $this->json($materials),
                'criterion_scope' => $this->json($criteria),
                'content_digest' => $this->digest($body),
                'sealed_by' => $actorId,
                'sealed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('governed_evidence')->where('id', $evidenceId)->update([
                'current_revision_number' => $revisionNumber,
                'review_status' => 'UNREVIEWED',
                'effective_review_decision' => 'NONE',
                'effective_review_decision_id' => null,
                'updated_at' => $now,
            ]);

            return $this->revision($evidenceId, $revisionNumber);
        });
    }

    public function transitionLifecycle(string $evidenceId, string $actorId, string $state): void
    {
        if (! in_array($state, ['ACTIVE', 'WITHDRAWN', 'SUPERSEDED'], true)) {
            throw new InvalidArgumentException('Invalid Evidence lifecycle.');
        }

        DB::transaction(function () use ($evidenceId, $actorId, $state): void {
            $evidence = $this->lock('governed_evidence', $evidenceId);
            $this->own($evidence, $actorId);

            if ($evidence->lifecycle_state === $state) {
                return;
            }

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Terminal Evidence lifecycle cannot reopen or change terminal state.');
            }

            DB::table('governed_evidence')->where('id', $evidenceId)->update([
                'lifecycle_state' => $state,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function requestReview(string $evidenceId, string $actorId, array $data = []): array
    {
        return DB::transaction(function () use ($evidenceId, $actorId, $data): array {
            $evidence = $this->lock('governed_evidence', $evidenceId);
            $this->own($evidence, $actorId);

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Only ACTIVE Evidence can enter Formal Review.');
            }

            $revision = DB::table('governed_evidence_revisions')
                ->where('evidence_id', $evidenceId)
                ->where('revision', $evidence->current_revision_number)
                ->first();

            if (! $revision) {
                throw new LogicException('Current Evidence Revision is missing.');
            }

            $scope = $this->text(
                (string) ($data['review_scope_key'] ?? "CAPABILITY:{$evidence->capability_id}"),
                160,
                'Review scope',
            );
            $purpose = $this->text(
                (string) ($data['purpose'] ?? 'FORMAL_EVIDENCE_REVIEW'),
                180,
                'Review purpose',
            );
            $criteria = array_key_exists('criterion_refs', $data)
                ? $this->stringList($data['criterion_refs'], 'Review criterion references', true, 50, 120)
                : $this->stringList(
                    $this->decode($revision->criterion_scope),
                    'Review criterion references',
                    false,
                    50,
                    120,
                );
            if ($criteria === [] && $evidence->governed_purpose !== 'GOVERNED_PROVENANCE_ATTESTATION') {
                throw new LogicException('Formal capability Evidence requires a non-empty governed Review criterion scope.');
            }

            $existing = DB::table('evidence_review_requests')
                ->where('evidence_id', $evidenceId)
                ->where('evidence_revision_id', $revision->id)
                ->where('review_scope_key', $scope)
                ->whereIn('status', ['REQUESTED', 'ASSIGNED', 'IN_REVIEW', 'READY_FOR_DECISION'])
                ->first();

            if ($existing) {
                return $this->array($existing, ['criterion_refs']);
            }

            $priorDecision = DB::table('evidence_review_decisions')
                ->where('evidence_id', $evidenceId)
                ->where('review_scope_key', $scope)
                ->orderByDesc('decided_at')
                ->first();
            $id = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_requests')->insert([
                'id' => $id,
                'evidence_id' => $evidenceId,
                'evidence_revision_id' => $revision->id,
                'requested_by' => $actorId,
                'review_scope_key' => $scope,
                'criterion_refs' => $this->json($criteria),
                'purpose' => $purpose,
                'prior_decision_id' => $priorDecision?->id,
                'status' => 'REQUESTED',
                'requested_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row('evidence_review_requests', $id, ['criterion_refs']);
        });
    }

    /** @return array<string, mixed> */
    public function admitReviewRequest(string $requestId, string $reviewerId): array
    {
        return DB::transaction(function () use ($requestId, $reviewerId): array {
            $request = $this->lock('evidence_review_requests', $requestId);
            $evidence = $this->lock('governed_evidence', $request->evidence_id);
            $this->own($evidence, $reviewerId);

            $existing = DB::table('evidence_reviews')->where('review_request_id', $requestId)->first();
            if ($existing) {
                return $this->array($existing, ['criterion_refs']);
            }

            if ($request->status !== 'REQUESTED') {
                throw new LogicException('Review Request is not awaiting assignment/start.');
            }

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Review cannot start for non-ACTIVE Evidence.');
            }

            $id = (string) Str::uuid7();
            $now = now();
            $criteria = $this->decode($request->criterion_refs);
            $initialStatus = $criteria === [] ? 'READY_FOR_DECISION' : 'IN_REVIEW';

            DB::table('evidence_review_requests')->where('id', $requestId)->update([
                'status' => $initialStatus,
                'assigned_reviewer_id' => $reviewerId,
                'assigned_at' => $now,
                'started_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_reviews')->insert([
                'id' => $id,
                'review_request_id' => $requestId,
                'evidence_id' => $request->evidence_id,
                'evidence_revision_id' => $request->evidence_revision_id,
                'reviewer_id' => $reviewerId,
                'review_scope_key' => $request->review_scope_key,
                'criterion_refs' => $request->criterion_refs,
                'status' => $initialStatus,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($this->isCurrentRevision($evidence, $request->evidence_revision_id)) {
                DB::table('governed_evidence')->where('id', $evidence->id)->update([
                    'review_status' => 'IN_REVIEW',
                    'updated_at' => $now,
                ]);
            }

            return $this->row('evidence_reviews', $id, ['criterion_refs']);
        });
    }

    /**
     * @param  list<string>  $supportingRevisionIds
     * @return array<string, mixed>
     */
    public function recordFinding(
        string $reviewId,
        string $actorId,
        string $criterion,
        string $finding,
        string $statement,
        array $supportingRevisionIds = [],
    ): array {
        if (! in_array($finding, self::FINDINGS, true)) {
            throw new InvalidArgumentException('Invalid Review Finding.');
        }

        return DB::transaction(function () use (
            $reviewId,
            $actorId,
            $criterion,
            $finding,
            $statement,
            $supportingRevisionIds,
        ): array {
            $review = $this->lock('evidence_reviews', $reviewId);
            if ($review->reviewer_id !== $actorId) {
                throw new LogicException('Review is outside reviewer boundary.');
            }
            if (! in_array($review->status, ['IN_REVIEW', 'READY_FOR_DECISION'], true)) {
                throw new LogicException('Review is not open for Findings.');
            }

            $criterion = $this->text($criterion, 120, 'Criterion key');
            $criteria = $this->decode($review->criterion_refs);
            if (! in_array($criterion, $criteria, true)) {
                throw new LogicException('Finding criterion is outside the pinned Review criterion scope.');
            }
            if (DB::table('evidence_review_findings')
                ->where('review_id', $reviewId)
                ->where('criterion_key', $criterion)
                ->exists()) {
                throw new LogicException('A Finding already exists for this pinned Review criterion.');
            }

            $supporting = $this->stringList(
                $supportingRevisionIds,
                'supporting Evidence Revision references',
            );
            $this->validateRevisionReferences($actorId, $review->evidence_id, $supporting, true);
            $id = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_findings')->insert([
                'id' => $id,
                'review_id' => $reviewId,
                'criterion_key' => $criterion,
                'finding' => $finding,
                'statement' => $this->text($statement, 4000, 'Finding statement'),
                'supporting_evidence_revision_ids' => $this->json($supporting),
                'recorded_by' => $actorId,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $coveredCriteria = DB::table('evidence_review_findings')
                ->where('review_id', $reviewId)
                ->pluck('criterion_key')
                ->map(fn ($value): string => (string) $value)
                ->unique()
                ->all();
            $scopeComplete = array_diff(array_values(array_unique($criteria)), $coveredCriteria) === [];

            if ($scopeComplete && $review->status !== 'READY_FOR_DECISION') {
                DB::table('evidence_reviews')->where('id', $reviewId)->update([
                    'status' => 'READY_FOR_DECISION',
                    'updated_at' => $now,
                ]);
                DB::table('evidence_review_requests')->where('id', $review->review_request_id)->update([
                    'status' => 'READY_FOR_DECISION',
                    'updated_at' => $now,
                ]);
            }

            return $this->row('evidence_review_findings', $id, ['supporting_evidence_revision_ids']);
        });
    }

    /** @return array<string, mixed> */
    public function recordReviewDecision(
        string $reviewId,
        string $actorId,
        string $decision,
        string $rationale,
    ): array {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new InvalidArgumentException('Invalid Review Decision.');
        }

        return DB::transaction(function () use ($reviewId, $actorId, $decision, $rationale): array {
            $review = $this->lock('evidence_reviews', $reviewId);
            if ($review->reviewer_id !== $actorId) {
                throw new LogicException('Review is outside reviewer boundary.');
            }

            $existing = DB::table('evidence_review_decisions')->where('review_id', $reviewId)->first();
            if ($existing) {
                return (array) $existing;
            }

            if ($review->status !== 'READY_FOR_DECISION') {
                throw new LogicException('Review must be READY_FOR_DECISION before a Decision can be sealed.');
            }

            $request = DB::table('evidence_review_requests')->where('id', $review->review_request_id)->first();
            if (! $request) {
                throw new LogicException('Review Request is missing.');
            }

            $id = (string) Str::uuid7();
            $now = now();
            DB::table('evidence_review_decisions')->insert([
                'id' => $id,
                'review_id' => $reviewId,
                'evidence_id' => $review->evidence_id,
                'evidence_revision_id' => $review->evidence_revision_id,
                'supersedes_decision_id' => $request->prior_decision_id,
                'review_scope_key' => $review->review_scope_key,
                'decision' => $decision,
                'rationale' => $this->text($rationale, 4000, 'Review Decision rationale'),
                'decided_by' => $actorId,
                'decided_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_reviews')->where('id', $reviewId)->update([
                'status' => 'CLOSED',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('evidence_review_requests')->where('id', $review->review_request_id)->update([
                'status' => 'CLOSED',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);

            $evidence = $this->lock('governed_evidence', $review->evidence_id);
            if ($this->isCurrentRevision($evidence, $review->evidence_revision_id)) {
                DB::table('governed_evidence')->where('id', $review->evidence_id)->update([
                    'review_status' => 'REVIEWED',
                    'effective_review_decision' => $decision,
                    'effective_review_decision_id' => $id,
                    'updated_at' => $now,
                ]);
            }

            return $this->row('evidence_review_decisions', $id);
        });
    }

    /**
     * @param  list<string>  $reviewDecisionIds
     * @param  list<string>  $supportingRevisionIds
     * @param  list<string>  $contradictingRevisionIds
     * @return array<string, mixed>
     */
    public function evaluateMastery(
        string $subjectId,
        string $capabilityId,
        string $evaluatorId,
        string $policyRevision,
        string $judgment,
        string $freshness,
        array $reviewDecisionIds,
        array $supportingRevisionIds,
        array $contradictingRevisionIds,
        string $rationale,
    ): array {
        if ($subjectId !== $evaluatorId) {
            throw new LogicException('Mastery evaluation is outside the authenticated actor boundary.');
        }
        if (! in_array($judgment, self::JUDGMENTS, true) || ! in_array($freshness, self::FRESHNESS, true)) {
            throw new InvalidArgumentException('Invalid Mastery dimensions.');
        }

        $capabilityId = $this->text($capabilityId, 100, 'Capability ID');
        $policyRevision = $this->text($policyRevision, 120, 'Mastery Policy Revision');
        $rationale = $this->text($rationale, 4000, 'Mastery rationale');
        $policy = DB::table('evidence_mastery_policy_revisions')
            ->where('id', $policyRevision)
            ->where('state', 'APPROVED')
            ->first();
        if (! $policy) {
            throw new LogicException('A real approved Mastery Policy Revision is required.');
        }
        $qualifyingDecisions = $this->stringList(
            $this->decode($policy->qualifying_review_decisions),
            'Mastery Policy qualifying Review Decisions',
            true,
            2,
            40,
        );
        $decisions = $this->stringList($reviewDecisionIds, 'Review Decision references');
        $supporting = $this->stringList($supportingRevisionIds, 'supporting Evidence Revision references');
        $contradicting = $this->stringList($contradictingRevisionIds, 'contradicting Evidence Revision references');

        if (array_intersect($supporting, $contradicting) !== []) {
            throw new LogicException('An Evidence Revision cannot be both supporting and contradicting in the same Mastery State.');
        }
        if ($judgment === 'MASTERED' && ($supporting === [] || $decisions === [])) {
            throw new LogicException('MASTERED requires exact supporting Evidence Revision and Review Decision provenance.');
        }

        $revisionRows = [];
        foreach (array_values(array_unique([...$supporting, ...$contradicting])) as $revisionId) {
            $row = DB::table('governed_evidence_revisions as r')
                ->join('governed_evidence as e', 'e.id', '=', 'r.evidence_id')
                ->where('r.id', $revisionId)
                ->select(
                    'r.id',
                    'r.evidence_id',
                    'e.subject_actor_id',
                    'e.capability_id',
                    'e.lifecycle_state',
                    'e.effective_review_decision_id',
                )
                ->first();

            if (! $row) {
                throw new LogicException('Unknown Evidence Revision reference.');
            }
            if ($row->subject_actor_id !== $subjectId || $row->capability_id !== $capabilityId) {
                throw new LogicException('Evidence Revision reference is outside the Mastery subject/capability boundary.');
            }
            if ($row->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Only ACTIVE Evidence Revisions can contribute to a new Mastery State.');
            }

            $revisionRows[$revisionId] = $row;
        }

        $decisionRows = [];
        foreach ($decisions as $decisionId) {
            $row = DB::table('evidence_review_decisions as d')
                ->join('governed_evidence as e', 'e.id', '=', 'd.evidence_id')
                ->where('d.id', $decisionId)
                ->select(
                    'd.id',
                    'd.decision',
                    'd.evidence_id',
                    'd.evidence_revision_id',
                    'e.subject_actor_id',
                    'e.capability_id',
                    'e.effective_review_decision_id',
                )
                ->first();

            if (! $row) {
                throw new LogicException('Unknown Review Decision reference.');
            }
            if ($row->subject_actor_id !== $subjectId || $row->capability_id !== $capabilityId) {
                throw new LogicException('Review Decision reference is outside the Mastery subject/capability boundary.');
            }
            if ($row->effective_review_decision_id !== $row->id) {
                throw new LogicException('Superseded Review Decisions cannot be used as effective Mastery provenance.');
            }
            if (! isset($revisionRows[$row->evidence_revision_id])) {
                throw new LogicException('Every Review Decision must reference an Evidence Revision explicitly included in the Mastery evaluation.');
            }
            $decisionRows[$row->id] = $row;
        }

        foreach ($revisionRows as $revisionId => $row) {
            if (! in_array($row->effective_review_decision_id, $decisions, true)) {
                throw new LogicException("Evidence Revision {$revisionId} is missing its exact effective Review Decision provenance.");
            }
        }

        if ($judgment === 'MASTERED') {
            foreach ($supporting as $revisionId) {
                $decisionId = $revisionRows[$revisionId]->effective_review_decision_id;
                $decisionOutcome = $decisionRows[$decisionId]->decision ?? null;
                if (! is_string($decisionOutcome) || ! in_array($decisionOutcome, $qualifyingDecisions, true)) {
                    throw new LogicException('MASTERED requires a policy-qualifying effective Review Decision for every supporting Evidence Revision.');
                }
            }
        }

        $body = [
            'subject_actor_id' => $subjectId,
            'target_type' => 'CAPABILITY',
            'target_id' => $capabilityId,
            'policy_revision_id' => $policyRevision,
            'judgment' => $judgment,
            'freshness_status' => $freshness,
            'review_decision_ids' => $decisions,
            'supporting_evidence_revision_ids' => $supporting,
            'contradicting_evidence_revision_ids' => $contradicting,
            'rationale' => $rationale,
        ];

        return DB::transaction(function () use ($body, $evaluatorId): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                $body['subject_actor_id'].'|'.$body['target_id'],
            ]);

            $prior = $this->masteryChainTips($body['subject_actor_id'])
                ->where('current.target_type', 'CAPABILITY')
                ->where('current.target_id', $body['target_id'])
                ->lockForUpdate()
                ->first();
            $evaluationId = (string) Str::uuid7();
            $stateId = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_mastery_evaluations')->insert([
                'id' => $evaluationId,
                'subject_actor_id' => $body['subject_actor_id'],
                'target_type' => $body['target_type'],
                'target_id' => $body['target_id'],
                'policy_revision_id' => $body['policy_revision_id'],
                'judgment' => $body['judgment'],
                'freshness_status' => $body['freshness_status'],
                'review_decision_ids' => $this->json($body['review_decision_ids']),
                'supporting_evidence_revision_ids' => $this->json($body['supporting_evidence_revision_ids']),
                'contradicting_evidence_revision_ids' => $this->json($body['contradicting_evidence_revision_ids']),
                'rationale' => $body['rationale'],
                'evaluator_id' => $evaluatorId,
                'content_digest' => $this->digest($body),
                'evaluated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_mastery_states')->insert([
                'id' => $stateId,
                'subject_actor_id' => $body['subject_actor_id'],
                'target_type' => $body['target_type'],
                'target_id' => $body['target_id'],
                'judgment' => $body['judgment'],
                'freshness_status' => $body['freshness_status'],
                'policy_revision_id' => $body['policy_revision_id'],
                'evaluation_id' => $evaluationId,
                'previous_state_id' => $prior?->id,
                'reason' => $body['rationale'],
                'evaluated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($body['review_decision_ids'] as $decisionId) {
                DB::table('evidence_mastery_state_decisions')->insert([
                    'mastery_state_id' => $stateId,
                    'review_decision_id' => $decisionId,
                    'created_at' => $now,
                ]);
            }

            foreach ($body['supporting_evidence_revision_ids'] as $revisionId) {
                DB::table('evidence_mastery_state_evidence')->insert([
                    'mastery_state_id' => $stateId,
                    'evidence_revision_id' => $revisionId,
                    'contribution' => 'SUPPORTING',
                    'created_at' => $now,
                ]);
            }

            foreach ($body['contradicting_evidence_revision_ids'] as $revisionId) {
                DB::table('evidence_mastery_state_evidence')->insert([
                    'mastery_state_id' => $stateId,
                    'evidence_revision_id' => $revisionId,
                    'contribution' => 'CONTRADICTING',
                    'created_at' => $now,
                ]);
            }

            return $this->masteryState($stateId);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $annotations
     * @return array<string, mixed>
     */
    public function createPortfolio(
        string $actorId,
        string $name,
        ?string $scope,
        string $grouping,
        array $filters = [],
        array $annotations = [],
    ): array {
        $id = (string) Str::uuid7();
        $now = now();
        $grouping = $this->text($grouping, 80, 'Portfolio grouping');
        if (! in_array($grouping, ['CAPABILITY', 'REVIEW_DECISION', 'MASTERY'], true)) {
            throw new InvalidArgumentException('Unsupported Portfolio grouping.');
        }
        $filters = $this->boundedPortfolioFilters($filters);
        $annotations = $this->boundedPortfolioAnnotations($annotations);

        DB::table('evidence_portfolios')->insert([
            'id' => $id,
            'owner_actor_id' => $actorId,
            'name' => $this->text($name, 180, 'Portfolio View name'),
            'view_scope' => $scope ? $this->text($scope, 120, 'Portfolio View scope') : null,
            'grouping' => $grouping,
            'filters' => $this->json($filters),
            'annotations' => $this->json($annotations),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('evidence_portfolios', $id, ['filters', 'annotations']);
    }

    public function addEvidenceToPortfolio(
        string $portfolioId,
        string $evidenceId,
        string $actorId,
        ?string $annotation = null,
        int $sort = 0,
    ): void {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();
        $evidence = DB::table('governed_evidence')->where('id', $evidenceId)->first();

        if (! $portfolio || $portfolio->owner_actor_id !== $actorId || ! $evidence || $evidence->subject_actor_id !== $actorId) {
            throw new LogicException('Portfolio boundary mismatch.');
        }

        $mastery = $this->masteryChainTips($actorId)
            ->where('current.target_type', 'CAPABILITY')
            ->where('current.target_id', $evidence->capability_id)
            ->first();
        $existing = DB::table('evidence_portfolio_items')
            ->where('portfolio_id', $portfolioId)
            ->where('evidence_id', $evidenceId)
            ->first();
        $now = now();
        $values = [
            'mastery_state_id' => $mastery?->id,
            'sort_order' => max(0, $sort),
            'annotation' => $annotation === null || trim($annotation) === ''
                ? null
                : $this->text($annotation, 4000, 'Portfolio item annotation'),
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('evidence_portfolio_items')->where('id', $existing->id)->update($values);

            return;
        }

        DB::table('evidence_portfolio_items')->insert([
            ...$values,
            'id' => (string) Str::uuid7(),
            'portfolio_id' => $portfolioId,
            'evidence_id' => $evidenceId,
            'created_at' => $now,
        ]);
    }

    public function removeEvidenceFromPortfolio(string $portfolioId, string $evidenceId, string $actorId): void
    {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();
        if (! $portfolio || $portfolio->owner_actor_id !== $actorId) {
            throw new LogicException('Portfolio boundary mismatch.');
        }

        DB::table('evidence_portfolio_items')
            ->where('portfolio_id', $portfolioId)
            ->where('evidence_id', $evidenceId)
            ->delete();
    }

    /** @return array<string, mixed> */
    public function workspace(string $actorId): array
    {
        $handoffReceipts = DB::table('evidence_source_handoff_receipts')
            ->where('subject_actor_id', $actorId)
            ->latest('registered_at')
            ->select(
                'id',
                'source_type',
                'source_id',
                'source_revision',
                'source_digest',
                'selected_material_refs',
                'capability_id',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, ['selected_material_refs']))
            ->all();

        $candidates = DB::table('evidence_candidates')
            ->where('subject_actor_id', $actorId)
            ->latest()
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'selected_material_refs',
                'criterion_scope',
                'proposed_facts',
                'metadata',
            ]))
            ->all();

        $evidence = DB::table('governed_evidence as e')
            ->join('governed_evidence_revisions as r', fn ($join) => $join
                ->on('r.evidence_id', '=', 'e.id')
                ->on('r.revision', '=', 'e.current_revision_number'))
            ->where('e.subject_actor_id', $actorId)
            ->latest('e.admitted_at')
            ->select(
                'e.*',
                'r.id as current_revision_id',
                'r.previous_revision_id',
                'r.title',
                'r.summary',
                'r.facts',
                'r.selected_material_refs',
                'r.criterion_scope',
                'r.source_type',
                'r.source_id',
                'r.source_revision',
                'r.source_digest',
                'r.revision_reason',
                'r.content_digest',
                'r.sealed_at',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']))
            ->all();

        foreach ($evidence as &$record) {
            $record['revisions'] = DB::table('governed_evidence_revisions')
                ->where('evidence_id', $record['id'])
                ->orderBy('revision')
                ->get()
                ->map(fn ($row) => $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']))
                ->all();
        }
        unset($record);

        $evidenceIds = array_column($evidence, 'id');
        $requests = $evidenceIds === [] ? [] : DB::table('evidence_review_requests')
            ->whereIn('evidence_id', $evidenceIds)
            ->latest('requested_at')
            ->get()
            ->map(fn ($row) => $this->array($row, ['criterion_refs']))
            ->all();
        $reviews = $evidenceIds === [] ? [] : DB::table('evidence_reviews')
            ->whereIn('evidence_id', $evidenceIds)
            ->latest('started_at')
            ->get()
            ->map(fn ($row) => $this->array($row, ['criterion_refs']))
            ->all();

        foreach ($reviews as &$review) {
            $review['findings'] = DB::table('evidence_review_findings')
                ->where('review_id', $review['id'])
                ->orderBy('recorded_at')
                ->get()
                ->map(fn ($row) => $this->array($row, ['supporting_evidence_revision_ids']))
                ->all();
            $decision = DB::table('evidence_review_decisions')->where('review_id', $review['id'])->first();
            $review['decision'] = $decision ? (array) $decision : null;
        }
        unset($review);

        $history = DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.subject_actor_id', $actorId)
            ->orderByDesc('s.evaluated_at')
            ->select(
                's.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'review_decision_ids',
                'supporting_evidence_revision_ids',
                'contradicting_evidence_revision_ids',
            ]))
            ->all();
        $mastery = $this->masteryChainTips($actorId)
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 'current.evaluation_id')
            ->select(
                'current.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'review_decision_ids',
                'supporting_evidence_revision_ids',
                'contradicting_evidence_revision_ids',
            ]))
            ->all();
        $masteryPolicies = DB::table('evidence_mastery_policy_revisions')
            ->where('state', 'APPROVED')
            ->orderBy('policy_key')
            ->orderByDesc('revision')
            ->get()
            ->map(fn ($row) => $this->array($row, ['qualifying_review_decisions']))
            ->all();

        $portfolios = DB::table('evidence_portfolios')
            ->where('owner_actor_id', $actorId)
            ->latest('updated_at')
            ->get()
            ->map(fn ($row) => $this->array($row, ['filters', 'annotations']))
            ->all();

        foreach ($portfolios as &$portfolio) {
            $portfolio['items'] = DB::table('evidence_portfolio_items as i')
                ->join('governed_evidence as e', 'e.id', '=', 'i.evidence_id')
                ->join('governed_evidence_revisions as r', fn ($join) => $join
                    ->on('r.evidence_id', '=', 'e.id')
                    ->on('r.revision', '=', 'e.current_revision_number'))
                ->where('i.portfolio_id', $portfolio['id'])
                ->orderBy('i.sort_order')
                ->select(
                    'i.*',
                    'e.capability_id',
                    'e.lifecycle_state',
                    'e.review_status',
                    'e.effective_review_decision',
                    'r.title',
                    'r.summary',
                    'r.id as current_revision_id',
                )
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }
        unset($portfolio);

        $openCandidateStates = [
            'RECEIVED',
            'DRAFT',
            'PREPARED',
            'SUBMITTED_FOR_INTAKE',
            'RETURNED_FOR_CONTEXT',
        ];

        return [
            'summary' => [
                'candidate_count' => count(array_filter(
                    $candidates,
                    fn ($candidate) => in_array($candidate['state'], $openCandidateStates, true),
                )),
                'evidence_count' => count($evidence),
                'review_in_progress_count' => count(array_filter(
                    $reviews,
                    fn ($review) => in_array($review['status'], ['IN_REVIEW', 'READY_FOR_DECISION'], true),
                )),
                'mastery_count' => count($mastery),
                'portfolio_count' => count($portfolios),
            ],
            'handoff_receipts' => $handoffReceipts,
            'candidates' => $candidates,
            'evidence' => $evidence,
            'review_requests' => $requests,
            'reviews' => $reviews,
            'mastery' => $mastery,
            'mastery_history' => $history,
            'mastery_policies' => $masteryPolicies,
            'portfolios' => $portfolios,
        ];
    }

    /** @return array<string, mixed> */
    private function candidate(string $id): array
    {
        return $this->row('evidence_candidates', $id, [
            'selected_material_refs',
            'criterion_scope',
            'proposed_facts',
            'metadata',
        ]);
    }

    /** @param list<string> $revisionIds */
    private function validateRevisionReferences(
        string $actorId,
        string $reviewEvidenceId,
        array $revisionIds,
        bool $sameCapability,
    ): void {
        $reviewEvidence = DB::table('governed_evidence')->where('id', $reviewEvidenceId)->first();
        if (! $reviewEvidence || $reviewEvidence->subject_actor_id !== $actorId) {
            throw new LogicException('Review Evidence boundary mismatch.');
        }

        foreach ($revisionIds as $revisionId) {
            $row = DB::table('governed_evidence_revisions as r')
                ->join('governed_evidence as e', 'e.id', '=', 'r.evidence_id')
                ->where('r.id', $revisionId)
                ->select('e.subject_actor_id', 'e.capability_id')
                ->first();

            if (! $row) {
                throw new LogicException('Unknown Evidence Revision reference.');
            }
            if ($row->subject_actor_id !== $actorId || ($sameCapability && $row->capability_id !== $reviewEvidence->capability_id)) {
                throw new LogicException('Evidence Revision reference is outside the Review ownership/scope boundary.');
            }
        }
    }

    private function isCurrentRevision(object $evidence, string $revisionId): bool
    {
        return DB::table('governed_evidence_revisions')
            ->where('id', $revisionId)
            ->where('evidence_id', $evidence->id)
            ->where('revision', $evidence->current_revision_number)
            ->exists();
    }

    private function masteryChainTips(string $subjectId): Builder
    {
        return DB::table('evidence_mastery_states as current')
            ->where('current.subject_actor_id', $subjectId)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('evidence_mastery_states as successor')
                    ->whereColumn('successor.previous_state_id', 'current.id');
            });
    }

    /** @return array<string, mixed> */
    private function masteryState(string $stateId): array
    {
        $row = DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.id', $stateId)
            ->select(
                's.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->first();

        if (! $row) {
            throw new LogicException('Mastery State missing.');
        }

        return $this->array($row, [
            'review_decision_ids',
            'supporting_evidence_revision_ids',
            'contradicting_evidence_revision_ids',
        ]);
    }

    private function lock(string $table, string $id): object
    {
        $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
        if (! $row) {
            throw new InvalidArgumentException("{$table} record not found.");
        }

        return $row;
    }

    private function own(object $row, string $actorId): void
    {
        if (($row->subject_actor_id ?? null) !== $actorId) {
            throw new LogicException('Record is outside actor boundary.');
        }
    }

    /**
     * @param  list<string>  $json
     * @return array<string, mixed>
     */
    private function row(string $table, string $id, array $json = []): array
    {
        $row = DB::table($table)->where('id', $id)->first();
        if (! $row) {
            throw new InvalidArgumentException("{$table} record not found.");
        }

        return $this->array($row, $json);
    }

    /** @return array<string, mixed> */
    private function revision(string $evidenceId, int $revision): array
    {
        $row = DB::table('governed_evidence_revisions')
            ->where('evidence_id', $evidenceId)
            ->where('revision', $revision)
            ->first();

        if (! $row) {
            throw new LogicException('Evidence Revision missing.');
        }

        return $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']);
    }

    /**
     * @param  list<string>  $json
     * @return array<string, mixed>
     */
    private function array(object $row, array $json = []): array
    {
        $out = (array) $row;
        foreach ($json as $key) {
            $out[$key] = $this->decode($out[$key] ?? null);
        }

        return $out;
    }

    /** @return array<mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : [];

        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<string> */
    private function stringList(
        mixed $value,
        string $name,
        bool $required = false,
        int $maxItems = 50,
        int $maxLength = 240,
    ): array
    {
        if (! is_array($value)) {
            if ($required) {
                throw new InvalidArgumentException("{$name} must be a non-empty array.");
            }

            return [];
        }

        $normalized = [];
        foreach ($value as $entry) {
            if (! is_string($entry) || trim($entry) === '') {
                throw new InvalidArgumentException("{$name} contains an invalid reference.");
            }
            $entry = trim($entry);
            if (mb_strlen($entry) > $maxLength) {
                throw new InvalidArgumentException("{$name} contains an overlong reference.");
            }
            $normalized[] = $entry;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);

        if ($required && $normalized === []) {
            throw new InvalidArgumentException("{$name} must contain at least one reference.");
        }
        if (count($normalized) > $maxItems) {
            throw new InvalidArgumentException("{$name} exceeds the maximum item count.");
        }

        return $normalized;
    }

    /** @param list<string> $criteria */
    private function governedPurpose(string $purpose, array $criteria): string
    {
        $purpose = $this->text($purpose, 180, 'Governed Evidence purpose');
        if (! array_key_exists($purpose, self::GOVERNED_PURPOSES)) {
            throw new InvalidArgumentException('Unsupported governed Evidence purpose.');
        }
        if (self::GOVERNED_PURPOSES[$purpose] && $criteria === []) {
            throw new LogicException('Formal capability Evidence requires a governed criterion scope.');
        }

        return $purpose;
    }

    /** @return list<array{key: string, value: string}> */
    private function boundedFacts(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > 50) {
            throw new InvalidArgumentException('Source Handoff facts must be a list of at most 50 key/value facts.');
        }

        $facts = [];
        foreach ($value as $fact) {
            if (! is_array($fact) || array_diff(array_keys($fact), ['key', 'value']) !== []) {
                throw new InvalidArgumentException('Each source Handoff fact must contain only key and value.');
            }
            $facts[] = [
                'key' => $this->text((string) ($fact['key'] ?? ''), 80, 'Source Handoff fact key'),
                'value' => $this->text((string) ($fact['value'] ?? ''), 2000, 'Source Handoff fact value'),
            ];
        }

        return $facts;
    }

    /** @return array<string, bool|float|int|string|null> */
    private function boundedMetadata(mixed $value): array
    {
        if (! is_array($value) || (array_is_list($value) && $value !== []) || count($value) > 20) {
            throw new InvalidArgumentException('Source Handoff metadata must be a bounded key/value object.');
        }

        $metadata = [];
        foreach ($value as $key => $entry) {
            if (! is_string($key) || preg_match('/^[A-Za-z0-9_.:-]{1,80}$/', $key) !== 1) {
                throw new InvalidArgumentException('Source Handoff metadata contains an invalid key.');
            }
            if (! is_string($entry) && ! is_bool($entry) && ! is_int($entry) && ! is_float($entry) && $entry !== null) {
                throw new InvalidArgumentException('Source Handoff metadata contains an unsupported value.');
            }
            if (is_string($entry) && mb_strlen($entry) > 500) {
                throw new InvalidArgumentException('Source Handoff metadata contains an overlong value.');
            }
            $metadata[$key] = $entry;
        }

        ksort($metadata, SORT_STRING);

        return $metadata;
    }

    /** @param array<string, mixed> $filters */
    private function boundedPortfolioFilters(array $filters): array
    {
        if (array_diff(array_keys($filters), ['lifecycle_states', 'review_decisions', 'capability_ids']) !== []) {
            throw new InvalidArgumentException('Portfolio filters contain an unsupported field.');
        }

        $normalized = [];
        if (array_key_exists('lifecycle_states', $filters)) {
            $states = $this->stringList($filters['lifecycle_states'], 'Portfolio lifecycle filters', false, 3, 24);
            foreach ($states as $state) {
                if (! in_array($state, ['ACTIVE', 'WITHDRAWN', 'SUPERSEDED'], true)) {
                    throw new InvalidArgumentException('Portfolio lifecycle filter is invalid.');
                }
            }
            $normalized['lifecycle_states'] = $states;
        }
        if (array_key_exists('review_decisions', $filters)) {
            $decisions = $this->stringList($filters['review_decisions'], 'Portfolio Review Decision filters', false, 5, 40);
            foreach ($decisions as $decision) {
                if ($decision !== 'NONE' && ! in_array($decision, self::DECISIONS, true)) {
                    throw new InvalidArgumentException('Portfolio Review Decision filter is invalid.');
                }
            }
            $normalized['review_decisions'] = $decisions;
        }
        if (array_key_exists('capability_ids', $filters)) {
            $normalized['capability_ids'] = $this->stringList(
                $filters['capability_ids'],
                'Portfolio Capability filters',
                false,
                20,
                100,
            );
        }

        return $normalized;
    }

    /** @param array<string, mixed> $annotations */
    private function boundedPortfolioAnnotations(array $annotations): array
    {
        if (array_diff(array_keys($annotations), ['purpose', 'audience']) !== []) {
            throw new InvalidArgumentException('Portfolio annotations contain an unsupported field.');
        }

        $normalized = [];
        foreach ($annotations as $key => $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException('Portfolio annotations must be text.');
            }
            $normalized[$key] = $this->text($value, 500, "Portfolio {$key}");
        }

        return $normalized;
    }

    private function text(string $value, int $max, string $name): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException("{$name} is required.");
        }
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException("{$name} exceeds the maximum length.");
        }

        return $value;
    }

    private function sourceDigest(string $value): string
    {
        $digest = strtolower(trim($value));
        if (! preg_match('/^[a-f0-9]{64}$/', $digest)) {
            throw new InvalidArgumentException('Source digest must be SHA-256 hex.');
        }

        return $digest;
    }

    private function identityText(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<mixed> $value */
    private function digest(array $value): string
    {
        return hash('sha256', $this->json($value));
    }
}
