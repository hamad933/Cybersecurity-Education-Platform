<?php

namespace App\Modules\Evidence\IntakeReview\Domain;

final readonly class CanonicalEvidenceReference
{
    public function __construct(
        public string $evidenceId,
        public string $evidenceRevisionId,
    ) {
        if ($this->evidenceId === '' || $this->evidenceRevisionId === '') {
            throw new IntakeReviewException('Evidence and Evidence Revision identifiers are required.');
        }
    }

    /** @return array{evidence_id:string,evidence_revision_id:string} */
    public function toArray(): array
    {
        return [
            'evidence_id' => $this->evidenceId,
            'evidence_revision_id' => $this->evidenceRevisionId,
        ];
    }
}
