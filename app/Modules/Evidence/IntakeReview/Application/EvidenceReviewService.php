<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\CanonicalEvidenceReference;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewAuthorizer;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\IntakeReview\Domain\ReviewFindingOutcome;
use App\Modules\Evidence\IntakeReview\Domain\ReviewStatus;
use App\Modules\Evidence\Models\EvidenceReviewScopeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ValueError;

final class EvidenceReviewService
{
    public function __construct(private readonly IntakeReviewAuthorizer $authorizer) {}

    /**
     * @param  list<array{evidence_id:string,evidence_revision_id:string}>  $items
     * @param  list<string>  $criterionRefs
     * @return array<string, mixed>
     */
    public function requestReview(
        array $items,
        string $requestedBy,
        string $reviewScopeKey,
        array $criterionRefs,
        string $purpose,
        ?string $assignedReviewerId = null,
    ): array {
        $references = $this->references($items);

        return DB::transaction(function () use (
            $references,
            $requestedBy,
            $reviewScopeKey,
            $criterionRefs,
            $purpose,
            $assignedReviewerId,
        ): array {
            $subjectActorId = null;

            foreach ($references as $reference) {
                $row = $this->evidenceRevision($reference);

                if ((string) $row->lifecycle_state !== 'ACTIVE') {
                    throw new IntakeReviewException('Only ACTIVE canonical Evidence may enter formal Review.');
                }

                if ((int) $row->current_revision_number !== (int) $row->revision) {
                    throw new IntakeReviewException('Formal Review must pin the current sealed Evidence Revision.');
                }

                if ((string) $row->review_status === ReviewStatus::IN_REVIEW->value) {
                    throw new IntakeReviewException('Canonical Evidence already participates in an active formal Review.');
                }

                $subjectActorId ??= (string) $row->subject_actor_id;

                if (! hash_equals($subjectActorId, (string) $row->subject_actor_id)) {
                    throw new IntakeReviewException('A formal multi-Evidence Review cannot cross Evidence subject boundaries.');
                }
            }

            if ($subjectActorId === null) {
                throw new IntakeReviewException('Formal Review requires at least one canonical Evidence item.');
            }

            $this->authorizer->assertSubjectActor($subjectActorId, $requestedBy);

            $requestId = (string) Str::uuid7();
            $primary = $references[0];
            $now = now();
            $status = $assignedReviewerId === null ? 'REQUESTED' : 'ASSIGNED';

            DB::table('evidence_review_requests')->insert([
                'id' => $requestId,
                'evidence_id' => $primary->evidenceId,
                'evidence_revision_id' => $primary->evidenceRevisionId,
                'requested_by' => $requestedBy,
                'review_scope_key' => trim($reviewScopeKey),
                'criterion_refs' => json_encode($criterionRefs, JSON_THROW_ON_ERROR),
                'purpose' => trim($purpose),
                'prior_decision_id' => null,
                'status' => $status,
                'requested_at' => $now,
                'assigned_reviewer_id' => $assignedReviewerId,
                'assigned_at' => $assignedReviewerId === null ? null : $now,
                'started_at' => null,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($references as $ordinal => $reference) {
                EvidenceReviewScopeItem::query()->insert([
                    'review_request_id' => $requestId,
                    'evidence_id' => $reference->evidenceId,
                    'evidence_revision_id' => $reference->evidenceRevisionId,
                    'ordinal' => $ordinal + 1,
                    'added_by' => $requestedBy,
                    'created_at' => $now,
                ]);

                DB::table('governed_evidence')->where('id', $reference->evidenceId)->update([
                    'review_status' => ReviewStatus::IN_REVIEW->value,
                    'updated_at' => $now,
                ]);
            }

            return (array) DB::table('evidence_review_requests')->where('id', $requestId)->firstOrFail();
        });
    }

    /**
     * Resolve a single canonical Evidence object's current sealed Revision for the
     * workspace route, then enter the same governed multi-item Review boundary.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function requestCurrentRevisionReview(
        string $evidenceId,
        string $requestedBy,
        array $data = [],
    ): array {
        $current = DB::table('governed_evidence as evidence')
            ->join('governed_evidence_revisions as revision', function ($join): void {
                $join->on('revision.evidence_id', '=', 'evidence.id')
                    ->on('revision.revision', '=', 'evidence.current_revision_number');
            })
            ->where('evidence.id', $evidenceId)
            ->select(
                'evidence.id as evidence_id',
                'evidence.capability_id',
                'evidence.governed_purpose',
                'revision.id as evidence_revision_id',
                'revision.criterion_scope',
            )
            ->first();

        if ($current === null) {
            throw new IntakeReviewException('Current sealed Evidence Revision was not found.');
        }

        $criterionRefs = $data['criterion_refs'] ?? json_decode(
            (string) $current->criterion_scope,
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        if (! is_array($criterionRefs) || ! array_is_list($criterionRefs)) {
            throw new IntakeReviewException('Formal Review criterion scope must be a list.');
        }
        $criteria = [];
        foreach ($criterionRefs as $criterionRef) {
            if (! is_string($criterionRef) || trim($criterionRef) === '') {
                throw new IntakeReviewException('Formal Review criterion references must be non-empty strings.');
            }
            $criteria[] = trim($criterionRef);
        }
        if ($criteria === [] && $current->governed_purpose !== 'GOVERNED_PROVENANCE_ATTESTATION') {
            throw new IntakeReviewException('Formal capability Evidence requires a non-empty governed Review criterion scope.');
        }

        return $this->requestReview(
            [[
                'evidence_id' => (string) $current->evidence_id,
                'evidence_revision_id' => (string) $current->evidence_revision_id,
            ]],
            $requestedBy,
            (string) ($data['review_scope_key'] ?? "CAPABILITY:{$current->capability_id}"),
            $criteria,
            (string) ($data['purpose'] ?? 'FORMAL_EVIDENCE_REVIEW'),
        );
    }

    /** @return array<string, mixed> */
    public function startReview(string $reviewRequestId, string $reviewerId): array
    {
        return DB::transaction(function () use ($reviewRequestId, $reviewerId): array {
            $request = DB::table('evidence_review_requests')->where('id', $reviewRequestId)->lockForUpdate()->first();

            if ($request === null) {
                throw new IntakeReviewException('Evidence Review Request was not found.');
            }

            if (in_array((string) $request->status, ['CLOSED', 'CANCELLED'], true)) {
                throw new IntakeReviewException('Closed or cancelled Review Requests cannot be started.');
            }

            if ($request->assigned_reviewer_id !== null) {
                $this->authorizer->assertReviewer((string) $request->assigned_reviewer_id, $reviewerId);
            }

            $existing = DB::table('evidence_reviews')->where('review_request_id', $reviewRequestId)->first();

            if ($existing !== null) {
                $this->authorizer->assertReviewer((string) $existing->reviewer_id, $reviewerId);

                return (array) $existing;
            }

            $reviewId = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_requests')->where('id', $reviewRequestId)->update([
                'status' => 'IN_REVIEW',
                'assigned_reviewer_id' => $reviewerId,
                'assigned_at' => $request->assigned_at ?? $now,
                'started_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_reviews')->insert([
                'id' => $reviewId,
                'review_request_id' => $reviewRequestId,
                'evidence_id' => $request->evidence_id,
                'evidence_revision_id' => $request->evidence_revision_id,
                'reviewer_id' => $reviewerId,
                'review_scope_key' => $request->review_scope_key,
                'criterion_refs' => $request->criterion_refs,
                'status' => 'IN_REVIEW',
                'started_at' => $now,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return (array) DB::table('evidence_reviews')->where('id', $reviewId)->firstOrFail();
        });
    }

    /**
     * @param  list<string>  $supportingRevisionIds
     * @return array<string, mixed>
     */
    public function recordFinding(
        string $reviewId,
        string $reviewerId,
        string $criterionKey,
        string $finding,
        string $statement,
        array $supportingRevisionIds,
    ): array {
        try {
            ReviewFindingOutcome::from($finding);
        } catch (ValueError $error) {
            throw new IntakeReviewException('Unknown formal Review Finding outcome.', previous: $error);
        }

        return DB::transaction(function () use (
            $reviewId,
            $reviewerId,
            $criterionKey,
            $finding,
            $statement,
            $supportingRevisionIds,
        ): array {
            $review = DB::table('evidence_reviews')->where('id', $reviewId)->first();

            if ($review === null) {
                throw new IntakeReviewException('Evidence Review was not found.');
            }

            $this->authorizer->assertReviewer((string) $review->reviewer_id, $reviewerId);
            $allowed = EvidenceReviewScopeItem::query()
                ->where('review_request_id', $review->review_request_id)
                ->pluck('evidence_revision_id')
                ->map(static fn (mixed $id): string => (string) $id)
                ->all();

            foreach ($supportingRevisionIds as $revisionId) {
                if (! in_array($revisionId, $allowed, true)) {
                    throw new IntakeReviewException('Review Finding references Evidence outside the formal Review scope.');
                }
            }

            if (DB::table('evidence_review_findings')
                ->where('review_id', $reviewId)
                ->where('criterion_key', trim($criterionKey))
                ->exists()) {
                throw new IntakeReviewException('A Review Finding already exists for this criterion.');
            }

            $id = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_findings')->insert([
                'id' => $id,
                'review_id' => $reviewId,
                'criterion_key' => trim($criterionKey),
                'finding' => $finding,
                'statement' => trim($statement),
                'supporting_evidence_revision_ids' => json_encode($supportingRevisionIds, JSON_THROW_ON_ERROR),
                'recorded_by' => $reviewerId,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_reviews')->where('id', $reviewId)->update([
                'status' => 'READY_FOR_DECISION',
                'updated_at' => $now,
            ]);
            DB::table('evidence_review_requests')->where('id', $review->review_request_id)->update([
                'status' => 'READY_FOR_DECISION',
                'updated_at' => $now,
            ]);

            return (array) DB::table('evidence_review_findings')->where('id', $id)->firstOrFail();
        });
    }

    /**
     * @param  list<array{evidence_id:string,evidence_revision_id:string}>  $items
     * @return list<CanonicalEvidenceReference>
     */
    private function references(array $items): array
    {
        if ($items === []) {
            throw new IntakeReviewException('Formal Review requires at least one canonical Evidence item.');
        }

        $references = [];
        $seen = [];

        foreach ($items as $item) {
            $reference = DecisionItemData::fromArray($item)->reference;

            if (isset($seen[$reference->evidenceId])) {
                throw new IntakeReviewException('A formal Review cannot contain duplicate canonical Evidence items.');
            }

            $seen[$reference->evidenceId] = true;
            $references[] = $reference;
        }

        return $references;
    }

    private function evidenceRevision(CanonicalEvidenceReference $reference): object
    {
        $row = DB::table('governed_evidence as evidence')
            ->join('governed_evidence_revisions as revision', 'revision.evidence_id', '=', 'evidence.id')
            ->where('evidence.id', $reference->evidenceId)
            ->where('revision.id', $reference->evidenceRevisionId)
            ->select([
                'evidence.subject_actor_id',
                'evidence.lifecycle_state',
                'evidence.current_revision_number',
                'evidence.review_status',
                'revision.revision',
            ])
            ->first();

        if ($row === null) {
            throw new IntakeReviewException('Formal Review references an unknown canonical Evidence/Revision pair.');
        }

        return $row;
    }
}
