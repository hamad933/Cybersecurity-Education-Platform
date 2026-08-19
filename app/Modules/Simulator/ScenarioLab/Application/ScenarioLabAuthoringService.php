<?php

declare(strict_types=1);

namespace App\Modules\Simulator\ScenarioLab\Application;

use App\Modules\Simulator\ScenarioLab\Domain\DefinitionStatus;
use App\Modules\Simulator\ScenarioLab\Domain\LabDefinition;
use App\Modules\Simulator\ScenarioLab\Domain\ScenarioDefinition;
use App\Modules\Simulator\ScenarioLab\Domain\ValidationReport;
use DomainException;
use InvalidArgumentException;

final class ScenarioLabAuthoringService
{
    /** @var list<string> */
    private const LAB_ENVIRONMENT_MODES = ['ISOLATED', 'ENTERPRISE_BINDING', 'PORTABLE_CONTRACT'];

    /** @var list<string> */
    private const LAB_USAGE_MODES = ['STANDALONE', 'SCENARIO_MODULE'];

    /** @var list<string> */
    private const GUIDANCE_MODES = ['GUIDED', 'UNGUIDED', 'SELECTABLE'];

    /** @var list<string> */
    private const PARTICIPATION_MODES = ['SOLO', 'TEAM', 'ROLE_BASED', 'SELECTABLE'];

    /** @var list<string> */
    private const FIXED_LINEAGE_KEYS = ['enterprise_id', 'digital_twin_revision_id', 'baseline_id'];

    /** @var list<string> */
    private const EXTERNAL_RUNTIME_KEYS = [
        'docker',
        'docker_image',
        'container',
        'container_image',
        'vm',
        'vm_image',
        'vmware',
        'hypervisor',
        'kubernetes',
        'remote_range',
        'cloud_provider',
        'ssh',
        'winrm',
        'real_siem_connector',
        'active_directory_connector',
    ];

    /**
     * @param array<string, mixed> $overview
     * @param array<string, mixed> $environmentContract
     * @param list<array<string, mixed>> $roles
     * @param list<array<string, mixed>> $phases
     * @param array<string, mixed> $orchestration
     * @param array<string, mixed> $policies
     * @param list<array<string, mixed>> $validationRules
     */
    public function draftScenario(
        string $definitionId,
        string $slug,
        string $titleAr,
        array $environmentContract,
        array $phases,
        array $orchestration,
        array $roles = [],
        array $policies = [],
        array $validationRules = [],
        array $overview = [],
        ?string $titleEn = null,
        int $revision = 1,
    ): ScenarioDefinition {
        $this->assertRevision($revision);

        return new ScenarioDefinition(
            definitionId: $definitionId,
            slug: $slug,
            titleAr: $titleAr,
            titleEn: $titleEn,
            revision: $revision,
            status: DefinitionStatus::DRAFT,
            overview: $overview,
            environmentContract: $environmentContract,
            roles: $roles,
            phases: $phases,
            orchestration: $orchestration,
            labModuleReferences: [],
            policies: $policies,
            validationRules: $validationRules,
        );
    }

    public function newScenarioDraft(ScenarioDefinition $published): ScenarioDefinition
    {
        $this->requirePublishedScenario($published);

        return new ScenarioDefinition(
            definitionId: $published->definitionId,
            slug: $published->slug,
            titleAr: $published->titleAr,
            titleEn: $published->titleEn,
            revision: $published->revision + 1,
            status: DefinitionStatus::DRAFT,
            overview: $published->overview,
            environmentContract: $published->environmentContract,
            roles: $published->roles,
            phases: $published->phases,
            orchestration: $published->orchestration,
            labModuleReferences: $published->labModuleReferences,
            policies: $published->policies,
            validationRules: $published->validationRules,
        );
    }

    public function publishScenario(ScenarioDefinition $draft): ScenarioDefinition
    {
        $this->requireDraftScenario($draft);
        $report = $this->validateScenario($draft);
        if ($report->isValid() === false) {
            throw new DomainException('Scenario Definition is not publishable: '.implode(' ', $report->errors));
        }

        return new ScenarioDefinition(
            definitionId: $draft->definitionId,
            slug: $draft->slug,
            titleAr: $draft->titleAr,
            titleEn: $draft->titleEn,
            revision: $draft->revision,
            status: DefinitionStatus::PUBLISHED,
            overview: $draft->overview,
            environmentContract: $draft->environmentContract,
            roles: $draft->roles,
            phases: $draft->phases,
            orchestration: $draft->orchestration,
            labModuleReferences: $draft->labModuleReferences,
            policies: $draft->policies,
            validationRules: $draft->validationRules,
            digest: $this->digest($draft->contentPayload()),
        );
    }

