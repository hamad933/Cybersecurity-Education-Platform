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
        $selection = $this->knowledge->resolveUnitSelection($requestedUnitId);
        $activeUnitId = $selection['resolved_id'];
        $active = $this->knowledge->unit($activeUnitId, $requestedRevisionId);
        $placements = $this->curriculum->placements(array_column($catalog, 'id'));
        $citations = $this->activeCitations($active);
        $activePlacements = $this->curriculum->placementsForUnit($activeUnitId);

        return [
            'catalog' => $catalog,
            'structure' => $this->knowledge->hierarchyProjection($placements, []),
            'active' => $active,
            'selection' => $selection,
            'content_contract' => $this->knowledge->contentContract(),
            'capability_manifest' => $this->knowledge->capabilityManifest(),
            'context' => [
                'placements' => $activePlacements,
                'sources' => $this->quality->sourcesForClaims($citations),
                'unresolved_citation_count' => $this->unresolvedCitationCount($citations),
                'hierarchy_state' => $activePlacements === []
                    ? 'NO_CURRICULUM_PLACEMENT'
                    : 'PARENT_DOMAIN_CLUSTER_CONTEXT_REQUIRED',
                'navigation' => $this->unitNavigation($activeUnitId),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function learn(?string $requestedUnitId, string $actorId): array
    {
        $catalog = $this->knowledge->catalog();
        $selection = $this->knowledge->resolveUnitSelection($requestedUnitId);
        $activeUnitId = $selection['resolved_id'];
        $learningUnit = $this->knowledge->learningUnit($activeUnitId);
        $lesson = is_array($learningUnit['lesson'] ?? null) ? $learningUnit['lesson'] : [
            'availability' => 'UNAVAILABLE_NO_CANONICAL_UNIT',
            'selection_policy' => 'latest_published_revision_only',
            'revision' => null,
            'unavailable_reason' => 'No canonical Knowledge Unit is available for learning delivery.',
        ];
        $active = $learningUnit;
        if (is_array($active)) {
            unset($active['lesson']);
        }
        $lessonCitations = $lesson['revision']['citations'] ?? [];
        $lessonCitations = is_array($lessonCitations)
            ? array_values(array_filter($lessonCitations, 'is_string'))
            : [];

        return [
            'catalog' => $catalog,
            'active' => $active,
            'lesson' => $lesson,
            'journey' => $this->journey->forUnit($activeUnitId, $actorId),
            'selection' => $selection,
            'content_contract' => $this->knowledge->contentContract(),
            'context' => [
                'placements' => $this->curriculum->placementsForUnit($activeUnitId),
                'sources' => $this->quality->sourcesForClaims($lessonCitations),
                'prerequisites' => [
                    'state' => 'AUTHORITATIVE_PREREQUISITE_CONTRACT_UNAVAILABLE',
                    'items' => [],
                    'availability_may_be_inferred' => false,
                ],
                'navigation' => $this->unitNavigation($activeUnitId),
                'resume' => [
                    'storage' => 'browser_local',
                    'server_persisted' => false,
                    'semantic_scope' => 'reading_position_only_not_completion_or_mastery',
                ],
            ],
            'semantic_boundary' => [
                'progress' => 'journey_activity_context',
                'completion' => 'lesson_navigation_and_practice_activity_only',
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

        $visualization = $this->curriculum->visualization($active, [], null);

        return [
            'catalog' => $catalog,
            'active' => $active === null ? null : [
                'id' => $active['id'],
                'title_ar' => $active['title_ar'],
                'title_en' => $active['title_en'],
            ],
            'map' => $visualization['map'],
            'view' => $visualization['view'],
            'overlay' => $visualization['overlay'],
            'graph' => $visualization['graph'],
        ];
    }

    /** @return array<string, mixed> */
    public function researchQuality(?string $requestedUnitId, ?string $requestedSourceId): array
    {
        $catalog = $this->knowledge->catalog();
        $selection = $this->knowledge->resolveUnitSelection($requestedUnitId);
        $activeUnitId = $selection['resolved_id'];
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
            'selection' => $selection,
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

    /** @return array<string, string|null> */
    private function unitNavigation(?string $unitId): array
    {
        $query = $unitId === null ? '' : '?object='.rawurlencode($unitId);

        return [
            'library' => '/knowledge'.$query,
            'learn' => '/knowledge/learn'.$query,
            'visualize' => '/knowledge/visualize'.$query,
            'research_quality' => '/knowledge/research-quality'.$query,
        ];
    }
}
