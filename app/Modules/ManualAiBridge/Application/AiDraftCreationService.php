<?php

namespace App\Modules\ManualAiBridge\Application;

use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use InvalidArgumentException;

final class AiDraftCreationService
{
    public function __construct(private readonly LessonRevisionWorkflow $lessons) {}

    /** @param array<string,mixed> $result */
    public function create(array $result, string $actorId): string
    {
        $knowledgeUnit = $result['knowledge_unit_id'] ?? null;
        $blocks = $result['proposed_blocks'] ?? null;
        $citations = $result['citation_claim_ids'] ?? null;
        if (! is_string($knowledgeUnit) || ! is_array($blocks) || ! is_array($citations)) {
            throw new InvalidArgumentException('AI result cannot be converted into a lesson draft.');
        }
        $draft = $this->lessons->createDraft(
            $knowledgeUnit,
            $blocks,
            $citations,
            isset($result['derived_from_revision_id']) && is_string($result['derived_from_revision_id']) ? $result['derived_from_revision_id'] : null,
            $actorId,
            isset($result['authority_baseline_id']) && is_string($result['authority_baseline_id']) ? $result['authority_baseline_id'] : null,
        );

        return (string) $draft->id;
    }
}
