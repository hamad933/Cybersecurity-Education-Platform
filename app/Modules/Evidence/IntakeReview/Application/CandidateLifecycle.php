<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\CandidateEvidenceState;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;
use ValueError;

final class CandidateLifecycle
{
    public function assertCanTransition(string $from, string $to): void
    {
        try {
            $current = CandidateEvidenceState::from($from);
            $target = CandidateEvidenceState::from($to);
        } catch (ValueError $error) {
            throw new IntakeReviewException('Unknown Candidate Evidence lifecycle state.', previous: $error);
        }

        foreach ($current->allowedNext() as $allowed) {
            if ($allowed === $target) {
                return;
            }
        }

        throw new IntakeReviewException("Illegal Candidate Evidence transition: {$from} -> {$to}.");
    }
}
