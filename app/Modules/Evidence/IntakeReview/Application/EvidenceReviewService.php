<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\CanonicalEvidenceReference;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewAuthorizer;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\IntakeReview\Domain\ReviewFindingOutcome;
use App\Modules\Evidence\IntakeReview\Domain\ReviewStatus;
use App\Modules\Evidence\Models\EvidenceReviewDecisionItem;
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
        $reviewScopeKey = $this->text($reviewScopeKey, 160, 'Review scope');
        $criterionRefs = $this->stringList($criterionRefs, 50, 120, 'Review criterion references');
        $purpose = $this->text($purpose, 180, 'Review purpose');
        $assignedReviewerId = $assignedReviewerId === null ? null : trim($assignedReviewerId);

        if ($assignedReviewerId === null || ! Str::isUuid($assignedReviewerId)) {
            throw new IntakeReviewException('Formal Review requires an explicitly assigned reviewer.');
        }

        return DB::transaction(function () use (
            $references,
            $requestedBy,
            $reviewScopeKey,
            $criterionRefs,
            $purpose,
            $assignedReviewerId,
        ): array {
            $subjectActorId = null;
            $allowedCriteria = [];
            $requiresCriteria = false;

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

                $allowedCriteria = array_values(array_unique([
                    ...$allowedCriteria,
                    ...$this->decodeStringList($row->criterion_scope),
                ]));
                $requiresCriteria = $requiresCriteria
                    || (string) $row->governed_purpose === 'FORMAL_CAPABILITY_EVIDENCE';
            }

            if ($subjectActorId === null) {
                throw new IntakeReviewException('Formal Review requires at least one canonical Evidence item.');
            }

            $this->authorizer->assertSubjectActor($subjectActorId, $requestedBy);

            if ($requiresCriteria && $criterionRefs === []) {
                throw new IntakeReviewException('Formal capability Evidence requires a non-empty governed Review criterion scope.');
            }

            $outsideScope = array_values(array_diff($criterionRefs, $allowedCriteria));
            if ($outsideScope !== []) {
                throw new IntakeReviewException(
                    'Formal Review criterion references are outside the pinned Evidence criterion scope: '
                    .implode(', ', $outsideScope).'.',
                );
            }

            $requestId = (string) Str::uuid7();
            $primary = $references[0];
            $now = now();
            $priorDecisionId = $this->priorDecisionId($references, $reviewScopeKey);

            DB::table('evidence_review_requests')->insert([
                'id' => $requestId,
                'evidence_id' => $primary->evidenceId,
                'evidence_revision_id' => $primary->evidenceRevisionId,
                'requested_by' => $requestedBy,
                'review_scope_key' => $reviewScopeKey,
                'criterion_refs' => json_encode($criterionRefs, JSON_THROW_ON_ERROR),
                'purpose' => $purpose,
                'prior_decision_id' => $priorDecisionId,
                'status' => 'ASSIGNED',
                'requested_at' => $now,
                'assigned_reviewer_id' => $assignedReviewerId,
                'assigned_at' => $now,
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
            (string) ($data['assigned_reviewer_id'] ?? $requestedBy),
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

            if ($request->assigned_reviewer_id === null) {
                throw new IntakeReviewException('Formal Review cannot be claimed without an explicit reviewer assignment.');
            }
            $this->authorizer->assertReviewer((string) $request->assigned_reviewer_id, $reviewerId);

            $existing = DB::table('evidence_reviews')->where('review_request_id', $reviewRequestId)->first();

            if ($existing !== null) {
                $this->authorizer->assertReviewer((string) $existing->reviewer_id, $reviewerId);

                return (array) $existing;
            }

            $reviewId = (string) Str::uuid7();
            $now = now();
            $criteria = $this->decodeStringList($request->criterion_refs);
            $initialStatus = $criteria === [] ? 'READY_FOR_DECISION' : 'IN_REVIEW';

            $scopeItems = EvidenceReviewScopeItem::query()
                ->where('review_request_id', $reviewRequestId)
                ->orderBy('ordinal')
                ->get();
            if ($scopeItems->isEmpty()) {
                throw new IntakeReviewException('Formal Review requires canonical Evidence scope items.');
            }
            foreach ($scopeItems as $scopeItem) {
                $current = $this->evidenceRevision(new CanonicalEvidenceReference(
                    (string) $scopeItem->evidence_id,
                    (string) $scopeItem->evidence_revision_id,
                ));
                if ((string) $current->lifecycle_state !== 'ACTIVE'
                    || (int) $current->current_revision_number !== (int) $current->revision) {
                    throw new IntakeReviewException('Formal Review scope is stale and must be requested again.');
                }
            }

            DB::table('evidence_review_requests')->where('id', $reviewRequestId)->update([
                'status' => $initialStatus,
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
                'status' => $initialStatus,
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

        $criterionKey = $this->text($criterionKey, 120, 'Review Finding criterion');
        $statement = $this->text($statement, 4000, 'Review Finding statement');
        $supportingRevisionIds = $this->stringList(
            $supportingRevisionIds,
            50,
            64,
            'Supporting Evidence Revision references',
        );

        return DB::transaction(function () use (
            $reviewId,
            $reviewerId,
            $criterionKey,
            $finding,
            $statement,
            $supportingRevisionIds,
        ): array {
            $review = DB::table('evidence_reviews')->where('id', $reviewId)->lockForUpdate()->first();

            if ($review === null) {
                throw new IntakeReviewException('Evidence Review was not found.');
            }

            $this->authorizer->assertReviewer((string) $review->reviewer_id, $reviewerId);
            if (! in_array((string) $review->status, ['IN_REVIEW', 'READY_FOR_DECISION'], true)) {
                throw new IntakeReviewException('Evidence Review is not open for Findings.');
            }

            $criteria = $this->decodeStringList($review->criterion_refs);
            if (! in_array($criterionKey, $criteria, true)) {
                throw new IntakeReviewException('Review Finding criterion is outside the pinned Review criterion scope.');
            }

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
                ->where('criterion_key', $criterionKey)
                ->exists()) {
                throw new IntakeReviewException('A Review Finding already exists for this criterion.');
            }

            $id = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_findings')->insert([
                'id' => $id,
                'review_id' => $reviewId,
                'criterion_key' => $criterionKey,
                'finding' => $finding,
                'statement' => $statement,
                'supporting_evidence_revision_ids' => json_encode($supportingRevisionIds, JSON_THROW_ON_ERROR),
                'recorded_by' => $reviewerId,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $covered = DB::table('evidence_review_findings')
                ->where('review_id', $reviewId)
                ->pluck('criterion_key')
                ->map(static fn (mixed $value): string => (string) $value)
                ->unique()
                ->all();
            $complete = array_diff($criteria, $covered) === [];

            if ($complete) {
                DB::table('evidence_reviews')->where('id', $reviewId)->update([
                    'status' => 'READY_FOR_DECISION',
                    'updated_at' => $now,
                ]);
                DB::table('evidence_review_requests')->where('id', $review->review_request_id)->update([
                    'status' => 'READY_FOR_DECISION',
                    'updated_at' => $now,
                ]);
            }

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

    /** @param list<CanonicalEvidenceReference> $references */
    private function priorDecisionId(array $references, string $reviewScopeKey): ?string
    {
        $evidenceIds = array_map(
            static fn (CanonicalEvidenceReference $reference): string => $reference->evidenceId,
            $references,
        );
        sort($evidenceIds, SORT_STRING);

        $decisions = DB::table('evidence_review_decisions as decision')
            ->where('decision.review_scope_key', $reviewScopeKey)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('evidence_review_decisions as successor')
                    ->whereColumn('successor.supersedes_decision_id', 'decision.id');
            })
            ->orderByDesc('decision.decided_at')
            ->orderByDesc('decision.id')
            ->get(['decision.id']);

        foreach ($decisions as $decision) {
            $priorEvidenceIds = EvidenceReviewDecisionItem::query()
                ->where('decision_id', $decision->id)
                ->pluck('evidence_id')
                ->map(static fn (mixed $value): string => (string) $value)
                ->sort()
                ->values()
                ->all();

            if ($priorEvidenceIds === $evidenceIds) {
                return (string) $decision->id;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function stringList(mixed $value, int $maxItems, int $maxLength, string $label): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $maxItems) {
            throw new IntakeReviewException("{$label} must be a bounded list.");
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '' || mb_strlen(trim($item)) > $maxLength) {
                throw new IntakeReviewException("{$label} contains an invalid reference.");
            }
            $items[] = trim($item);
        }
        $items = array_values(array_unique($items));
        sort($items, SORT_STRING);

        return $items;
    }

    /** @return list<string> */
    private function decodeStringList(mixed $value): array
    {
        $decoded = is_array($value)
            ? $value
            : json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return $this->stringList($decoded, 50, 120, 'Review criterion references');
    }

    private function text(string $value, int $maxLength, string $label): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new IntakeReviewException("{$label} is invalid.");
        }

        return $value;
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
                'evidence.governed_purpose',
                'revision.revision',
                'revision.criterion_scope',
            ])
            ->first();

        if ($row === null) {
            throw new IntakeReviewException('Formal Review references an unknown canonical Evidence/Revision pair.');
        }

        return $row;
    }
}
