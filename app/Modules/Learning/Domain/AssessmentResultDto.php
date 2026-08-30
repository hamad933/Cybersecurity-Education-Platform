<?php

namespace App\Modules\Learning\Domain;

/**
 * Exposes a typed DTO handoff boundary for later MOD-EVD consumption.
 * This writer MUST NOT create an EvidenceRecord or accept/reject evidence.
 */
readonly class AssessmentResultDto
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $origin,
        public string $capability_id,
        public string $knowledge_unit_id,
        public string $outcome,
        public array $payload,
    ) {
    }
}
