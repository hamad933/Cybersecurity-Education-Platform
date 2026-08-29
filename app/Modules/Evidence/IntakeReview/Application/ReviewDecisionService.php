<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewAuthorizer;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\IntakeReview\Domain\ReviewDecisionOutcome;
use App\Modules\Evidence\IntakeReview\Domain\ReviewStatus;
use App\Modules\Evidence\Models\EvidenceEffectiveReviewDecision;
use App\Modules\Evidence\Models\EvidenceReviewDecisionItem;
use App\Modules\Evidence\Models\EvidenceReviewScopeItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ValueError;

final class ReviewDecisionService
{
    public function __construct(private readonly IntakeReviewAuthorizer $authorizer) {}

    /** @return array<string, mixed> */
    public function recordDecision(
        string $reviewId,
        string $reviewerId,
        string $decision,
        string $rationale,
        ?string $supersedesDecisionId = null,
    ): array {
        try {
            ReviewDecisionOutcome::from($decision);
        } catch (ValueError $error) {
            throw new IntakeReviewException('Unknown formal Review Decision outcome.', previous: $error);
        }

        $rationale = trim($rationale);
        if ($rationale === '' || mb_strlen($rationale) > 4000) {
            throw new IntakeReviewException('Formal Review Decision rationale is invalid.');
        }

        return DB::transaction(function () use (
            $reviewId,
            $reviewerId,
            $decision,
            $rationale,
            $supersedesDecisionId,
        ): array {
            $review = DB::table('evidence_reviews')->where('id', $reviewId)->lockForUpdate()->first();

            if ($review === null) {
                throw new IntakeReviewException('Evidence Review was not found.');
            }

            $this->authorizer->assertReviewer((string) $review->reviewer_id, $reviewerId);

            if ((string) $review->status !== 'READY_FOR_DECISION') {
                throw new IntakeReviewException('Formal Review must have recorded Findings before a Decision.');
            }

            if (DB::table('evidence_review_decisions')->where('review_id', $reviewId)->exists()) {
                throw new IntakeReviewException('A formal Review Decision already exists for this Review.');
            }

            $items = $this->scopeItems((string) $review->review_request_id);

            if ($items->isEmpty()) {
                throw new IntakeReviewException('Formal Review Decision requires canonical Evidence scope items.');
            }

            $request = DB::table('evidence_review_requests')
                ->where('id', $review->review_request_id)
                ->first();
            if ($request === null) {
                throw new IntakeReviewException('Evidence Review Request was not found.');
            }
            $expectedPriorDecisionId = $request->prior_decision_id === null
                ? null
                : (string) $request->prior_decision_id;

            if ($supersedesDecisionId !== null
                && ! hash_equals($expectedPriorDecisionId ?? '', $supersedesDecisionId)) {
                throw new IntakeReviewException('Supersession must use the prior Decision pinned by the Review Request.');
            }
            $supersedesDecisionId = $expectedPriorDecisionId;

            $this->assertCompleteCriteria($review);
            $this->assertSupersessionLineage($supersedesDecisionId, (string) $review->review_scope_key, $items);

            $decisionId = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_review_decisions')->insert([
                'id' => $decisionId,
                'review_id' => $reviewId,
                'evidence_id' => $review->evidence_id,
                'evidence_revision_id' => $review->evidence_revision_id,
                'supersedes_decision_id' => $supersedesDecisionId,
                'review_scope_key' => $review->review_scope_key,
                'decision' => $decision,
                'rationale' => $rationale,
                'decided_by' => $reviewerId,
                'decided_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($items as $item) {
                EvidenceReviewDecisionItem::query()->insert([
                    'decision_id' => $decisionId,
                    'evidence_id' => $item->evidence_id,
                    'evidence_revision_id' => $item->evidence_revision_id,
                    'ordinal' => $item->ordinal,
                    'created_at' => $now,
                ]);

                $evidence = DB::table('governed_evidence as evidence')
                    ->join('governed_evidence_revisions as revision', function ($join): void {
                        $join->on('revision.evidence_id', '=', 'evidence.id')
                            ->on('revision.revision', '=', 'evidence.current_revision_number');
                    })
                    ->where('evidence.id', $item->evidence_id)
                    ->select('evidence.id', 'evidence.lifecycle_state', 'revision.id as current_revision_id')
                    ->lockForUpdate()
                    ->first();

                if ($evidence !== null
                    && (string) $evidence->lifecycle_state === 'ACTIVE'
                    && (string) $evidence->current_revision_id === (string) $item->evidence_revision_id) {
                    EvidenceEffectiveReviewDecision::query()->upsert([[
                        'evidence_id' => (string) $item->evidence_id,
                        'review_scope_key' => (string) $review->review_scope_key,
                        'evidence_revision_id' => (string) $item->evidence_revision_id,
                        'decision_id' => $decisionId,
                        'decision' => $decision,
                        'decided_at' => $now,
                        'projected_at' => $now,
                    ]], ['evidence_id', 'review_scope_key'], [
                        'evidence_revision_id',
                        'decision_id',
                        'decision',
                        'decided_at',
                        'projected_at',
                    ]);

                    // Backward-compatible display projection only. Governance queries use
                    // evidence_effective_review_decisions, keyed by Evidence and scope.
                    DB::table('governed_evidence')->where('id', $item->evidence_id)->update([
                        'review_status' => ReviewStatus::REVIEWED->value,
                        'effective_review_decision' => $decision,
                        'effective_review_decision_id' => $decisionId,
                        'updated_at' => $now,
                    ]);
                }
            }

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

            return (array) DB::table('evidence_review_decisions')->where('id', $decisionId)->firstOrFail();
        });
    }

    /** @return Collection<int, EvidenceReviewScopeItem> */
    private function scopeItems(string $reviewRequestId): Collection
    {
        return EvidenceReviewScopeItem::query()
            ->where('review_request_id', $reviewRequestId)
            ->orderBy('ordinal')
            ->get();
    }

    private function assertCompleteCriteria(object $review): void
    {
        $criteria = json_decode((string) $review->criterion_refs, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($criteria) || ! array_is_list($criteria)) {
            throw new IntakeReviewException('Formal Review criterion scope is malformed.');
        }

        $covered = DB::table('evidence_review_findings')
            ->where('review_id', $review->id)
            ->pluck('criterion_key')
            ->map(static fn (mixed $value): string => (string) $value)
            ->unique()
            ->all();
        if (array_diff($criteria, $covered) !== []) {
            throw new IntakeReviewException('Formal Review Decision requires complete pinned criterion Findings.');
        }
    }

    /** @param Collection<int, EvidenceReviewScopeItem> $items */
    private function assertSupersessionLineage(
        ?string $priorDecisionId,
        string $reviewScopeKey,
        Collection $items,
    ): void {
        if ($priorDecisionId === null) {
            return;
        }

        $prior = DB::table('evidence_review_decisions')->where('id', $priorDecisionId)->first();
        if ($prior === null || (string) $prior->review_scope_key !== $reviewScopeKey) {
            throw new IntakeReviewException('Superseded Review Decision is outside the exact Review scope.');
        }
        if (DB::table('evidence_review_decisions')
            ->where('supersedes_decision_id', $priorDecisionId)
            ->exists()) {
            throw new IntakeReviewException('Superseded Review Decision is not the current lineage tip.');
        }

        $currentEvidenceIds = $items->pluck('evidence_id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->sort()
            ->values()
            ->all();
        $priorEvidenceIds = EvidenceReviewDecisionItem::query()
            ->where('decision_id', $priorDecisionId)
            ->pluck('evidence_id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->sort()
            ->values()
            ->all();

        if ($currentEvidenceIds !== $priorEvidenceIds) {
            throw new IntakeReviewException('Superseding Review Decision must preserve exact Evidence scope membership.');
        }
    }
}
