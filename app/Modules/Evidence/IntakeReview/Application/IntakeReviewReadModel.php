<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use App\Modules\Evidence\Models\EvidenceCandidateIntakeEvent;
use App\Modules\Evidence\Models\EvidenceReviewDecisionItem;
use App\Modules\Evidence\Models\EvidenceReviewScopeItem;
use Illuminate\Support\Facades\DB;

final class IntakeReviewReadModel
{
    /** @return list<array<string, mixed>> */
    public function candidateTimeline(string $candidateId): array
    {
        return EvidenceCandidateIntakeEvent::query()
            ->where('candidate_id', $candidateId)
            ->orderBy('sequence')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function reviewScope(string $reviewRequestId): array
    {
        return EvidenceReviewScopeItem::query()
            ->where('review_request_id', $reviewRequestId)
            ->orderBy('ordinal')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array{decision:array<string,mixed>,items:list<array<string,mixed>>} */
    public function reviewDecision(string $decisionId): array
    {
        $decision = DB::table('evidence_review_decisions')->where('id', $decisionId)->first();

        if ($decision === null) {
            throw new IntakeReviewException('Evidence Review Decision was not found.');
        }

        return [
            'decision' => (array) $decision,
            'items' => EvidenceReviewDecisionItem::query()
                ->where('decision_id', $decisionId)
                ->orderBy('ordinal')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
        ];
    }
}
