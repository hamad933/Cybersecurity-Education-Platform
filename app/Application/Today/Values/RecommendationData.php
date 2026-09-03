<?php

declare(strict_types=1);

namespace App\Application\Today\Values;

class RecommendationData
{
    public function __construct(
        public readonly string $recommendationId,
        public readonly string $id,
        public readonly string $title,
        public readonly string $domain,
        public readonly string $domainLabel,
        public readonly string $href,
        public readonly string $description,
        public readonly string $rationaleText,
        public readonly ?string $timeCommitment = null,
        public readonly ?string $difficulty = null,
        public readonly ?string $actionLabel = null,
        public readonly ?string $targetCompetency = null,
        public readonly ?array $unlockedCapabilities = null,
        public readonly ?array $prerequisiteChain = null,
        public readonly ?string $selectionRuleId = null,
        public readonly ?string $selectedAt = null,
        public readonly ?string $observedAt = null,
        public readonly ?string $freshUntil = null,
        public readonly ?string $target = null,
    ) {
    }

    public function toArray(): array
    {
        $array = [
            'recommendationId' => $this->recommendationId,
            'id' => $this->id,
            'title' => $this->title,
            'domain' => $this->domain,
            'domainLabel' => $this->domainLabel,
            'href' => $this->href,
            'description' => $this->description,
            'rationaleText' => $this->rationaleText,
        ];

        if ($this->timeCommitment !== null) { $array['timeCommitment'] = $this->timeCommitment; }
        if ($this->difficulty !== null) { $array['difficulty'] = $this->difficulty; }
        if ($this->actionLabel !== null) { $array['actionLabel'] = $this->actionLabel; }
        if ($this->targetCompetency !== null) { $array['targetCompetency'] = $this->targetCompetency; }
        if ($this->unlockedCapabilities !== null) { $array['unlockedCapabilities'] = $this->unlockedCapabilities; }
        if ($this->prerequisiteChain !== null) { $array['prerequisiteChain'] = $this->prerequisiteChain; }
        if ($this->selectionRuleId !== null) { $array['selectionRuleId'] = $this->selectionRuleId; }
        if ($this->selectedAt !== null) { $array['selectedAt'] = $this->selectedAt; }
        if ($this->observedAt !== null) { $array['observedAt'] = $this->observedAt; }
        if ($this->freshUntil !== null) { $array['freshUntil'] = $this->freshUntil; }
        if ($this->target !== null) { $array['target'] = $this->target; }

        return $array;
    }
}
