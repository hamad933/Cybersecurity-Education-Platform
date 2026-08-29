<?php

namespace App\Modules\Simulator\Application;

use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;

final class SimulationDefinitionService
{
    public const LAB_LOCAL = 'LAB_LOCAL';

    public const ENTERPRISE_BASELINE = 'ENTERPRISE_BASELINE';

    private const LAB_DEFINITIONS = 'simulation_lab_definitions';

    private const LAB_TASKS = 'simulation_lab_task_nodes';

    private const LAB_DEPENDENCIES = 'simulation_lab_task_dependencies';

    private const LAB_TEMPLATE_REFERENCES = 'simulation_lab_device_template_references';

    private const LAB_ENVIRONMENT_SCHEMA = 'cep.simulation.lab-environment-contract.v1';

    private const INTERNAL_EXECUTION_MODEL = 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION';

    public function __construct(private readonly SimulationEnterpriseStateReader $enterpriseState) {}

    /**
     * @param  array<string, mixed>  $environmentContract
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function createLabDraft(
        string $slug,
        string $titleAr,
        string $environmentBindingMode,
        array $environmentContract,
        array $configuration,
        array $validation,
        ?string $enterpriseId,
        ?string $baselineId,
        string $actorId,
    ): array {
        $this->assertActor($actorId);
        $this->assertEnvironmentContract($environmentContract);
        $this->assertEnvironmentBinding($environmentBindingMode, $enterpriseId, $baselineId);
        if ($environmentBindingMode === self::ENTERPRISE_BASELINE) {
            $this->assertEnterpriseBaselineCapabilities($environmentContract, $enterpriseId, $baselineId);
        }

        return DB::transaction(function () use ($slug, $titleAr, $environmentBindingMode, $environmentContract, $configuration, $validation, $enterpriseId, $baselineId, $actorId): array {
            $identity = DB::table('simulation_labs')->where('slug', $slug)->lockForUpdate()->first();
            $now = now();
            if ($identity === null) {
                $labId = (string) Str::uuid7();
                DB::table('simulation_labs')->insert([
                    'id' => $labId,
                    'slug' => $slug,
                    'title_ar' => $titleAr,
                    'title_en' => null,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $labId = (string) $identity->id;
                if (DB::table(self::LAB_DEFINITIONS)->where('lab_id', $labId)->whereIn('status', ['DRAFT', 'VALIDATED'])->exists()) {
                    throw new LogicException('Lab already has an open definition revision.');
                }
            }

            $previous = DB::table(self::LAB_DEFINITIONS)
                ->where('lab_id', $labId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('revision')
                ->first();
            $revision = (int) DB::table(self::LAB_DEFINITIONS)->where('lab_id', $labId)->max('revision') + 1;
            $definitionId = (string) Str::uuid7();
            $definition = [
                'lab_id' => $labId,
                'revision' => $revision,
                'environment_binding_mode' => $environmentBindingMode,
                'environment_contract' => $environmentContract,
                'configuration' => $configuration,
                'validation' => $validation,
                'tasks' => [],
                'dependencies' => [],
                'device_template_references' => [],
            ];

            DB::table(self::LAB_DEFINITIONS)->insert([
                'id' => $definitionId,
                'lab_id' => $labId,
                'based_on_revision_id' => $previous?->id,
                'enterprise_id' => $enterpriseId,
                'baseline_id' => $baselineId,
                'slug' => $slug,
                'title_ar' => $titleAr,
                'title_en' => null,
                'revision' => $revision,
                'status' => 'DRAFT',
                'environment_binding_mode' => $environmentBindingMode,
                'environment_contract' => $this->json($environmentContract),
                'configuration' => $this->json($configuration),
                'validation' => $this->json($validation),
                'validation_report' => null,
                'digest' => $this->digest($definition),
                'validated_at' => null,
                'published_at' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row(self::LAB_DEFINITIONS, $definitionId);
        });
    }

    /**
     * Adapter for the pre-correction publication API used by the bounded Wave 1 seed path.
     * It persists the same definition through the corrected lifecycle and graph tables.
     *
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function publishEnterpriseBoundLab(
        string $enterpriseId,
        string $baselineId,
        string $slug,
        string $titleAr,
        array $configuration,
        array $validation,
        string $actorId,
    ): array {
        $requiredCapabilities = $this->stringList($configuration['required_capabilities'] ?? []);
        $environmentContract = [
            'schema' => self::LAB_ENVIRONMENT_SCHEMA,
            'execution_model' => self::INTERNAL_EXECUTION_MODEL,
            'required_capabilities' => $requiredCapabilities,
        ];
        $stepsValue = $configuration['steps'] ?? [];
        $steps = is_array($stepsValue) ? array_values($stepsValue) : [];
        unset($configuration['steps']);

        return DB::transaction(function () use ($enterpriseId, $baselineId, $slug, $titleAr, $configuration, $validation, $actorId, $environmentContract, $steps): array {
            $draft = $this->createLabDraft(
                $slug,
                $titleAr,
                self::ENTERPRISE_BASELINE,
                $environmentContract,
                $configuration,
                $validation,
                $enterpriseId,
                $baselineId,
                $actorId,
            );
            $previousTaskId = null;
            foreach ($steps as $index => $step) {
                $stepMap = is_array($step) ? $step : [];
                $label = is_string($step) ? $step : $this->stringOr($stepMap['title'] ?? $stepMap['label'] ?? null, 'Task '.($index + 1));
                $task = $this->addLabTask((string) $draft['id'], [
                    'task_key' => $this->stringOr($stepMap['task_key'] ?? $stepMap['id'] ?? null, Str::slug($label) ?: 'task-'.($index + 1)),
                    'title_ar' => $label,
                    'objective' => $this->stringOr($stepMap['objective'] ?? null, $label),
                    'permitted_tools' => $this->arrayValue($stepMap['permitted_tools'] ?? []),
                    'required_capabilities' => $this->arrayValue($stepMap['required_capabilities'] ?? []),
                    'required_role' => $stepMap['required_role'] ?? null,
                    'expected_signals' => $this->arrayValue($stepMap['expected_signals'] ?? []),
                    'validation_rule' => $this->arrayValue($stepMap['validation_rule'] ?? $validation),
                    'completion_weight' => 1,
                    'is_optional' => false,
                ], $actorId);
                if ($previousTaskId !== null) {
                    $this->addLabTaskDependency((string) $draft['id'], [
                        'predecessor_task_id' => $previousTaskId,
                        'successor_task_id' => (string) $task['id'],
                        'dependency_type' => 'REQUIRED',
                        'condition' => null,
                    ], $actorId);
                }
                $previousTaskId = (string) $task['id'];
            }
            $validated = $this->validateLabDefinition((string) $draft['id'], $actorId);
            if ((string) $validated['status'] !== 'VALIDATED') {
                throw new LogicException('Legacy Lab publication did not satisfy the corrected definition contract.');
            }

            return $this->publishLabDefinition((string) $draft['id'], $actorId);
        });
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function addLabTask(string $labDefinitionId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $this->requireDefinition($labDefinitionId, ['DRAFT']);
        $id = (string) Str::uuid7();
        $now = now();
        $weight = $attributes['completion_weight'] ?? 1;
        if (! is_int($weight) && ! is_float($weight) && ! is_numeric($weight)) {
            throw new DomainException('Lab task completion weight must be numeric.');
        }

        DB::table(self::LAB_TASKS)->insert([
            'id' => $id,
            'lab_definition_id' => $labDefinitionId,
            'task_key' => $this->requiredString($attributes, 'task_key'),
            'title_ar' => $this->requiredString($attributes, 'title_ar'),
            'objective' => $this->requiredString($attributes, 'objective'),
            'permitted_tools' => $this->json($this->arrayValue($attributes['permitted_tools'] ?? [])),
            'required_capabilities' => $this->json($this->arrayValue($attributes['required_capabilities'] ?? [])),
            'required_role' => $this->nullableString($attributes['required_role'] ?? null),
            'expected_signals' => $this->json($this->arrayValue($attributes['expected_signals'] ?? [])),
            'validation_rule' => $this->json($this->arrayValue($attributes['validation_rule'] ?? [])),
            'completion_weight' => (float) $weight,
            'is_optional' => (bool) ($attributes['is_optional'] ?? false),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->refreshDraftDigest($labDefinitionId);

        return $this->row(self::LAB_TASKS, $id);
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function addLabTaskDependency(string $labDefinitionId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $this->requireDefinition($labDefinitionId, ['DRAFT']);
        $predecessorId = $this->requiredString($attributes, 'predecessor_task_id');
        $successorId = $this->requiredString($attributes, 'successor_task_id');
        $this->requireTask($labDefinitionId, $predecessorId);
        $this->requireTask($labDefinitionId, $successorId);

        $id = (string) Str::uuid7();
        $now = now();
        DB::table(self::LAB_DEPENDENCIES)->insert([
            'id' => $id,
            'lab_definition_id' => $labDefinitionId,
            'predecessor_task_id' => $predecessorId,
            'successor_task_id' => $successorId,
            'dependency_type' => $this->nullableString($attributes['dependency_type'] ?? null) ?? 'REQUIRED',
            'condition' => isset($attributes['condition']) ? $this->json($this->arrayValue($attributes['condition'])) : null,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->refreshDraftDigest($labDefinitionId);

        return $this->row(self::LAB_DEPENDENCIES, $id);
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function addLabDeviceTemplateReference(string $labDefinitionId, array $attributes, string $actorId): array
    {
        $this->assertActor($actorId);
        $this->requireDefinition($labDefinitionId, ['DRAFT']);
        $templateRevisionId = $this->requiredString($attributes, 'device_template_revision_id');
        $templateRevision = $this->enterpriseState->findPublishedDeviceTemplateRevisionForSimulation($templateRevisionId);
        if ($templateRevision === null) {
            throw new DomainException('Lab definitions may reference only published Device Template Revisions.');
        }
        $requiredCapabilities = $this->stringList($attributes['required_capabilities'] ?? []);
        $templateCapabilities = $this->stringList($templateRevision['capabilities'] ?? []);
        $missingCapabilities = array_values(array_diff($requiredCapabilities, $templateCapabilities));
        if ($missingCapabilities !== []) {
            throw new DomainException('Lab Device Template reference requires capabilities not declared by the published revision: '.implode(', ', $missingCapabilities));
        }

        $id = (string) Str::uuid7();
        $now = now();
        DB::table(self::LAB_TEMPLATE_REFERENCES)->insert([
            'id' => $id,
            'lab_definition_id' => $labDefinitionId,
            'device_template_revision_id' => $templateRevisionId,
            'reference_key' => $this->requiredString($attributes, 'reference_key'),
            'required_capabilities' => $this->json($requiredCapabilities),
            'parameters' => $this->json($this->arrayValue($attributes['parameters'] ?? [])),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->refreshDraftDigest($labDefinitionId);

        return $this->row(self::LAB_TEMPLATE_REFERENCES, $id);
    }

    /** @return array<string, mixed> */
    public function validateLabDefinition(string $labDefinitionId, string $actorId): array
    {
        $this->assertActor($actorId);
        $definition = $this->requireDefinition($labDefinitionId, ['DRAFT', 'VALIDATED']);
        $payload = $this->definitionPayload($definition);
        $tasks = $this->listValue($payload['tasks']);
        $dependencies = $this->listValue($payload['dependencies']);
        $errors = [];
        $warnings = [];

        try {
            $this->assertEnvironmentContract($this->arrayValue($payload['environment_contract']));
            $this->assertEnvironmentBinding(
                (string) $definition->environment_binding_mode,
                $definition->enterprise_id === null ? null : (string) $definition->enterprise_id,
                $definition->baseline_id === null ? null : (string) $definition->baseline_id,
            );
            if ((string) $definition->environment_binding_mode === self::ENTERPRISE_BASELINE) {
                $this->assertEnterpriseBaselineCapabilities(
                    $this->arrayValue($payload['environment_contract']),
                    $definition->enterprise_id === null ? null : (string) $definition->enterprise_id,
                    $definition->baseline_id === null ? null : (string) $definition->baseline_id,
                );
            }
        } catch (DomainException|LogicException $exception) {
            $errors[] = $exception->getMessage();
        }
        if ($tasks === []) {
            $errors[] = 'Lab Definition requires at least one Task Graph node.';
        }
        if ($this->arrayValue($payload['validation']) === []) {
            $errors[] = 'Lab Definition requires a validation contract.';
        }

        $graph = $this->inspectTaskGraph($tasks, $dependencies);
        $errors = [...$errors, ...$graph['errors']];
        if ($graph['parallel_task_count'] === 0 && count($tasks) > 1) {
            $warnings[] = 'Task Graph is valid but currently linear; parallel and conditional branches remain supported.';
        }
        if ((string) $definition->environment_binding_mode === self::LAB_LOCAL && $this->listValue($payload['device_template_references']) === []) {
            $warnings[] = 'Lab-local definition relies on capabilities only and does not pin a Device Template Revision.';
        }

        $digest = $this->digest($payload);
        $report = [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_digest' => $digest,
            'validated_by' => $actorId,
            'checked_at' => now()->toISOString(),
            'task_graph' => [
                'root_task_ids' => $graph['root_task_ids'],
                'parallel_task_count' => $graph['parallel_task_count'],
                'conditional_dependency_count' => $graph['conditional_dependency_count'],
            ],
        ];
        DB::table(self::LAB_DEFINITIONS)->where('id', $labDefinitionId)->update([
            'status' => $errors === [] ? 'VALIDATED' : 'DRAFT',
            'validation_report' => $this->json($report),
            'validated_at' => $errors === [] ? now() : null,
            'digest' => $digest,
            'updated_at' => now(),
        ]);

        return $this->row(self::LAB_DEFINITIONS, $labDefinitionId);
    }

