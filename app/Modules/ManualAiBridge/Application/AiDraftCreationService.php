<?php

namespace App\Modules\ManualAiBridge\Application;

use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use InvalidArgumentException;

final class AiDraftCreationService
{
    public function __construct(private readonly LessonRevisionWorkflow $lessons) {}

    /** @param array<string,mixed> $result */
    public function create(array $result, string $proposalId, string $actorId): string
    {
        $knowledgeUnit = $result['knowledge_unit_id'] ?? null;
        $blocks = $result['proposed_blocks'] ?? null;
        $citations = $result['citation_claim_ids'] ?? null;
        if (! is_string($knowledgeUnit) || ! is_array($blocks) || ! is_array($citations)) {
            throw new InvalidArgumentException('AI result cannot be converted into a lesson draft.');
        }

        $selectedBlock = null;
        foreach ($blocks as $block) {
            if (($block['proposal_id'] ?? null) === $proposalId) {
                $selectedBlock = $block;
                break;
            }
        }

        if ($selectedBlock === null) {
            throw new InvalidArgumentException('Target proposal ID not found in the AI result blocks.');
        }

        $draft = $this->lessons->createDraft(
            $knowledgeUnit,
            [$selectedBlock],
            $citations,
            isset($result['derived_from_revision_id']) && is_string($result['derived_from_revision_id']) ? $result['derived_from_revision_id'] : null,
            $actorId,
            isset($result['authority_baseline_id']) && is_string($result['authority_baseline_id']) ? $result['authority_baseline_id'] : null,
        );

        return (string) $draft->id;
    }
}
