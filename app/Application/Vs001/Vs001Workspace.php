<?php

namespace App\Application\Vs001;

use App\Modules\Evidence\Application\Vs001EvidenceService;
use App\Modules\Knowledge\Application\Vs001KnowledgeService;
use App\Modules\Learning\Application\Vs001LearningService;
use App\Modules\Simulator\Application\Vs001SimulationService;
use App\Modules\SourceGovernance\Application\Vs001SourceService;

final class Vs001Workspace
{
    public function __construct(
        private readonly Vs001SourceService $sources,
        private readonly Vs001KnowledgeService $knowledge,
        private readonly Vs001LearningService $learning,
        private readonly Vs001SimulationService $simulation,
        private readonly Vs001EvidenceService $evidence,
    ) {}

    /** @return list<array<string,mixed>> */
    public function sources(): array
    {
        return $this->sources->reviewedSources();
    }

    /** @return list<array<string,mixed>> */
    public function revisions(): array
    {
        return $this->knowledge->revisions();
    }

    /** @return array<string,mixed> */
    public function lesson(): array
    {
        return $this->knowledge->publishedLesson();
    }

    /** @return array<string,mixed> */
    public function practice(string $actorId): array
    {
        return $this->learning->practiceWorkspace($actorId);
    }

    /** @return array<string,mixed> */
    public function lab(): array
    {
        return $this->simulation->workspace();
    }

    /** @return array{evidence:list<array<string,mixed>>,mastery:?array<string,mixed>,triggers:list<array<string,mixed>>} */
    public function evidenceMastery(string $actorId): array
    {
        return ['evidence' => $this->evidence->workspace($actorId)] + $this->learning->masteryWorkspace($actorId);
    }
}
