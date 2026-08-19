<?php

declare(strict_types=1);

namespace App\Modules\Simulator\ScenarioLab\Domain;

final readonly class LabDefinition
{
    /**
     * @param list<string> $knowledgeLinks
     * @param array<string, mixed> $environment
     * @param array<string, mixed> $initialState
     * @param list<array<string, mixed>> $tasks
     * @param array<string, mixed> $policies
     * @param list<string> $toolRequirements
     * @param list<string> $availableActions
     * @param list<string> $usageModes
     * @param list<array<string, mixed>> $validationRules
     */
    public function __construct(
        public string $definitionId,
        public string $slug,
        public string $titleAr,
        public ?string $titleEn,
        public int $revision,
        public DefinitionStatus $status,
        public string $purpose,
        public array $knowledgeLinks,
        public array $environment,
        public array $initialState,
        public array $tasks,
        public array $policies,
        public array $toolRequirements,
        public array $availableActions,
        public array $usageModes,
        public array $validationRules,
        public ?string $digest = null,
    ) {}

    /** @return array<string, mixed> */
    public function contentPayload(): array
    {
        return [
            'definition_id' => $this->definitionId,
            'slug' => $this->slug,
            'title_ar' => $this->titleAr,
            'title_en' => $this->titleEn,
            'revision' => $this->revision,
            'purpose' => $this->purpose,
            'knowledge_links' => $this->knowledgeLinks,
            'environment' => $this->environment,
            'initial_state' => $this->initialState,
            'tasks' => $this->tasks,
            'policies' => $this->policies,
            'tool_requirements' => $this->toolRequirements,
            'available_actions' => $this->availableActions,
            'usage_modes' => $this->usageModes,
            'validation_rules' => $this->validationRules,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...$this->contentPayload(),
            'status' => $this->status->value,
            'digest' => $this->digest,
        ];
    }
}