    public function attachLabModuleReference(
        ScenarioDefinition $scenarioDraft,
        LabDefinition $publishedLab,
        string $moduleKey,
        ?string $phaseKey = null,
        array $policy = [],
    ): ScenarioDefinition {
        $this->requireDraftScenario($scenarioDraft);
        $this->requirePublishedLab($publishedLab);
        $moduleKey = trim($moduleKey);
        if ($moduleKey === '') {
            throw new InvalidArgumentException('Lab Module Reference requires a non-empty module key.');
        }
        if ($phaseKey !== null && $this->phaseExists($scenarioDraft->phases, $phaseKey) === false) {
            throw new DomainException('Lab Module Reference targets an unknown Scenario phase.');
        }
        foreach ($scenarioDraft->labModuleReferences as $reference) {
            if (($reference['module_key'] ?? null) === $moduleKey) {
                throw new DomainException('Lab Module Reference module keys must be unique inside one Scenario revision.');
            }
        }

        $references = $scenarioDraft->labModuleReferences;
        $references[] = [
            'module_key' => $moduleKey,
            'lab_definition_id' => $publishedLab->definitionId,
            'lab_revision' => $publishedLab->revision,
            'lab_digest' => $publishedLab->digest,
            'phase_key' => $phaseKey,
            'policy' => $policy,
        ];

        return new ScenarioDefinition(
            definitionId: $scenarioDraft->definitionId,
            slug: $scenarioDraft->slug,
            titleAr: $scenarioDraft->titleAr,
            titleEn: $scenarioDraft->titleEn,
            revision: $scenarioDraft->revision,
            status: DefinitionStatus::DRAFT,
            overview: $scenarioDraft->overview,
            environmentContract: $scenarioDraft->environmentContract,
            roles: $scenarioDraft->roles,
            phases: $scenarioDraft->phases,
            orchestration: $scenarioDraft->orchestration,
            labModuleReferences: $references,
            policies: $scenarioDraft->policies,
            validationRules: $scenarioDraft->validationRules,
        );
    }

    public function validateScenario(ScenarioDefinition $definition): ValidationReport
    {
        $errors = [];
        $warnings = [];

        $this->validateIdentity(
            $definition->definitionId,
            $definition->slug,
            $definition->titleAr,
            $definition->revision,
            $errors,
        );

        if ($this->containsAnyKeyRecursive($definition->environmentContract, self::FIXED_LINEAGE_KEYS)) {
            $errors[] = 'Scenario Environment Contract must remain portable and cannot pin Enterprise, Digital Twin Revision, or Baseline identifiers.';
        }
        if ($this->containsAnyKeyRecursive($definition->environmentContract, self::EXTERNAL_RUNTIME_KEYS)) {
            $errors[] = 'Scenario Environment Contract cannot require external execution infrastructure in CEP V1.';
        }
        if ($this->nonEmptyStringList($definition->environmentContract['required_capabilities'] ?? null) === false) {
            $errors[] = 'Scenario Environment Contract requires at least one required_capability.';
        }

        $phaseKeys = $this->validatePhases($definition->phases, $errors);
        $this->validateRoles($definition->roles, $errors);
        $this->validateScenarioOrchestration($definition->orchestration, $phaseKeys, $errors);
        $this->validateScenarioLabReferences($definition->labModuleReferences, $phaseKeys, $errors);
        $this->validateRules($definition->validationRules, 'Scenario', $errors);

        if (array_key_exists('run_type', $definition->policies)) {
            $errors[] = 'Scenario policies cannot define a Run type; Run creation is outside Scenario Definition authoring.';
        }
        if ($definition->labModuleReferences === []) {
            $warnings[] = 'Scenario has no Lab Module References; this is valid when the Scenario is entirely orchestration-driven.';
        }

        return new ValidationReport($errors, $warnings);
    }

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
    public function draftLab(
        string $definitionId,
        string $slug,
        string $titleAr,
        string $purpose,
        array $environment,
        array $tasks,
        array $policies,
        array $usageModes,
        array $validationRules,
        array $knowledgeLinks = [],
        array $initialState = [],
        array $toolRequirements = [],
        array $availableActions = [],
        ?string $titleEn = null,
        int $revision = 1,
    ): LabDefinition {
        $this->assertRevision($revision);

        return new LabDefinition(
            definitionId: $definitionId,
            slug: $slug,
            titleAr: $titleAr,
            titleEn: $titleEn,
            revision: $revision,
            status: DefinitionStatus::DRAFT,
            purpose: $purpose,
            knowledgeLinks: $knowledgeLinks,
            environment: $environment,
            initialState: $initialState,
            tasks: $tasks,
            policies: $policies,
            toolRequirements: $toolRequirements,
            availableActions: $availableActions,
            usageModes: $usageModes,
            validationRules: $validationRules,
        );
    }

