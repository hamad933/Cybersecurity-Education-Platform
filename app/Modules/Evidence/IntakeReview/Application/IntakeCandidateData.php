<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;

final readonly class IntakeCandidateData
{
    /**
     * @param list<string> $selectedMaterialRefs
     * @param list<string> $criterionScope
     * @param array<string, mixed> $facts
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $sourceType,
        public string $sourceId,
        public string $sourceRevision,
        public string $sourceDigest,
        public array $selectedMaterialRefs,
        public string $capabilityId,
        public string $evidenceClaim,
        public array $criterionScope,
        public string $governedPurpose,
        public string $title,
        public string $summary,
        public array $facts,
        public array $metadata,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        foreach ([
            'source_type',
            'source_id',
            'source_revision',
            'source_digest',
            'capability_id',
            'evidence_claim',
            'governed_purpose',
            'title',
            'summary',
        ] as $required) {
            if (!isset($payload[$required]) || trim((string) $payload[$required]) === '') {
                throw new IntakeReviewException("Missing Candidate Evidence field: {$required}.");
            }
        }

        $sourceDigest = strtolower(trim((string) $payload['source_digest']));

        if (!preg_match('/^[a-f0-9]{64}$/', $sourceDigest)) {
            throw new IntakeReviewException('Source digest must be a SHA-256 hex digest.');
        }

        return new self(
            self::text($payload['source_type'], 64),
            self::text($payload['source_id'], 160),
            self::text($payload['source_revision'], 80),
            $sourceDigest,
            self::stringList($payload['selected_material_refs'] ?? []),
            self::text($payload['capability_id'], 100),
            self::text($payload['evidence_claim'], 4000),
            self::stringList($payload['criterion_scope'] ?? []),
            self::text($payload['governed_purpose'], 180),
            self::text($payload['title'], 180),
            self::text($payload['summary'], 4000),
            is_array($payload['facts'] ?? null) ? $payload['facts'] : [],
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    public function semanticIdentityDigest(ProvenanceDigest $digest, string $subjectActorId): string
    {
        return $digest->digest([
            'subject_actor_id' => $subjectActorId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'source_revision' => $this->sourceRevision,
            'selected_material_refs' => $this->selectedMaterialRefs,
            'evidence_claim' => $this->evidenceClaim,
            'criterion_scope' => $this->criterionScope,
            'governed_purpose' => $this->governedPurpose,
        ]);
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            throw new IntakeReviewException('Expected a list of string references.');
        }

        $items = [];

        foreach ($value as $item) {
            $text = trim((string) $item);

            if ($text === '') {
                throw new IntakeReviewException('Reference lists cannot contain empty values.');
            }

            $items[] = $text;
        }

        $items = array_values(array_unique($items));
        sort($items);

        return $items;
    }

    private static function text(mixed $value, int $maxLength): string
    {
        $text = trim((string) $value);

        if ($text === '' || mb_strlen($text) > $maxLength) {
            throw new IntakeReviewException("Text must contain between 1 and {$maxLength} characters.");
        }

        return $text;
    }
}
