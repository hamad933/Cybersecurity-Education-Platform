<?php

declare(strict_types=1);

namespace App\Modules\Simulator\ScenarioLab\Domain;

final readonly class ScenarioDefinition
{
    /**
     * @param array<string, mixed> $overview
     * @param array<string, mixed> $environmentContract
     * @param list<array<string, mixed>> $roles
     * @param list<array<string, mixed>> $phases
     * @param array<string, mixed> $orchestration
     * @param list<array<string, mixed>> $labModuleReferences
     * @param array<string, mixed> $policies
     * @param list<array<string, mixed>> $validationRules
     */
    public function __construct(
        public string $definitionId,
        public string $slug,
        public string $titleAr,
        public ?string $titleEn,
        public int $revision,
        public DefinitionStatus $status,
        public array $overview,
        public array $environmentContract,
        public array $roles,
        public array $phases,
        public array $orchestration,
        public array $labModuleReferences,
        public array $policies,
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
            'overview' => $this->overview,
            'environment_contract' => $this->environmentContract,
            'roles' => $this->roles,
            'phases' => $this->phases,
            'orchestration' => $this->orchestration,
            'lab_module_references' => $this->labModuleReferences,
            'policies' => $this->policies,
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
