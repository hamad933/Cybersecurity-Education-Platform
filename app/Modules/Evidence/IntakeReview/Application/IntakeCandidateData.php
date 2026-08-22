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
        public string $handoffReceiptId,
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
    public static function fromArray(array $payload, \stdClass $receipt): self
    {
        foreach ([
            'evidence_claim',
            'governed_purpose',
            'title',
            'summary',
        ] as $required) {
            if (!isset($payload[$required]) || trim((string) $payload[$required]) === '') {
                throw new IntakeReviewException("Missing Candidate Evidence field: {$required}.");
            }
        }

        return new self(
            $receipt->id,
            self::text($receipt->source_type, 64),
            self::text($receipt->source_id, 160),
            self::text($receipt->source_revision, 80),
            $receipt->source_digest,
            is_string($receipt->selected_material_refs) ? json_decode($receipt->selected_material_refs, true) : (is_array($receipt->selected_material_refs) ? $receipt->selected_material_refs : []),
            self::text($receipt->capability_id, 100),
            self::text($payload['evidence_claim'], 4000),
            self::stringList($payload['criterion_scope'] ?? []),
            self::text($payload['governed_purpose'], 180),
            self::text($payload['title'], 180),
            self::text($payload['summary'], 4000),
            is_string($receipt->facts) ? json_decode($receipt->facts, true) : (is_array($receipt->facts) ? $receipt->facts : []),
            is_string($receipt->metadata) ? json_decode($receipt->metadata, true) : (is_array($receipt->metadata) ? $receipt->metadata : []),
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
