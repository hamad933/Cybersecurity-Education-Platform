<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewAuthorizer;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\IntakeReview\Domain\ReviewDecisionOutcome;
use App\Modules\Evidence\IntakeReview\Domain\ReviewStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use ValueError;

final class ReviewDecisionService
{
    public function __construct(private readonly IntakeReviewAuthorizer $authorizer)
    {
    }

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

            if ($supersedesDecisionId !== null
                && !DB::table('evidence_review_decisions')->where('id', $supersedesDecisionId)->exists()) {
                throw new IntakeReviewException('Superseded Review Decision was not found.');
            }

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
                'rationale' => trim($rationale),
                'decided_by' => $reviewerId,
                'decided_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($items as $item) {
                DB::table('evidence_review_decision_items')->insert([
                    'decision_id' => $decisionId,
                    'evidence_id' => $item->evidence_id,
                    'evidence_revision_id' => $item->evidence_revision_id,
                    'ordinal' => $item->ordinal,
                    'created_at' => $now,
                ]);

                DB::table('governed_evidence')->where('id', $item->evidence_id)->update([
                    'review_status' => ReviewStatus::REVIEWED->value,
                    'effective_review_decision' => $decision,
                    'effective_review_decision_id' => $decisionId,
                    'updated_at' => $now,
                ]);
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

    /** @return Collection<int, stdClass> */
    private function scopeItems(string $reviewRequestId): Collection
    {
        return DB::table('evidence_review_scope_items')
            ->where('review_request_id', $reviewRequestId)
            ->orderBy('ordinal')
            ->get();
    }
}