    public function newLabDraft(LabDefinition $published): LabDefinition
    {
        $this->requirePublishedLab($published);

        return new LabDefinition(
            definitionId: $published->definitionId,
            slug: $published->slug,
            titleAr: $published->titleAr,
            titleEn: $published->titleEn,
            revision: $published->revision + 1,
            status: DefinitionStatus::DRAFT,
            purpose: $published->purpose,
            knowledgeLinks: $published->knowledgeLinks,
            environment: $published->environment,
            initialState: $published->initialState,
            tasks: $published->tasks,
            policies: $published->policies,
            toolRequirements: $published->toolRequirements,
            availableActions: $published->availableActions,
            usageModes: $published->usageModes,
            validationRules: $published->validationRules,
        );
    }

    public function publishLab(LabDefinition $draft): LabDefinition
    {
        $this->requireDraftLab($draft);
        $report = $this->validateLab($draft);
        if ($report->isValid() === false) {
            throw new DomainException('Lab Definition is not publishable: '.implode(' ', $report->errors));
        }

        return new LabDefinition(
            definitionId: $draft->definitionId,
            slug: $draft->slug,
            titleAr: $draft->titleAr,
            titleEn: $draft->titleEn,
            revision: $draft->revision,
            status: DefinitionStatus::PUBLISHED,
            purpose: $draft->purpose,
            knowledgeLinks: $draft->knowledgeLinks,
            environment: $draft->environment,
            initialState: $draft->initialState,
            tasks: $draft->tasks,
            policies: $draft->policies,
            toolRequirements: $draft->toolRequirements,
            availableActions: $draft->availableActions,
            usageModes: $draft->usageModes,
            validationRules: $draft->validationRules,
            digest: $this->digest($draft->contentPayload()),
        );
    }

    public function validateLab(LabDefinition $definition): ValidationReport
    {
        $errors = [];
        $warnings = [];

        $this->validateIdentity(
            $definition->definitionId,
            $definition->slug,
            $definition->titleAr,
            $definition->revision,
            $errors,
        );
        if (trim($definition->purpose) === '') {
            $errors[] = 'Lab Definition requires a purpose.';
        }

        $mode = $definition->environment['mode'] ?? null;
        if (is_string($mode) === false || in_array($mode, self::LAB_ENVIRONMENT_MODES, true) === false) {
            $errors[] = 'Lab environment mode must be ISOLATED, ENTERPRISE_BINDING, or PORTABLE_CONTRACT.';
        } elseif ($mode === 'ISOLATED') {
            if ($this->nonEmptyStringList($definition->environment['simulated_capabilities'] ?? null) === false) {
                $errors[] = 'Isolated Lab environment requires simulated_capabilities.';
            }
            if ($this->containsAnyKeyRecursive($definition->environment, self::FIXED_LINEAGE_KEYS)) {
                $errors[] = 'Isolated Lab environment cannot contain Enterprise/Twin/Baseline bindings.';
            }
        } elseif ($mode === 'ENTERPRISE_BINDING') {
            foreach (self::FIXED_LINEAGE_KEYS as $requiredKey) {
                if ($this->nonEmptyString($definition->environment[$requiredKey] ?? null) === false) {
                    $errors[] = "Enterprise-bound Lab environment requires {$requiredKey}.";
                }
            }
        } elseif ($mode === 'PORTABLE_CONTRACT') {
            if ($this->nonEmptyStringList($definition->environment['required_capabilities'] ?? null) === false) {
                $errors[] = 'Portable Lab environment requires required_capabilities.';
            }
            if ($this->containsAnyKeyRecursive($definition->environment, self::FIXED_LINEAGE_KEYS)) {
                $errors[] = 'Portable Lab environment cannot pin Enterprise, Digital Twin Revision, or Baseline identifiers.';
            }
        }
        if ($this->containsAnyKeyRecursive($definition->environment, self::EXTERNAL_RUNTIME_KEYS)) {
            $errors[] = 'Lab Definition cannot require Docker, VM, remote-range, cloud, or production connector execution in CEP V1.';
        }

        $this->validateLabUsageModes($definition->usageModes, $errors);
        $this->validateLabPolicies($definition->policies, $errors);
        $this->validateLabTasks($definition->tasks, $errors);
        $this->validateRules($definition->validationRules, 'Lab', $errors);

        if ($definition->toolRequirements === []) {
            $warnings[] = 'Lab has no explicit simulated tool requirements.';
        }
        if ($definition->availableActions === []) {
            $warnings[] = 'Lab has no explicit available simulated actions.';
        }

        return new ValidationReport($errors, $warnings);
    }