    /** @return array<string, mixed> */
    public function publishLabDefinition(string $labDefinitionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($labDefinitionId): array {
            $definition = DB::table(self::LAB_DEFINITIONS)->where('id', $labDefinitionId)->lockForUpdate()->first();
            if ($definition === null || (string) $definition->status !== 'VALIDATED') {
                throw new LogicException('Lab publication requires a validated draft.');
            }
            $digest = $this->digest($this->definitionPayload($definition));
            $report = $this->decode($definition->validation_report);
            if (($report['valid'] ?? false) !== true || ! is_string($report['validated_digest'] ?? null) || ! hash_equals($report['validated_digest'], $digest)) {
                throw new LogicException('Lab Definition changed after validation; validate it again before publication.');
            }

            DB::table(self::LAB_DEFINITIONS)->where('id', $labDefinitionId)->update([
                'status' => 'PUBLISHED',
                'digest' => $digest,
                'published_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->row(self::LAB_DEFINITIONS, $labDefinitionId);
        });
    }

    /** @return array<string, mixed> */
    public function cloneLabDefinition(string $labDefinitionId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($labDefinitionId, $actorId): array {
            $source = DB::table(self::LAB_DEFINITIONS)->where('id', $labDefinitionId)->where('status', 'PUBLISHED')->lockForUpdate()->first();
            if ($source === null) {
                throw new LogicException('Only a published Lab Definition Revision can seed a new draft.');
            }
            if (DB::table(self::LAB_DEFINITIONS)->where('lab_id', $source->lab_id)->whereIn('status', ['DRAFT', 'VALIDATED'])->exists()) {
                throw new LogicException('Lab already has an open definition revision.');
            }

            $newDefinitionId = (string) Str::uuid7();
            $now = now();
            $nextRevision = (int) DB::table(self::LAB_DEFINITIONS)->where('lab_id', $source->lab_id)->max('revision') + 1;
            DB::table(self::LAB_DEFINITIONS)->insert([
                'id' => $newDefinitionId,
                'lab_id' => $source->lab_id,
                'based_on_revision_id' => $source->id,
                'enterprise_id' => $source->enterprise_id,
                'baseline_id' => $source->baseline_id,
                'slug' => $source->slug,
                'title_ar' => $source->title_ar,
                'title_en' => $source->title_en,
                'revision' => $nextRevision,
                'status' => 'DRAFT',
                'environment_binding_mode' => $source->environment_binding_mode,
                'environment_contract' => $source->environment_contract,
                'configuration' => $source->configuration,
                'validation' => $source->validation,
                'validation_report' => null,
                'digest' => $source->digest,
                'validated_at' => null,
                'published_at' => null,
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $taskMap = [];
            foreach (DB::table(self::LAB_TASKS)->where('lab_definition_id', $source->id)->get() as $task) {
                $newTaskId = (string) Str::uuid7();
                $taskMap[(string) $task->id] = $newTaskId;
                DB::table(self::LAB_TASKS)->insert([
                    'id' => $newTaskId,
                    'lab_definition_id' => $newDefinitionId,
                    'task_key' => $task->task_key,
                    'title_ar' => $task->title_ar,
                    'objective' => $task->objective,
                    'permitted_tools' => $task->permitted_tools,
                    'required_capabilities' => $task->required_capabilities,
                    'required_role' => $task->required_role,
                    'expected_signals' => $task->expected_signals,
                    'validation_rule' => $task->validation_rule,
                    'completion_weight' => $task->completion_weight,
                    'is_optional' => $task->is_optional,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach (DB::table(self::LAB_DEPENDENCIES)->where('lab_definition_id', $source->id)->get() as $dependency) {
                DB::table(self::LAB_DEPENDENCIES)->insert([
                    'id' => (string) Str::uuid7(),
                    'lab_definition_id' => $newDefinitionId,
                    'predecessor_task_id' => $taskMap[(string) $dependency->predecessor_task_id],
                    'successor_task_id' => $taskMap[(string) $dependency->successor_task_id],
                    'dependency_type' => $dependency->dependency_type,
                    'condition' => $dependency->condition,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach (DB::table(self::LAB_TEMPLATE_REFERENCES)->where('lab_definition_id', $source->id)->get() as $reference) {
                DB::table(self::LAB_TEMPLATE_REFERENCES)->insert([
                    'id' => (string) Str::uuid7(),
                    'lab_definition_id' => $newDefinitionId,
                    'device_template_revision_id' => $reference->device_template_revision_id,
                    'reference_key' => $reference->reference_key,
                    'required_capabilities' => $reference->required_capabilities,
                    'parameters' => $reference->parameters,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $this->refreshDraftDigest($newDefinitionId);

            return $this->row(self::LAB_DEFINITIONS, $newDefinitionId);
        });
    }

    private function assertEnvironmentBinding(string $mode, ?string $enterpriseId, ?string $baselineId): void
    {
        if ($mode === self::LAB_LOCAL) {
            if ($enterpriseId !== null || $baselineId !== null) {
                throw new DomainException('Lab-local definitions cannot claim an Enterprise or Baseline binding.');
            }

            return;
        }
        if ($mode !== self::ENTERPRISE_BASELINE || $enterpriseId === null || $baselineId === null) {
            throw new DomainException('Enterprise-bound Lab definitions require one Enterprise and one published Baseline.');
        }
        if ($this->enterpriseState->findPublishedBaselineForSimulation($enterpriseId, $baselineId) === null) {
            throw new DomainException('Enterprise-bound Lab definitions must pin a published Baseline owned by that Enterprise.');
        }
    }

    /** @param array<string, mixed> $contract */
    private function assertEnvironmentContract(array $contract): void
    {
        if (($contract['schema'] ?? null) !== self::LAB_ENVIRONMENT_SCHEMA) {
            throw new DomainException('Lab Environment Contract schema is invalid.');
        }
        if (($contract['execution_model'] ?? null) !== self::INTERNAL_EXECUTION_MODEL) {
            throw new DomainException('Lab Environment Contract must use the internal CEP simulation model.');
        }
        if ($this->stringList($contract['required_capabilities'] ?? []) === []) {
            throw new DomainException('Lab Environment Contract requires at least one simulation capability.');
        }
        foreach (['provider_id', 'container_image', 'vm_image', 'remote_range_id', 'baseline_id', 'enterprise_id'] as $forbiddenKey) {
            if (array_key_exists($forbiddenKey, $contract)) {
                throw new DomainException('Lab Environment Contract cannot embed runtime-provider or fixed binding identifiers.');
            }
        }
    }

    /** @param array<string, mixed> $contract */
    private function assertEnterpriseBaselineCapabilities(array $contract, ?string $enterpriseId, ?string $baselineId): void
    {
        if ($enterpriseId === null || $baselineId === null) {
            return;
        }
        $state = $this->enterpriseState->findPublishedBaselineForSimulation($enterpriseId, $baselineId);
        if ($state === null) {
            return;
        }
        $baselineState = is_array($state->baseline['state'] ?? null) ? $state->baseline['state'] : [];
        $available = $this->stringList($baselineState['capabilities'] ?? []);
        $missing = array_values(array_diff(
            $this->stringList($contract['required_capabilities'] ?? []),
            $available,
        ));
        if ($missing !== []) {
            throw new DomainException('Enterprise Baseline does not satisfy Lab Environment Contract capabilities: '.implode(', ', $missing));
        }
    }

    private function refreshDraftDigest(string $labDefinitionId): void
    {
        $definition = $this->requireDefinition($labDefinitionId, ['DRAFT']);
        DB::table(self::LAB_DEFINITIONS)->where('id', $labDefinitionId)->update([
            'digest' => $this->digest($this->definitionPayload($definition)),
            'validation_report' => null,
            'validated_at' => null,
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function definitionPayload(stdClass $definition): array
    {
        $tasks = DB::table(self::LAB_TASKS)
            ->where('lab_definition_id', $definition->id)
            ->orderBy('task_key')
            ->get()
            ->map(fn (stdClass $task): array => [
                'id' => (string) $task->id,
                'task_key' => (string) $task->task_key,
                'title_ar' => (string) $task->title_ar,
                'objective' => (string) $task->objective,
                'permitted_tools' => $this->decode($task->permitted_tools),
                'required_capabilities' => $this->decode($task->required_capabilities),
                'required_role' => $task->required_role === null ? null : (string) $task->required_role,
                'expected_signals' => $this->decode($task->expected_signals),
                'validation_rule' => $this->decode($task->validation_rule),
                'completion_weight' => (float) $task->completion_weight,
                'is_optional' => (bool) $task->is_optional,
            ])->all();
        $taskKeys = collect($tasks)->mapWithKeys(fn (array $task): array => [$task['id'] => $task['task_key']]);
        $dependencies = DB::table(self::LAB_DEPENDENCIES)
            ->where('lab_definition_id', $definition->id)
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $dependency): array => [
                'id' => (string) $dependency->id,
                'predecessor_task_id' => (string) $dependency->predecessor_task_id,
                'predecessor_task_key' => (string) $taskKeys->get((string) $dependency->predecessor_task_id),
                'successor_task_id' => (string) $dependency->successor_task_id,
                'successor_task_key' => (string) $taskKeys->get((string) $dependency->successor_task_id),
                'dependency_type' => (string) $dependency->dependency_type,
                'condition' => $dependency->condition === null ? null : $this->decode($dependency->condition),
            ])->all();
        $templateReferences = DB::table(self::LAB_TEMPLATE_REFERENCES)
            ->where('lab_definition_id', $definition->id)
            ->orderBy('reference_key')
            ->get()
            ->map(fn (stdClass $reference): array => [
                'id' => (string) $reference->id,
                'reference_key' => (string) $reference->reference_key,
                'device_template_revision_id' => (string) $reference->device_template_revision_id,
                'required_capabilities' => $this->decode($reference->required_capabilities),
                'parameters' => $this->decode($reference->parameters),
            ])->all();

        return [
            'lab_id' => (string) $definition->lab_id,
            'revision' => (int) $definition->revision,
            'environment_binding_mode' => (string) $definition->environment_binding_mode,
            'enterprise_id' => $definition->enterprise_id === null ? null : (string) $definition->enterprise_id,
            'baseline_id' => $definition->baseline_id === null ? null : (string) $definition->baseline_id,
            'environment_contract' => $this->decode($definition->environment_contract),
            'configuration' => $this->decode($definition->configuration),
            'validation' => $this->decode($definition->validation),
            'tasks' => $tasks,
            'dependencies' => $dependencies,
            'device_template_references' => $templateReferences,
        ];
    }

    /**
     * @param  list<mixed>  $tasks
     * @param  list<mixed>  $dependencies
     * @return array{errors:list<string>,root_task_ids:list<string>,parallel_task_count:int,conditional_dependency_count:int}
     */
    private function inspectTaskGraph(array $tasks, array $dependencies): array
    {
        $taskIds = [];
        foreach ($tasks as $task) {
            if (is_array($task) && is_string($task['id'] ?? null)) {
                $taskIds[] = $task['id'];
            }
        }
        $indegree = array_fill_keys($taskIds, 0);
        $outdegree = array_fill_keys($taskIds, 0);
        $adjacency = array_fill_keys($taskIds, []);
        $conditionalCount = 0;
        foreach ($dependencies as $dependency) {
            if (! is_array($dependency)) {
                continue;
            }
            $from = $dependency['predecessor_task_id'] ?? null;
            $to = $dependency['successor_task_id'] ?? null;
            if (! is_string($from) || ! is_string($to) || ! array_key_exists($from, $indegree) || ! array_key_exists($to, $indegree)) {
                continue;
            }
            $adjacency[$from][] = $to;
            $indegree[$to]++;
            $outdegree[$from]++;
            if (($dependency['dependency_type'] ?? null) === 'CONDITIONAL') {
                $conditionalCount++;
            }
        }

        $roots = array_keys(array_filter($indegree, fn (int $degree): bool => $degree === 0));
        $queue = $roots;
        $visited = 0;
        $workingIndegree = $indegree;
        while ($queue !== []) {
            $current = array_shift($queue);
            $visited++;
            foreach ($adjacency[$current] as $successor) {
                $workingIndegree[$successor]--;
                if ($workingIndegree[$successor] === 0) {
                    $queue[] = $successor;
                }
            }
        }
        $errors = [];
        if ($taskIds !== [] && $roots === []) {
            $errors[] = 'Lab Task Graph must have at least one entry node.';
        }
        if ($visited !== count($taskIds)) {
            $errors[] = 'Lab Task Graph must be acyclic.';
        }
        $parallelTaskCount = count(array_filter($outdegree, fn (int $degree): bool => $degree > 1));

        return [
            'errors' => $errors,
            'root_task_ids' => $roots,
            'parallel_task_count' => $parallelTaskCount,
            'conditional_dependency_count' => $conditionalCount,
        ];
    }

    /** @param list<string> $statuses */
    private function requireDefinition(string $id, array $statuses): stdClass
    {
        $definition = DB::table(self::LAB_DEFINITIONS)->where('id', $id)->first();
        if ($definition === null) {
            throw new DomainException('Lab Definition Revision not found.');
        }
        if (! in_array((string) $definition->status, $statuses, true)) {
            throw new LogicException('Lab Definition Revision is not editable in its current lifecycle state.');
        }

        return $definition;
    }

    private function requireTask(string $labDefinitionId, string $taskId): stdClass
    {
        $task = DB::table(self::LAB_TASKS)->where('id', $taskId)->where('lab_definition_id', $labDefinitionId)->first();
        if ($task === null) {
            throw new DomainException('Lab task dependency endpoints must belong to the same Lab Definition Revision.');
        }

        return $task;
    }

    /** @param array<string, mixed> $attributes */
    private function requiredString(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException("{$key} is required.");
        }

        return trim($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function stringOr(mixed $value, string $fallback): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    /** @return array<string, mixed> */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<mixed> */
    private function listValue(mixed $value): array
    {
        return is_array($value) && array_is_list($value) ? $value : [];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && trim($item) !== ''));
    }

    private function assertActor(string $actorId): void
    {
        if (trim($actorId) === '') {
            throw new DomainException('Definition mutations require an attributed actor.');
        }
    }

    /** @return array<string, mixed> */
    private function row(string $table, string $id): array
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException("Definition record not found in {$table}.");
        }

        return (array) $row;
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<array-key, mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
