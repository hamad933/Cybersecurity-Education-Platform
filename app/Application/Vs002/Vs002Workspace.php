<?php

namespace App\Application\Vs002;

use App\Modules\Evidence\Application\Vs002EvidenceService;
use App\Modules\Knowledge\Application\Vs002KnowledgeService;
use App\Modules\Learning\Application\Vs002LearningService;
use App\Modules\Simulator\Application\Vs002SimulationService;
use App\Modules\SourceGovernance\Application\Vs002SourceService;

final class Vs002Workspace
{
    public function __construct(
        private readonly Vs002SourceService $sources,
        private readonly Vs002KnowledgeService $knowledge,
        private readonly Vs002LearningService $learning,
        private readonly Vs002SimulationService $simulation,
        private readonly Vs002EvidenceService $evidence,
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

    /** @return array<string,mixed> */
    public function evidence(string $actorId): array
    {
        return $this->evidence->workspace($actorId) + $this->learning->workspace($actorId) + ['policies' => $this->simulation->policyRevisions()];
    }
}
