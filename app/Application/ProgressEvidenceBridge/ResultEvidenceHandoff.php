<?php

namespace App\Application\ProgressEvidenceBridge;

use InvalidArgumentException;

final readonly class ResultEvidenceHandoff
{
    /**
     * @param  array<mixed>  $artifactRefs
     * @param  array<mixed>  $criterionScope
     */
    public function __construct(
        public string $subjectActorId,
        public string $deliveredBy,
        public string $sourceResultId,
        public int $sourceResultRevision,
        public string $sourceResultDigest,
        public string $manifestDigest,
        public array $artifactRefs,
        public string $capabilityId,
        public string $evidenceClaim,
        public array $criterionScope,
        public string $title,
        public string $summary,
        public string $provenance,
        public bool $sourceFixture,
        public ?string $sourceHandoffId = null,
    ) {
        self::boundedText($subjectActorId, 64, 'Subject actor ID');
        self::boundedText($deliveredBy, 64, 'Delivery actor ID');
        self::boundedText($sourceResultId, 160, 'Source Result ID');
        self::boundedText($capabilityId, 100, 'Capability ID');
        self::boundedText($evidenceClaim, 4000, 'Evidence claim');
        self::boundedText($title, 180, 'Candidate Evidence title');
        self::boundedText($summary, 4000, 'Candidate Evidence summary');
        self::boundedText($provenance, 500, 'Result provenance');

        if ($sourceResultRevision < 1) {
            throw new InvalidArgumentException('Source Result revision must be positive.');
        }
        self::digest($sourceResultDigest, 'Source Result digest');
        self::digest($manifestDigest, 'Result Handoff manifest digest');
        self::stringList($artifactRefs, 20, 240, 'Result artifact references', false);
        self::stringList($criterionScope, 50, 120, 'Candidate Evidence criterion scope', true);

        if ($sourceHandoffId !== null) {
            self::boundedText($sourceHandoffId, 160, 'Source Handoff ID');
        }
    }

    private static function boundedText(string $value, int $max, string $label): void
    {
        if (trim($value) === '' || mb_strlen(trim($value)) > $max) {
            throw new InvalidArgumentException("{$label} is invalid.");
        }
    }

    private static function digest(string $value, string $label): void
    {
        if (preg_match('/^[a-f0-9]{64}$/', strtolower(trim($value))) !== 1) {
            throw new InvalidArgumentException("{$label} must be SHA-256 hex.");
        }
    }

    /** @param array<mixed> $values */
    private static function stringList(
        array $values,
        int $maxItems,
        int $maxLength,
        string $label,
        bool $required,
    ): void {
        if (! array_is_list($values) || count($values) > $maxItems || ($required && $values === [])) {
            throw new InvalidArgumentException("{$label} must be a bounded non-empty list.");
        }

        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $maxLength) {
                throw new InvalidArgumentException("{$label} contains an invalid reference.");
            }
        }
    }
}