    /** @param list<array<string, mixed>> $phases */
    private function phaseExists(array $phases, string $phaseKey): bool
    {
        foreach ($phases as $phase) {
            if (($phase['key'] ?? null) === $phaseKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $phases
     * @param list<string> $errors
     * @return list<string>
     */
    private function validatePhases(array $phases, array &$errors): array
    {
        if ($phases === []) {
            $errors[] = 'Scenario Definition requires at least one phase.';

            return [];
        }

        $keys = [];
        $ordinals = [];
        foreach ($phases as $index => $phase) {
            $key = $phase['key'] ?? null;
            $title = $phase['title'] ?? null;
            $ordinal = $phase['ordinal'] ?? null;
            if ($this->nonEmptyString($key) === false || $this->nonEmptyString($title) === false) {
                $errors[] = "Scenario phase {$index} requires non-empty key and title.";
                continue;
            }
            if (is_int($ordinal) === false || $ordinal < 1) {
                $errors[] = "Scenario phase {$key} requires a positive integer ordinal.";
            }
            if (in_array($key, $keys, true)) {
                $errors[] = "Scenario phase key {$key} is duplicated.";
            }
            if (is_int($ordinal) && in_array($ordinal, $ordinals, true)) {
                $errors[] = "Scenario phase ordinal {$ordinal} is duplicated.";
            }
            $keys[] = $key;
            if (is_int($ordinal)) {
                $ordinals[] = $ordinal;
            }
        }

        return $keys;
    }

    /**
     * @param list<array<string, mixed>> $roles
     * @param list<string> $errors
     */
    private function validateRoles(array $roles, array &$errors): void
    {
        $keys = [];
        foreach ($roles as $index => $role) {
            $key = $role['key'] ?? null;
            $title = $role['title'] ?? null;
            if ($this->nonEmptyString($key) === false || $this->nonEmptyString($title) === false) {
                $errors[] = "Scenario role {$index} requires non-empty key and title.";
                continue;
            }
            if (in_array($key, $keys, true)) {
                $errors[] = "Scenario role key {$key} is duplicated.";
            }
            $keys[] = $key;
        }
    }

    /**
     * @param array<string, mixed> $orchestration
     * @param list<string> $phaseKeys
     * @param list<string> $errors
     */
    private function validateScenarioOrchestration(array $orchestration, array $phaseKeys, array &$errors): void
    {
        $entryPhase = $orchestration['entry_phase'] ?? null;
        if ($this->nonEmptyString($entryPhase) === false || in_array($entryPhase, $phaseKeys, true) === false) {
            $errors[] = 'Scenario orchestration requires entry_phase referencing a defined phase.';
        }

        $transitions = $orchestration['transitions'] ?? [];
        if (is_array($transitions) === false || array_is_list($transitions) === false) {
            $errors[] = 'Scenario orchestration transitions must be a list.';

            return;
        }
        foreach ($transitions as $index => $transition) {
            if (is_array($transition) === false) {
                $errors[] = "Scenario transition {$index} must be an object-like array.";
                continue;
            }
            $from = $transition['from'] ?? null;
            $to = $transition['to'] ?? null;
            if ($this->nonEmptyString($from) === false || in_array($from, $phaseKeys, true) === false) {
                $errors[] = "Scenario transition {$index} references an unknown from phase.";
            }
            if ($this->nonEmptyString($to) === false || in_array($to, $phaseKeys, true) === false) {
                $errors[] = "Scenario transition {$index} references an unknown to phase.";
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $references
     * @param list<string> $phaseKeys
     * @param list<string> $errors
     */
    private function validateScenarioLabReferences(array $references, array $phaseKeys, array &$errors): void
    {
        $moduleKeys = [];
        foreach ($references as $index => $reference) {
            $moduleKey = $reference['module_key'] ?? null;
            $labId = $reference['lab_definition_id'] ?? null;
            $labRevision = $reference['lab_revision'] ?? null;
            $labDigest = $reference['lab_digest'] ?? null;
            $phaseKey = $reference['phase_key'] ?? null;
            if ($this->nonEmptyString($moduleKey) === false) {
                $errors[] = "Lab Module Reference {$index} requires module_key.";
            } elseif (in_array($moduleKey, $moduleKeys, true)) {
                $errors[] = "Lab Module Reference module key {$moduleKey} is duplicated.";
            } else {
                $moduleKeys[] = $moduleKey;
            }
            if ($this->nonEmptyString($labId) === false || is_int($labRevision) === false || $labRevision < 1) {
                $errors[] = "Lab Module Reference {$index} must pin a Lab Definition identity and revision.";
            }
            if ($this->nonEmptyString($labDigest) === false || strlen((string) $labDigest) !== 64) {
                $errors[] = "Lab Module Reference {$index} must pin the published Lab digest.";
            }
            if ($phaseKey !== null && (is_string($phaseKey) === false || in_array($phaseKey, $phaseKeys, true) === false)) {
                $errors[] = "Lab Module Reference {$index} targets an unknown phase.";
            }
            foreach (['lab_module_instance_id', 'standalone_lab_run_id', 'run_id'] as $runtimeKey) {
                if (array_key_exists($runtimeKey, $reference)) {
                    $errors[] = "Lab Module Reference {$index} cannot contain runtime field {$runtimeKey}.";
                }
            }
        }
    }

    /**
     * @param list<string> $usageModes
     * @param list<string> $errors
     */
    private function validateLabUsageModes(array $usageModes, array &$errors): void
    {
        if ($usageModes === []) {
            $errors[] = 'Lab Definition requires at least one usage mode.';

            return;
        }
        foreach (array_unique($usageModes) as $mode) {
            if (in_array($mode, self::LAB_USAGE_MODES, true) === false) {
                $errors[] = "Unsupported Lab usage mode {$mode}.";
            }
        }
    }

    /**
     * @param array<string, mixed> $policies
     * @param list<string> $errors
     */
    private function validateLabPolicies(array $policies, array &$errors): void
    {
        $guidanceMode = $policies['guidance_mode'] ?? null;
        if (is_string($guidanceMode) === false || in_array($guidanceMode, self::GUIDANCE_MODES, true) === false) {
            $errors[] = 'Lab policies require guidance_mode GUIDED, UNGUIDED, or SELECTABLE.';
        }
        $participationMode = $policies['participation_mode'] ?? 'SELECTABLE';
        if (is_string($participationMode) === false || in_array($participationMode, self::PARTICIPATION_MODES, true) === false) {
            $errors[] = 'Lab participation_mode must be SOLO, TEAM, ROLE_BASED, or SELECTABLE.';
        }
        if (array_key_exists('run_type', $policies)) {
            $errors[] = 'Guided/Unguided and participation choices are policies, not Run types.';
        }
    }

    /**
     * @param list<array<string, mixed>> $tasks
     * @param list<string> $errors
     */
    private function validateLabTasks(array $tasks, array &$errors): void
    {
        if ($tasks === []) {
            $errors[] = 'Lab Definition requires a non-empty task graph.';

            return;
        }

        $keys = [];
        $dependencies = [];
        foreach ($tasks as $index => $task) {
            $key = $task['key'] ?? null;
            $title = $task['title'] ?? null;
            if ($this->nonEmptyString($key) === false || $this->nonEmptyString($title) === false) {
                $errors[] = "Lab task {$index} requires non-empty key and title.";
                continue;
            }
            if (in_array($key, $keys, true)) {
                $errors[] = "Lab task key {$key} is duplicated.";
                continue;
            }
            $keys[] = $key;
            $taskDependencies = $task['depends_on'] ?? [];
            if (is_array($taskDependencies) === false || array_is_list($taskDependencies) === false) {
                $errors[] = "Lab task {$key} depends_on must be a list.";
                $taskDependencies = [];
            }
            $dependencies[$key] = $taskDependencies;
        }

        foreach ($dependencies as $taskKey => $taskDependencies) {
            foreach ($taskDependencies as $dependency) {
                if (is_string($dependency) === false || in_array($dependency, $keys, true) === false) {
                    $errors[] = "Lab task {$taskKey} depends on an unknown task.";
                } elseif ($dependency === $taskKey) {
                    $errors[] = "Lab task {$taskKey} cannot depend on itself.";
                }
            }
        }
        if ($this->hasDependencyCycle($dependencies)) {
            $errors[] = 'Lab task graph must be acyclic.';
        }
    }

    /**
     * @param list<array<string, mixed>> $rules
     * @param list<string> $errors
     */
    private function validateRules(array $rules, string $subject, array &$errors): void
    {
        if ($rules === []) {
            $errors[] = "{$subject} Definition requires at least one validation/completion rule.";

            return;
        }
        $keys = [];
        foreach ($rules as $index => $rule) {
            $key = $rule['key'] ?? null;
            $criterion = $rule['criterion'] ?? null;
            if ($this->nonEmptyString($key) === false || $this->nonEmptyString($criterion) === false) {
                $errors[] = "{$subject} validation rule {$index} requires non-empty key and criterion.";
                continue;
            }
            if (in_array($key, $keys, true)) {
                $errors[] = "{$subject} validation rule key {$key} is duplicated.";
            }
            $keys[] = $key;
        }
    }

    /**
     * @param array<string, list<mixed>> $dependencies
     */
    private function hasDependencyCycle(array $dependencies): bool
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $key) use (&$visit, &$visiting, &$visited, $dependencies): bool {
            if (isset($visited[$key])) {
                return false;
            }
            if (isset($visiting[$key])) {
                return true;
            }
            $visiting[$key] = true;
            foreach ($dependencies[$key] ?? [] as $dependency) {
                if (is_string($dependency) && array_key_exists($dependency, $dependencies) && $visit($dependency)) {
                    return true;
                }
            }
            unset($visiting[$key]);
            $visited[$key] = true;

            return false;
        };

        foreach (array_keys($dependencies) as $key) {
            if ($visit($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $errors
     */
    private function validateIdentity(
        string $definitionId,
        string $slug,
        string $titleAr,
        int $revision,
        array &$errors,
    ): void {
        if (trim($definitionId) === '') {
            $errors[] = 'Definition identity cannot be empty.';
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            $errors[] = 'Definition slug must use lowercase kebab-case.';
        }
        if (trim($titleAr) === '') {
            $errors[] = 'Arabic title cannot be empty.';
        }
        if ($revision < 1) {
            $errors[] = 'Definition revision must be positive.';
        }
    }

    private function requireDraftScenario(ScenarioDefinition $definition): void
    {
        if ($definition->status !== DefinitionStatus::DRAFT) {
            throw new DomainException('Published Scenario revisions are immutable; create a new Draft revision first.');
        }
    }

    private function requirePublishedScenario(ScenarioDefinition $definition): void
    {
        if ($definition->status !== DefinitionStatus::PUBLISHED || $definition->digest === null) {
            throw new DomainException('A new Scenario revision can be based only on a published revision.');
        }
    }

    private function requireDraftLab(LabDefinition $definition): void
    {
        if ($definition->status !== DefinitionStatus::DRAFT) {
            throw new DomainException('Published Lab revisions are immutable; create a new Draft revision first.');
        }
    }

    private function requirePublishedLab(LabDefinition $definition): void
    {
        if ($definition->status !== DefinitionStatus::PUBLISHED || $definition->digest === null) {
            throw new DomainException('Lab Module References and new revisions require a published Lab revision.');
        }
    }

    private function assertRevision(int $revision): void
    {
        if ($revision < 1) {
            throw new InvalidArgumentException('Definition revision must be positive.');
        }
    }

    /** @phpstan-assert-if-true non-empty-string $value */
    private function nonEmptyString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /** @phpstan-assert-if-true non-empty-list<non-empty-string> $value */
    private function nonEmptyStringList(mixed $value): bool
    {
        if (is_array($value) === false || array_is_list($value) === false || $value === []) {
            return false;
        }
        foreach ($value as $item) {
            if ($this->nonEmptyString($item) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $value
     * @param list<string> $keys
     */
    private function containsAnyKeyRecursive(array $value, array $keys): bool
    {
        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), $keys, true)) {
                return true;
            }
            if (is_array($item) && $this->containsAnyKeyRecursive($item, $keys)) {
                return true;
            }
        }

        return false;
    }

    private function digest(mixed $value): string
    {
        return hash(
            'sha256',
            json_encode(
                $this->canonicalize($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value) === false) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value, SORT_STRING);

        return array_map(
            fn (mixed $item): mixed => $this->canonicalize($item),
            $value,
        );
    }
}
