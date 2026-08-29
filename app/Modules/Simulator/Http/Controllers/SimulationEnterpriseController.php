<?php

namespace App\Modules\Simulator\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Enterprise\Application\EnterpriseDefinitionAuthoring;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use App\Modules\Simulator\Application\SimulationDefinitionService;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;
use stdClass;

final class SimulationEnterpriseController extends Controller
{
    private const RUN_OPERATIONS_TABLE = 'simulation_run_operations';

    private const RESULT_REPLAY_COMPARES_TABLE = 'simulation_result_replay_compares';

    public function __construct(
        private readonly SimulationEnterpriseService $simulation,
        private readonly SimulationDefinitionService $definitions,
        private readonly EnterpriseDefinitionAuthoring $enterpriseDefinitions,
        private readonly SimulationEnterpriseStateReader $enterpriseState,
    ) {}

    public function index(): Response
    {
        return $this->render('enterprise');
    }

    public function scenarios(): Response
    {
        return $this->render('scenarios');
    }

    public function labs(): Response
    {
        return $this->render('labs');
    }

    public function runs(): Response
    {
        return $this->render('runs');
    }

    public function results(): Response
    {
        return $this->render('results');
    }

    public function createEnterpriseEntity(Request $request, string $enterprise): RedirectResponse
    {
        $validated = $request->validate([
            'entity_key' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'entity_type' => ['required', 'string', 'in:SYSTEM,APPLICATION,SERVICE,NETWORK,DEVICE,IDENTITY,DATA,SECURITY_CONTROL,ROLE,TEAM'],
            'name_ar' => ['required', 'string', 'max:240'],
            'name_en' => ['nullable', 'string', 'max:240'],
            'lifecycle_state' => ['nullable', 'string', 'in:ACTIVE,INACTIVE,RETIRED'],
            'properties' => ['nullable', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->enterpriseDefinitions->createEntity($enterprise, $validated, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function createEnterpriseRelationship(Request $request, string $enterprise): RedirectResponse
    {
        $validated = $request->validate([
            'source_entity_id' => ['required', 'uuid'],
            'target_entity_id' => ['required', 'uuid', 'different:source_entity_id'],
            'relationship_type' => ['required', 'string', 'in:HOSTS,DEPENDS_ON,AUTHENTICATES_WITH,CONNECTS_TO,MANAGED_BY,PROTECTED_BY,STORES,ROUTES_TO,MEMBER_OF'],
            'properties' => ['nullable', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->enterpriseDefinitions->createRelationship($enterprise, $validated, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function createDeviceTemplateDraft(Request $request, string $enterprise): RedirectResponse
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'device_type' => ['required', 'string', 'max:80'],
            'name_ar' => ['required', 'string', 'max:240'],
            'capabilities' => ['required', 'array', 'min:1', 'max:64'],
            'capabilities.*' => ['string', 'max:120'],
            'state_model' => ['required', 'array', 'min:1'],
            'actions' => ['nullable', 'array'],
            'events' => ['nullable', 'array'],
            'telemetry' => ['nullable', 'array'],
            'behavior_rules' => ['required', 'array', 'min:1'],
            'validation_hooks' => ['nullable', 'array'],
        ]);
        $definition = [
            'capabilities' => $validated['capabilities'],
            'state_model' => $validated['state_model'],
            'actions' => $validated['actions'] ?? [],
            'events' => $validated['events'] ?? [],
            'telemetry' => $validated['telemetry'] ?? [],
            'behavior_rules' => $validated['behavior_rules'],
            'validation_hooks' => $validated['validation_hooks'] ?? [],
        ];

        return $this->mutate(fn () => $this->enterpriseDefinitions->createDeviceTemplateDraft(
            $enterprise,
            (string) $validated['template_key'],
            (string) $validated['device_type'],
            (string) $validated['name_ar'],
            $definition,
            $this->actorId(),
        ), 'cep.simulation.index');
    }

    public function validateDeviceTemplateRevision(string $revision): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->enterpriseDefinitions->validateDeviceTemplateRevision($revision, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function publishDeviceTemplateRevision(string $revision): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->enterpriseDefinitions->publishDeviceTemplateRevision($revision, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function createDigitalTwinDraft(Request $request, string $enterprise): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'name_ar' => ['required', 'string', 'max:240'],
            'behavior_model' => ['required', 'array', 'min:1'],
        ]);

        return $this->mutate(fn () => $this->enterpriseDefinitions->createDigitalTwinDraft(
            $enterprise,
            (string) $validated['slug'],
            (string) $validated['name_ar'],
            $validated['behavior_model'],
            $this->actorId(),
        ), 'cep.simulation.index');
    }

    public function addDigitalTwinComponent(Request $request, string $revision): RedirectResponse
    {
        $validated = $request->validate([
            'component_key' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'ownership_scope' => ['required', 'string', 'in:ENTERPRISE_ENTITY,SIMULATION_LOCAL'],
            'enterprise_entity_id' => ['nullable', 'uuid'],
            'device_template_revision_id' => ['nullable', 'uuid'],
            'name_ar' => ['required', 'string', 'max:240'],
            'simulation_definition' => ['required', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->enterpriseDefinitions->addDigitalTwinComponent($revision, $validated, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function addDigitalTwinRelationship(Request $request, string $revision): RedirectResponse
    {
        $validated = $request->validate([
            'source_component_id' => ['required', 'uuid'],
            'target_component_id' => ['required', 'uuid', 'different:source_component_id'],
            'relationship_type' => ['required', 'string', 'in:HOSTS,DEPENDS_ON,AUTHENTICATES_WITH,CONNECTS_TO,MANAGED_BY,PROTECTED_BY,STORES,ROUTES_TO,MEMBER_OF'],
            'properties' => ['nullable', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->enterpriseDefinitions->addDigitalTwinRelationship($revision, $validated, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function validateDigitalTwinRevision(string $revision): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->enterpriseDefinitions->validateDigitalTwinRevision($revision, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function publishDigitalTwinRevision(string $revision): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->enterpriseDefinitions->publishDigitalTwinRevision($revision, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function cloneDigitalTwinRevision(string $revision): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->enterpriseDefinitions->cloneDigitalTwinRevision($revision, $this->actorId()),
            'cep.simulation.index',
        );
    }

    public function createLabDraft(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'title_ar' => ['required', 'string', 'max:240'],
            'environment_binding_mode' => ['required', 'string', 'in:LAB_LOCAL,ENTERPRISE_BASELINE'],
            'enterprise_id' => ['nullable', 'uuid'],
            'baseline_id' => ['nullable', 'uuid'],
            'environment_contract' => ['required', 'array'],
            'configuration' => ['required', 'array'],
            'validation' => ['required', 'array', 'min:1'],
        ]);

        return $this->mutate(fn () => $this->definitions->createLabDraft(
            (string) $validated['slug'],
            (string) $validated['title_ar'],
            (string) $validated['environment_binding_mode'],
            $validated['environment_contract'],
            $validated['configuration'],
            $validated['validation'],
            isset($validated['enterprise_id']) ? (string) $validated['enterprise_id'] : null,
            isset($validated['baseline_id']) ? (string) $validated['baseline_id'] : null,
            $this->actorId(),
        ), 'cep.simulation.labs');
    }

    public function addLabTask(Request $request, string $lab): RedirectResponse
    {
        $validated = $request->validate([
            'task_key' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'title_ar' => ['required', 'string', 'max:240'],
            'objective' => ['required', 'string', 'max:2000'],
            'permitted_tools' => ['nullable', 'array', 'max:32'],
            'required_capabilities' => ['nullable', 'array', 'max:32'],
            'required_role' => ['nullable', 'string', 'max:120'],
            'expected_signals' => ['nullable', 'array', 'max:64'],
            'validation_rule' => ['required', 'array', 'min:1'],
            'completion_weight' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'is_optional' => ['nullable', 'boolean'],
        ]);

        return $this->mutate(
            fn () => $this->definitions->addLabTask($lab, $validated, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function addLabTaskDependency(Request $request, string $lab): RedirectResponse
    {
        $validated = $request->validate([
            'predecessor_task_id' => ['required', 'uuid'],
            'successor_task_id' => ['required', 'uuid', 'different:predecessor_task_id'],
            'dependency_type' => ['required', 'string', 'in:REQUIRED,CONDITIONAL'],
            'condition' => ['nullable', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->definitions->addLabTaskDependency($lab, $validated, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function addLabDeviceTemplateReference(Request $request, string $lab): RedirectResponse
    {
        $validated = $request->validate([
            'device_template_revision_id' => ['required', 'uuid'],
            'reference_key' => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'required_capabilities' => ['nullable', 'array', 'max:64'],
            'parameters' => ['nullable', 'array'],
        ]);

        return $this->mutate(
            fn () => $this->definitions->addLabDeviceTemplateReference($lab, $validated, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function validateLabDefinition(string $lab): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->definitions->validateLabDefinition($lab, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function publishLabDefinition(string $lab): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->definitions->publishLabDefinition($lab, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function cloneLabDefinition(string $lab): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->definitions->cloneLabDefinition($lab, $this->actorId()),
            'cep.simulation.labs',
        );
    }

    public function prepareScenario(Request $request, string $scenario): RedirectResponse
    {
        $validated = $request->validate([
            'baseline_id' => ['required', 'uuid'],
            'seed' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'mode' => ['nullable', 'string', 'in:GUIDED,UNGUIDED,SOLO,TEAM,ROLE_BASED'],
        ]);

        return $this->mutate(fn () => $this->simulation->prepareScenarioRun(
            $scenario,
            (string) $validated['baseline_id'],
            (int) $validated['seed'],
            ['mode' => $validated['mode'] ?? 'GUIDED'],
            $this->actorId(),
        ), 'cep.simulation.runs');
    }

    public function prepareLab(Request $request, string $lab): RedirectResponse
    {
        $validated = $request->validate([
            'seed' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'mode' => ['nullable', 'string', 'in:GUIDED,UNGUIDED,SOLO,TEAM,ROLE_BASED'],
        ]);

        return $this->mutate(fn () => $this->simulation->prepareStandaloneLabRun(
            $lab,
            (int) $validated['seed'],
            ['mode' => $validated['mode'] ?? 'GUIDED'],
            $this->actorId(),
        ), 'cep.simulation.runs');
    }

    public function ready(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->markReady($run, $this->actorId()));
    }

    public function start(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->start($run, $this->actorId()));
    }

    public function pause(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->pause($run, $this->actorId()));
    }

    public function resume(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->resume($run, $this->actorId()));
    }

    public function stop(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->stop($run, $this->actorId()));
    }

    public function complete(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->completeInternalSimulation($run, $this->actorId()));
    }

    public function snapshot(string $run): RedirectResponse
    {
        return $this->runTransition(fn () => $this->simulation->captureSnapshot($run, $this->actorId()));
    }

    public function operate(Request $request, string $run): RedirectResponse
    {
        $validated = $request->validate([
            'operation_key' => ['required', 'string', 'min:12', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'verb' => ['required', 'string', 'in:SET_CONTROL_STATE'],
            'target' => ['required', 'string', 'in:IDENTITY_MFA'],
            'value' => ['required', 'boolean'],
        ]);

        return $this->mutate(fn () => $this->simulation->applyOperation($run, [
            'operation_key' => (string) $validated['operation_key'],
            'verb' => (string) $validated['verb'],
            'target' => (string) $validated['target'],
            'value' => (bool) $validated['value'],
        ], $this->actorId()), 'cep.simulation.runs');
    }

    public function sealResult(Request $request, string $run): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:ACHIEVED,PARTIAL,NOT_ACHIEVED,INCONCLUSIVE,NOT_EVALUATED'],
            'summary_ar' => ['required', 'string', 'max:2000'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return $this->mutate(fn () => $this->simulation->sealResult(
            $run,
            (string) $validated['outcome'],
            (string) $validated['summary_ar'],
            isset($validated['score']) ? (float) $validated['score'] : null,
            $this->actorId(),
        ), 'cep.simulation.results');
    }

    public function replayCompare(string $result): RedirectResponse
    {
        return $this->mutate(fn () => $this->simulation->replayAndCompareResult($result, $this->actorId()), 'cep.simulation.results');
    }

    public function candidateEvidenceHandoff(Request $request, string $result): RedirectResponse
    {
        $validated = $request->validate([
            'claim_ar' => ['required', 'string', 'max:1000'],
            'artifact_refs' => ['nullable', 'array', 'max:20'],
            'artifact_refs.*' => ['string', 'max:240'],
            'intake_contract_ref' => ['nullable', 'string', 'max:160'],
        ]);

        return $this->mutate(fn () => $this->simulation->createCandidateEvidenceHandoff($result, [
            'claim_ar' => $validated['claim_ar'],
            'artifact_refs' => $validated['artifact_refs'] ?? [],
        ], $validated['intake_contract_ref'] ?? null, $this->actorId()), 'cep.simulation.results');
    }

    private function render(string $section): Response
    {
        return Inertia::render('SimulationEnterprise/Workspace', [
            'section' => $section,
            'navigation' => $this->navigation(),
            'enterprises' => $section === 'enterprise' ? $this->enterpriseState->listForSimulationWorkspace() : [],
            'scenarios' => $section === 'scenarios' ? $this->scenariosData() : [],
            'labs' => $section === 'labs' ? $this->labsData() : [],
            'runs' => $section === 'runs' ? $this->runsData() : [],
            'results' => $section === 'results' ? $this->resultsData() : [],
            'outcomes' => SimulationEnterpriseService::OUTCOMES,
        ]);
    }

    /** @return list<array{key:string,label:string,href:string}> */
    private function navigation(): array
    {
        return [
            ['key' => 'enterprise', 'label' => 'المؤسسة', 'href' => '/simulation'],
            ['key' => 'scenarios', 'label' => 'السيناريوهات', 'href' => '/simulation/scenarios'],
            ['key' => 'labs', 'label' => 'المختبرات', 'href' => '/simulation/labs'],
            ['key' => 'runs', 'label' => 'التشغيلات', 'href' => '/simulation/runs'],
            ['key' => 'results', 'label' => 'النتائج', 'href' => '/simulation/results'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function scenariosData(): array
    {
        return DB::table('simulation_scenario_definitions')->where('status', 'PUBLISHED')->orderByDesc('created_at')->get()->map(function (stdClass $scenario): array {
            $environmentContract = $this->decode($scenario->environment_contract);
            $modules = DB::table('simulation_scenario_lab_references as reference')
                ->join('simulation_lab_definitions as lab', 'lab.id', '=', 'reference.lab_definition_id')
                ->where('reference.scenario_definition_id', $scenario->id)
                ->orderBy('reference.ordinal')
                ->get(['reference.id', 'reference.module_key', 'reference.ordinal', 'reference.policy', 'lab.id as lab_id', 'lab.title_ar as lab_title_ar'])
                ->map(fn (stdClass $module): array => [
                    'reference_id' => (string) $module->id,
                    'module_key' => (string) $module->module_key,
                    'ordinal' => (int) $module->ordinal,
                    'policy' => $this->decode($module->policy),
                    'lab_definition_id' => (string) $module->lab_id,
                    'lab_title_ar' => (string) $module->lab_title_ar,
                ])->all();

            return [
                'id' => (string) $scenario->id,
                'slug' => (string) $scenario->slug,
                'title_ar' => (string) $scenario->title_ar,
                'revision' => (int) $scenario->revision,
                'digest' => (string) $scenario->digest,
                'environment_contract' => $environmentContract,
                'orchestration' => $this->decode($scenario->orchestration),
                'validation' => $this->decode($scenario->validation),
                'lab_module_references' => $modules,
                'preparation_targets' => $this->simulation->compatiblePreparationTargets($environmentContract),
                'provenance' => SimulationEnterpriseService::PROVENANCE_SIMULATED,
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function labsData(): array
    {
        return DB::table('simulation_lab_definitions')
            ->orderBy('slug')
            ->orderByDesc('revision')
            ->get()
            ->map(function (stdClass $lab): array {
                $tasks = DB::table('simulation_lab_task_nodes')
                    ->where('lab_definition_id', $lab->id)
                    ->orderBy('task_key')
                    ->get()
                    ->map(fn (stdClass $task): array => [
                        'id' => (string) $task->id,
                        'task_key' => (string) $task->task_key,
                        'title_ar' => (string) $task->title_ar,
                        'objective' => (string) $task->objective,
                        'permitted_tools' => $this->decodeList($task->permitted_tools),
                        'required_capabilities' => $this->decodeList($task->required_capabilities),
                        'required_role' => $task->required_role === null ? null : (string) $task->required_role,
                        'expected_signals' => $this->decodeList($task->expected_signals),
                        'validation_rule' => $this->decode($task->validation_rule),
                        'completion_weight' => (float) $task->completion_weight,
                        'is_optional' => (bool) $task->is_optional,
                    ])->all();
                $dependencies = DB::table('simulation_lab_task_dependencies')
                    ->where('lab_definition_id', $lab->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn (stdClass $dependency): array => [
                        'id' => (string) $dependency->id,
                        'predecessor_task_id' => (string) $dependency->predecessor_task_id,
                        'successor_task_id' => (string) $dependency->successor_task_id,
                        'dependency_type' => (string) $dependency->dependency_type,
                        'condition' => $dependency->condition === null ? null : $this->decode($dependency->condition),
                    ])->all();
                $templateReferences = DB::table('simulation_lab_device_template_references')
                    ->where('lab_definition_id', $lab->id)
                    ->orderBy('reference_key')
                    ->get()
                    ->map(fn (stdClass $reference): array => [
                        'id' => (string) $reference->id,
                        'reference_key' => (string) $reference->reference_key,
                        'device_template_revision_id' => (string) $reference->device_template_revision_id,
                        'required_capabilities' => $this->decodeList($reference->required_capabilities),
                        'parameters' => $this->decode($reference->parameters),
                    ])->all();

                return [
                    'id' => (string) $lab->id,
                    'lab_id' => (string) $lab->lab_id,
                    'based_on_revision_id' => $lab->based_on_revision_id === null ? null : (string) $lab->based_on_revision_id,
                    'slug' => (string) $lab->slug,
                    'title_ar' => (string) $lab->title_ar,
                    'revision' => (int) $lab->revision,
                    'status' => (string) $lab->status,
                    'environment_binding_mode' => (string) $lab->environment_binding_mode,
                    'enterprise_id' => $lab->enterprise_id === null ? null : (string) $lab->enterprise_id,
                    'baseline_id' => $lab->baseline_id === null ? null : (string) $lab->baseline_id,
                    'environment_contract' => $this->decode($lab->environment_contract),
                    'digest' => (string) $lab->digest,
                    'configuration' => $this->decode($lab->configuration),
                    'validation' => $this->decode($lab->validation),
                    'validation_report' => $this->decode($lab->validation_report),
                    'validated_at' => $lab->validated_at === null ? null : (string) $lab->validated_at,
                    'published_at' => $lab->published_at === null ? null : (string) $lab->published_at,
                    'tasks' => $tasks,
                    'task_dependencies' => $dependencies,
                    'device_template_references' => $templateReferences,
                    'can_prepare' => (string) $lab->status === 'PUBLISHED'
                        && (string) $lab->environment_binding_mode === SimulationDefinitionService::ENTERPRISE_BASELINE,
                    'provenance' => SimulationEnterpriseService::PROVENANCE_SIMULATED,
                ];
            })->all();
    }

    /** @return list<array<string, mixed>> */
    private function runsData(): array
    {
        return DB::table('simulation_runs')->orderByDesc('created_at')->limit(50)->get()->map(function (stdClass $run): array {
            $definitionTitle = $run->run_type === SimulationEnterpriseService::RUN_SCENARIO
                ? DB::table('simulation_scenario_definitions')->where('id', $run->scenario_definition_id)->value('title_ar')
                : DB::table('simulation_lab_definitions')->where('id', $run->standalone_lab_definition_id)->value('title_ar');
            $events = DB::table('simulation_run_events')->where('run_id', $run->id)->orderBy('sequence')->get()->map(fn (stdClass $event): array => [
                'sequence' => (int) $event->sequence,
                'event_type' => (string) $event->event_type,
                'payload' => $this->decode($event->payload),
                'actor_id' => (string) $event->actor_id,
                'occurred_at' => (string) $event->occurred_at,
            ])->all();
            $snapshots = DB::table('simulation_runtime_snapshots')->where('run_id', $run->id)->orderBy('sequence')->get()->map(fn (stdClass $snapshot): array => [
                'id' => (string) $snapshot->id,
                'sequence' => (int) $snapshot->sequence,
                'event_sequence' => (int) $snapshot->event_sequence,
                'snapshot_kind' => (string) $snapshot->snapshot_kind,
                'state' => $this->decode($snapshot->state),
                'state_digest' => (string) $snapshot->state_digest,
                'captured_by' => (string) $snapshot->captured_by,
                'captured_at' => (string) $snapshot->captured_at,
            ])->all();
            $checkpoints = DB::table('simulation_runtime_checkpoints')->where('run_id', $run->id)->orderBy('sequence')->get()->map(fn (stdClass $checkpoint): array => [
                'id' => (string) $checkpoint->id,
                'sequence' => (int) $checkpoint->sequence,
                'source_snapshot_id' => (string) $checkpoint->source_snapshot_id,
                'state' => $this->decode($checkpoint->state),
                'state_digest' => (string) $checkpoint->state_digest,
                'restorable' => (bool) $checkpoint->restorable,
                'created_by' => (string) $checkpoint->created_by,
                'created_at' => (string) $checkpoint->created_at,
            ])->all();
            $operations = DB::table(self::RUN_OPERATIONS_TABLE)->where('run_id', $run->id)->orderBy('occurred_at')->get()->map(fn (stdClass $operation): array => [
                'id' => (string) $operation->id,
                'operation_key' => (string) $operation->operation_key,
                'verb' => (string) $operation->verb,
                'target' => (string) $operation->target,
                'input' => $this->decode($operation->input),
                'telemetry' => $this->decode($operation->telemetry),
                'actor_id' => (string) $operation->actor_id,
            ])->all();
            $resultId = DB::table('simulation_run_results')->where('run_id', $run->id)->value('id');

            return [
                'id' => (string) $run->id,
                'run_type' => (string) $run->run_type,
                'lifecycle' => (string) $run->lifecycle,
                'definition_title_ar' => (string) ($definitionTitle ?? 'تعريف غير متاح'),
                'enterprise_id' => (string) $run->enterprise_id,
                'digital_twin_id' => (string) $run->digital_twin_id,
                'digital_twin_revision_id' => (string) $run->digital_twin_revision_id,
                'baseline_id' => (string) $run->baseline_id,
                'scenario_definition_id' => $run->scenario_definition_id,
                'standalone_lab_definition_id' => $run->standalone_lab_definition_id,
                'seed' => (int) $run->seed,
                'execution_policies' => $this->decode($run->execution_policies),
                'runtime_state' => $this->decode($run->runtime_state),
                'input_digest' => (string) $run->input_digest,
                'provenance' => (string) $run->provenance,
                'source_fixture' => (bool) $run->source_fixture,
                'available_actions' => $this->simulation->availableActions((string) $run->lifecycle),
                'events' => $events,
                'operations' => $operations,
                'snapshots' => $snapshots,
                'checkpoints' => $checkpoints,
                'result_id' => $resultId === null ? null : (string) $resultId,
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function resultsData(): array
    {
        return DB::table('simulation_run_results')
            ->orderByDesc('sealed_at')
            ->limit(50)
            ->get()
            ->map(function (stdClass $result): array {
                $handoff = DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $result->id)->first();
                $compare = DB::table(self::RESULT_REPLAY_COMPARES_TABLE)->where('result_id', $result->id)->orderByDesc('compared_at')->first();
                $sealedPayload = $this->decode($result->sealed_payload);

                return [
                    'id' => (string) $result->id,
                    'run_id' => (string) $result->run_id,
                    'run_type' => (string) ($sealedPayload['run_type'] ?? ''),
                    'run_lifecycle' => (string) ($sealedPayload['run_lifecycle'] ?? ''),
                    'outcome' => (string) $result->outcome,
                    'score' => $result->score === null ? null : (float) $result->score,
                    'summary_ar' => (string) $result->summary_ar,
                    'sealed_payload' => $sealedPayload,
                    'replay_timeline' => $this->decodeList($result->replay_timeline),
                    'artifacts' => $this->decodeList($result->artifacts),
                    'result_revision' => (int) $result->result_revision,
                    'result_digest' => (string) $result->result_digest,
                    'provenance' => (string) $result->provenance,
                    'source_fixture' => (bool) $result->source_fixture,
                    'sealed_by' => (string) $result->sealed_by,
                    'sealed_at' => (string) $result->sealed_at,
                    'replay_compare' => $compare === null ? null : [
                        'id' => (string) $compare->id,
                        'integrity_match' => (bool) $compare->integrity_match,
                        'sealed_result_digest' => (string) $compare->sealed_result_digest,
                        'reconstructed_state_digest' => (string) $compare->reconstructed_state_digest,
                        'reconstruction' => $this->decode($compare->reconstruction),
                        'actor_id' => (string) $compare->actor_id,
                        'compared_at' => (string) $compare->compared_at,
                    ],
                    'candidate_evidence_handoff' => $handoff === null ? null : [
                        'id' => (string) $handoff->id,
                        'status' => (string) $handoff->status,
                        'candidate_manifest' => $this->decode($handoff->candidate_manifest),
                        'source_result_revision' => (int) $handoff->source_result_revision,
                        'source_result_digest' => (string) $handoff->source_result_digest,
                        'provenance' => (string) $handoff->provenance,
                        'source_fixture' => (bool) $handoff->source_fixture,
                        'manifest_digest' => (string) $handoff->manifest_digest,
                        'created_by' => (string) $handoff->created_by,
                        'intake_contract_ref' => $handoff->intake_contract_ref,
                    ],
                ];
            })->all();
    }

    /** @param callable():mixed $action */
    private function mutate(callable $action, string $route): RedirectResponse
    {
        try {
            $action();
        } catch (LogicException $exception) {
            return redirect()->back()->withErrors(['simulation' => $exception->getMessage()]);
        }

        return redirect()->route($route);
    }

    /** @param callable():mixed $action */
    private function runTransition(callable $action): RedirectResponse
    {
        return $this->mutate($action, 'cep.simulation.runs');
    }

    private function actorId(): string
    {
        $actorId = auth()->id();
        abort_if($actorId === null, 401);

        return (string) $actorId;
    }

    /**
     * @return array<array-key, mixed>
     */
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

    /**
     * @return list<mixed>
     */
    private function decodeList(mixed $value): array
    {
        $decoded = $this->decode($value);

        return array_is_list($decoded) ? $decoded : [];
    }
}
