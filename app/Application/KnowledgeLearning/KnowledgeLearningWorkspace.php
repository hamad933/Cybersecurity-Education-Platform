<?php

namespace App\Application\KnowledgeLearning;

use App\Modules\Curriculum\Application\CurriculumKnowledgeService;
use App\Modules\Knowledge\Application\KnowledgeLibraryService;
use App\Modules\Learning\Application\KnowledgeJourneyService;
use App\Modules\SourceGovernance\Application\KnowledgeQualityService;

final class KnowledgeLearningWorkspace
{
    private readonly KnowledgeLibraryService $knowledge;

    private readonly CurriculumKnowledgeService $curriculum;

    private readonly KnowledgeJourneyService $journey;

    private readonly KnowledgeQualityService $quality;

    public function __construct(
        KnowledgeLibraryService $knowledge,
        CurriculumKnowledgeService $curriculum,
        KnowledgeJourneyService $journey,
        KnowledgeQualityService $quality,
    ) {
        $this->knowledge = $knowledge;
        $this->curriculum = $curriculum;
        $this->journey = $journey;
        $this->quality = $quality;
    }

    /** @return array<string, mixed> */
    public function library(?string $requestedUnitId, ?string $requestedRevisionId): array
    {
        $catalog = $this->knowledge->catalog();
        $activeUnitId = $this->knowledge->resolveUnitId($requestedUnitId);
        $active = $this->knowledge->unit($activeUnitId, $requestedRevisionId);
        $placements = $this->curriculum->placements(array_column($catalog, 'id'));
        $citations = $this->activeCitations($active);

        return [
            'catalog' => $catalog,
            'structure' => $this->structure($catalog, $placements),
            'active' => $active,
            'context' => [
                'placements' => $this->curriculum->placementsForUnit($activeUnitId),
                'sources' => $this->quality->sourcesForClaims($citations),
                'unresolved_citation_count' => $this->unresolvedCitationCount($citations),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function learn(?string $requestedUnitId, string $actorId): array
    {
        $catalog = $this->knowledge->catalog();
        $activeUnitId = $this->knowledge->resolveUnitId($requestedUnitId);
        $active = $this->knowledge->unit($activeUnitId);

        return [
            'catalog' => $catalog,
            'active' => $active === null ? null : [
                'id' => $active['id'],
                'title_ar' => $active['title_ar'],
                'title_en' => $active['title_en'],
                'revision' => $active['revision'] === null ? null : [
                    'id' => $active['revision']['id'],
                    'revision' => $active['revision']['revision'],
                    'state' => $active['revision']['state'],
                ],
            ],
            'journey' => $this->journey->forUnit($activeUnitId, $actorId),
            'semantic_boundary' => [
                'progress' => 'journey_activity_context',
                'mastery' => 'owned_by_progress_evidence',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function visualize(?string $requestedUnitId): array
    {
        $catalog = $this->knowledge->catalog();
        $activeUnitId = $this->knowledge->resolveUnitId($requestedUnitId);
        $active = $this->knowledge->unit($activeUnitId);
        $placements = $this->curriculum->placementsForUnit($activeUnitId);

        $nodes = [];
        if ($active !== null) {
            $nodes[] = [
                'id' => 'ku:'.$active['id'],
                'kind' => 'knowledge_unit',
                'label' => $active['title_ar'],
                'technical_label' => $active['id'],
            ];
        }
        foreach (collect($placements)->pluck('capability_id')->unique()->values() as $capabilityId) {
            $nodes[] = [
                'id' => 'capability:'.$capabilityId,
                'kind' => 'capability',
                'label' => $capabilityId,
                'technical_label' => $capabilityId,
            ];
        }

        $edges = array_map(static fn (array $placement): array => [
            'id' => 'placement:'.$placement['id'],
            'from' => 'capability:'.$placement['capability_id'],
            'to' => 'ku:'.$placement['knowledge_unit_id'],
            'type' => 'canonical_placement',
            'revision' => $placement['revision'],
            'lifecycle' => $placement['lifecycle'],
        ], $placements);

        return [
            'catalog' => $catalog,
            'active' => $active === null ? null : [
                'id' => $active['id'],
                'title_ar' => $active['title_ar'],
                'title_en' => $active['title_en'],
            ],
            'map' => [
                'saved' => false,
                'id' => null,
                'state' => 'NO_PERSISTED_MAP_MODEL_IN_WAVE1',
            ],
            'view' => [
                'implemented' => ['Tree', 'Graph'],
                'not_implemented' => ['Path', 'Canvas'],
            ],
            'overlay' => [
                'active' => null,
                'available' => [],
            ],
            'graph' => [
                'nodes' => $nodes,
                'edges' => $edges,
                'source' => 'curriculum_placements',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function researchQuality(?string $requestedUnitId, ?string $requestedSourceId): array
    {
        $catalog = $this->knowledge->catalog();
        $activeUnitId = $this->knowledge->resolveUnitId($requestedUnitId);
        $active = $this->knowledge->unit($activeUnitId);
        $citations = $this->activeCitations($active);

        return [
            'catalog' => $catalog,
            'active' => $active === null ? null : [
                'id' => $active['id'],
                'title_ar' => $active['title_ar'],
                'title_en' => $active['title_en'],
                'revision' => $active['revision'] === null ? null : [
                    'id' => $active['revision']['id'],
                    'revision' => $active['revision']['revision'],
                    'state' => $active['revision']['state'],
                    'citations' => $active['revision']['citations'],
                ],
            ],
            'quality' => $this->quality->workspace($requestedSourceId, $citations),
            'semantic_boundary' => [
                'review' => 'knowledge_quality',
                'evidence_review' => 'owned_by_progress_evidence',
                'mastery_judgment' => 'owned_by_progress_evidence',
            ],
        ];
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    public function updateRevision(string $revisionId, int $expectedLockVersion, array $blocks, array $citations, string $actorId): void
    {
        $this->knowledge->updateRevision($revisionId, $expectedLockVersion, $blocks, $citations, $actorId);
    }

    /** @return array{id: string, knowledge_unit_id: string} */
    public function restoreRevision(string $revisionId, string $actorId): array
    {
        return $this->knowledge->restoreRevision($revisionId, $actorId);
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @param  list<array<string, mixed>>  $placements
     * @return list<array<string, mixed>>
     */
    private function structure(array $catalog, array $placements): array
    {
        $catalogById = [];
        foreach ($catalog as $item) {
            $id = $item['id'] ?? null;
            if (is_string($id)) {
                $catalogById[$id] = $item;
            }
        }

        /** @var array<string, array<string, true>> $unitIdsByCapability */
        $unitIdsByCapability = [];
        /** @var array<string, true> $placedIds */
        $placedIds = [];

        foreach ($placements as $placement) {
            $capabilityId = $placement['capability_id'] ?? null;
            $unitId = $placement['knowledge_unit_id'] ?? null;
            if (! is_string($capabilityId) || ! is_string($unitId)) {
                continue;
            }

            $unitIdsByCapability[$capabilityId][$unitId] = true;
            $placedIds[$unitId] = true;
        }

        $groups = [];
        foreach ($unitIdsByCapability as $capabilityId => $unitIds) {
            $items = [];
            foreach (array_keys($unitIds) as $unitId) {
                if (isset($catalogById[$unitId])) {
                    $items[] = $catalogById[$unitId];
                }
            }

            $groups[] = [
                'capability_id' => $capabilityId,
                'items' => $items,
            ];
        }

        $unplaced = [];
        foreach ($catalog as $item) {
            $id = $item['id'] ?? null;
            if (is_string($id) && ! isset($placedIds[$id])) {
                $unplaced[] = $item;
            }
        }
        if ($unplaced !== []) {
            $groups[] = ['capability_id' => null, 'items' => $unplaced];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>|null  $active
     * @return list<string>
     */
    private function activeCitations(?array $active): array
    {
        $citations = $active['revision']['citations'] ?? [];

        return is_array($citations) ? array_values(array_filter($citations, 'is_string')) : [];
    }

    /** @param list<string> $citations */
    private function unresolvedCitationCount(array $citations): int
    {
        if ($citations === []) {
            return 0;
        }

        $resolvedClaimIds = [];
        foreach ($this->quality->sourcesForClaims($citations) as $source) {
            $claims = $source['claims'] ?? [];
            if (! is_array($claims)) {
                continue;
            }

            foreach ($claims as $claim) {
                if (! is_array($claim)) {
                    continue;
                }

                $claimId = $claim['claim_id'] ?? null;
                if (is_string($claimId)) {
                    $resolvedClaimIds[$claimId] = true;
                }
            }
        }

        return max(0, count(array_unique($citations)) - count($resolvedClaimIds));
    }
}
