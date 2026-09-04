<?php

namespace App\Modules\Evidence\IntakeReview\Application;

use App\Modules\Evidence\IntakeReview\Domain\IntakeReviewException;

final readonly class IntakeCandidateData
{
    /**
     * @param  list<string>  $selectedMaterialRefs
     * @param  list<string>  $criterionScope
     * @param  array<string, mixed>  $facts
     * @param  array<string, mixed>  $metadata
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
        public ?string $targetEvidenceId = null,
        public ?string $targetEvidenceRevisionId = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload, object $receipt): self
    {
        foreach ([
            'evidence_claim',
            'governed_purpose',
            'title',
            'summary',
        ] as $required) {
            if (! isset($payload[$required]) || trim((string) $payload[$required]) === '') {
                throw new IntakeReviewException("Missing Candidate Evidence field: {$required}.");
            }
        }

        $criterionScope = self::stringList($payload['criterion_scope'] ?? [], 50, 120);
        $governedPurpose = self::text($payload['governed_purpose'], 180);
        if (! in_array($governedPurpose, [
            'FORMAL_CAPABILITY_EVIDENCE',
            'GOVERNED_PROVENANCE_ATTESTATION',
        ], true)) {
            throw new IntakeReviewException('Unsupported governed Evidence purpose.');
        }
        if ($governedPurpose === 'FORMAL_CAPABILITY_EVIDENCE' && $criterionScope === []) {
            throw new IntakeReviewException('Formal capability Evidence requires a governed criterion scope.');
        }

        $sourceDigest = strtolower(trim((string) $receipt->source_digest));
        if (preg_match('/^[a-f0-9]{64}$/', $sourceDigest) !== 1) {
            throw new IntakeReviewException('Source digest must be SHA-256 hex.');
        }

        $targetEvidenceId = isset($payload['target_evidence_id']) ? self::uuid($payload['target_evidence_id']) : null;
        $targetEvidenceRevisionId = isset($payload['target_evidence_revision_id']) ? self::uuid($payload['target_evidence_revision_id']) : null;

        if (($targetEvidenceId === null) !== ($targetEvidenceRevisionId === null)) {
            throw new IntakeReviewException('Target evidence ID and revision ID must be provided together.');
        }

        return new self(
            $receipt->id,
            self::text($receipt->source_type, 64),
            self::text($receipt->source_id, 160),
            self::text($receipt->source_revision, 80),
            $sourceDigest,
            self::stringList(self::decode($receipt->selected_material_refs), 50, 240),
            self::text($receipt->capability_id, 100),
            self::text($payload['evidence_claim'], 4000),
            $criterionScope,
            $governedPurpose,
            self::text($payload['title'], 180),
            self::text($payload['summary'], 4000),
            self::decode($receipt->facts),
            self::decode($receipt->metadata),
            $targetEvidenceId,
            $targetEvidenceRevisionId,
        );
    }

    public function semanticIdentityDigest(ProvenanceDigest $digest, string $subjectActorId): string
    {
        return $digest->digest([
            'subject_actor_id' => $subjectActorId,
            'source_type' => self::identityText($this->sourceType),
            'source_id' => self::identityText($this->sourceId),
            'source_revision' => self::identityText($this->sourceRevision),
            'selected_material_refs' => $this->selectedMaterialRefs,
            'capability_id' => self::identityText($this->capabilityId),
            'evidence_claim' => self::identityText($this->evidenceClaim),
            'criterion_scope' => $this->criterionScope,
            'governed_purpose' => self::identityText($this->governedPurpose),
        ]);
    }

    /** @return list<string> */
    private static function stringList(mixed $value, int $maxItems, int $maxLength): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $maxItems) {
            throw new IntakeReviewException('Expected a bounded list of string references.');
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new IntakeReviewException('Reference lists must contain strings.');
            }
            $text = trim($item);

            if ($text === '' || mb_strlen($text) > $maxLength) {
                throw new IntakeReviewException('Reference lists cannot contain empty values.');
            }

            $items[] = $text;
        }

        $items = array_values(array_unique($items));
        sort($items);

        return $items;
    }

    /** @return array<mixed> */
    private static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || strlen($value) > 65536) {
            throw new IntakeReviewException('JSON payload exceeds 64KiB boundary.');
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new IntakeReviewException('Invalid or non-associative JSON.');
        }

        return $decoded;
    }

    private static function identityText(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    private static function uuid(mixed $value): string
    {
        $text = trim((string) $value);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $text) !== 1) {
            throw new IntakeReviewException('Invalid UUID format.');
        }
        return strtolower($text);
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
