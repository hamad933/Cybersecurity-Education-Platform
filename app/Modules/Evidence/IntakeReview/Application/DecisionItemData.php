<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\CanonicalEvidenceReference;
use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;

final readonly class DecisionItemData
{
    public function __construct(public CanonicalEvidenceReference $reference) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $evidenceId = trim((string) ($payload['evidence_id'] ?? ''));
        $revisionId = trim((string) ($payload['evidence_revision_id'] ?? ''));

        if ($evidenceId === '' || $revisionId === '') {
            throw new IntakeReviewException('Decision items require canonical Evidence and Revision identifiers.');
        }

        return new self(new CanonicalEvidenceReference($evidenceId, $revisionId));
    }
}
