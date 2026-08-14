<?php

namespace App\Modules\Simulator\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

final class SimulationEnterpriseController extends Controller
{
    private readonly SimulationEnterpriseService $simulation;

    public function __construct(SimulationEnterpriseService $simulation)
    {
        $this->simulation = $simulation;
    }

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

    public function prepareScenario(Request $request, string $scenario): RedirectResponse
    {
        $validated = $request->validate([
            'seed' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'mode' => ['nullable', 'string', 'in:GUIDED,UNGUIDED,SOLO,TEAM,ROLE_BASED'],
        ]);
        $this->simulation->prepareScenarioRun(
            $scenario,
            (int) $validated['seed'],
            ['mode' => $validated['mode'] ?? 'GUIDED'],
            auth()->id() === null ? null : (string) auth()->id(),
        );

        return redirect()->route('cep.simulation.runs');
    }

    public function prepareLab(Request $request, string $lab): RedirectResponse
    {
        $validated = $request->validate([
            'seed' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'mode' => ['nullable', 'string', 'in:GUIDED,UNGUIDED,SOLO,TEAM,ROLE_BASED'],
        ]);
        $this->simulation->prepareStandaloneLabRun(
            $lab,
            (int) $validated['seed'],
            ['mode' => $validated['mode'] ?? 'GUIDED'],
            auth()->id() === null ? null : (string) auth()->id(),
        );

        return redirect()->route('cep.simulation.runs');
    }

    public function ready(string $run): RedirectResponse
    {
        $this->simulation->markReady($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function start(string $run): RedirectResponse
    {
        $this->simulation->start($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function pause(string $run): RedirectResponse
    {
        $this->simulation->pause($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function resume(string $run): RedirectResponse
    {
        $this->simulation->resume($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function stop(string $run): RedirectResponse
    {
        $this->simulation->stop($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function complete(string $run): RedirectResponse
    {
        $this->simulation->completeInternalSimulation($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function snapshot(string $run): RedirectResponse
    {
        $this->simulation->captureSnapshot($run);

        return redirect()->route('cep.simulation.runs');
    }

    public function sealResult(Request $request, string $run): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:ACHIEVED,PARTIAL,NOT_ACHIEVED,INCONCLUSIVE,NOT_EVALUATED'],
            'summary_ar' => ['required', 'string', 'max:2000'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $this->simulation->sealResult(
            $run,
            $validated['outcome'],
            $validated['summary_ar'],
            isset($validated['score']) ? (float) $validated['score'] : null,
        );

        return redirect()->route('cep.simulation.results');
    }

    public function candidateEvidenceHandoff(Request $request, string $result): RedirectResponse
    {
        $validated = $request->validate([
            'claim_ar' => ['required', 'string', 'max:1000'],
            'artifact_refs' => ['nullable', 'array', 'max:20'],
            'artifact_refs.*' => ['string', 'max:240'],
            'intake_contract_ref' => ['nullable', 'string', 'max:160'],
        ]);
        $this->simulation->createCandidateEvidenceHandoff($result, [
            'claim_ar' => $validated['claim_ar'],
            'artifact_refs' => $validated['artifact_refs'] ?? [],
            'source' => 'SIMULATION_RUN_RESULT',
        ], $validated['intake_contract_ref'] ?? null);

        return redirect()->route('cep.simulation.results');
    }

    private function render(string $section): Response
    {
        return Inertia::render('SimulationEnterprise/Workspace', [
            'section' => $section,
            'navigation' => $this->navigation(),
            'enterprises' => $section === 'enterprise' ? $this->enterprises() : [],
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

    /** @return list<array<string,mixed>> */
    private function enterprises(): array
    {
        return DB::table('simulation_enterprises')->orderBy('name_ar')->get()->map(function (stdClass $enterprise): array {
            $twin = DB::table('simulation_digital_twin_revisions')->where('enterprise_id', $enterprise->id)->where('status', 'PUBLISHED')->orderByDesc('revision')->first();
            $baseline = DB::table('simulation_baselines')->where('enterprise_id', $enterprise->id)->where('status', 'PUBLISHED')->orderByDesc('revision')->first();

            return [
                'id' => (string) $enterprise->id,
                'slug' => (string) $enterprise->slug,
                'name_ar' => (string) $enterprise->name_ar,
                'description_ar' => $enterprise->description_ar,
                'definition' => $this->decode($enterprise->definition),
                'is_fixture' => (bool) $enterprise->is_fixture,
                'digital_twin_revision' => $twin === null ? null : [
                    'id' => (string) $twin->id,
                    'revision' => (int) $twin->revision,
                    'digest' => (string) $twin->digest,
                    'topology' => $this->decode($twin->topology),
                ],
                'baseline' => $baseline === null ? null : [
                    'id' => (string) $baseline->id,
                    'revision' => (int) $baseline->revision,
                    'digest' => (string) $baseline->digest,
                    'state' => $this->decode($baseline->state),
                ],
            ];
        })->all();
    }

    /** @return list<array<string,mixed>> */
    private function scenariosData(): array
    {
        return DB::table('simulation_scenario_definitions')->where('status', 'PUBLISHED')->orderByDesc('created_at')->get()->map(function (stdClass $scenario): array {
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
                'baseline_id' => (string) $scenario->baseline_id,
                'digest' => (string) $scenario->digest,
                'orchestration' => $this->decode($scenario->orchestration),
                'validation' => $this->decode($scenario->validation),
                'lab_module_references' => $modules,
            ];
        })->all();
    }

    /** @return list<array<string,mixed>> */
    private function labsData(): array
    {
        return DB::table('simulation_lab_definitions')->where('status', 'PUBLISHED')->orderByDesc('created_at')->get()->map(fn (stdClass $lab): array => [
            'id' => (string) $lab->id,
            'slug' => (string) $lab->slug,
            'title_ar' => (string) $lab->title_ar,
            'revision' => (int) $lab->revision,
            'baseline_id' => (string) $lab->baseline_id,
            'digest' => (string) $lab->digest,
            'configuration' => $this->decode($lab->configuration),
            'validation' => $this->decode($lab->validation),
        ])->all();
    }

    /** @return list<array<string,mixed>> */
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
                'occurred_at' => (string) $event->occurred_at,
            ])->all();
            $snapshots = DB::table('simulation_runtime_snapshots')->where('run_id', $run->id)->orderBy('sequence')->get()->map(fn (stdClass $snapshot): array => [
                'id' => (string) $snapshot->id,
                'sequence' => (int) $snapshot->sequence,
                'event_sequence' => (int) $snapshot->event_sequence,
                'state_digest' => (string) $snapshot->state_digest,
                'captured_at' => (string) $snapshot->captured_at,
            ])->all();
            $resultId = DB::table('simulation_run_results')->where('run_id', $run->id)->value('id');

            return [
                'id' => (string) $run->id,
                'run_type' => (string) $run->run_type,
                'lifecycle' => (string) $run->lifecycle,
                'definition_title_ar' => (string) ($definitionTitle ?? 'تعريف غير متاح'),
                'enterprise_id' => (string) $run->enterprise_id,
                'digital_twin_revision_id' => (string) $run->digital_twin_revision_id,
                'baseline_id' => (string) $run->baseline_id,
                'scenario_definition_id' => $run->scenario_definition_id,
                'standalone_lab_definition_id' => $run->standalone_lab_definition_id,
                'seed' => (int) $run->seed,
                'execution_policies' => $this->decode($run->execution_policies),
                'runtime_state' => $this->decode($run->runtime_state),
                'input_digest' => (string) $run->input_digest,
                'available_actions' => $this->simulation->availableActions((string) $run->lifecycle),
                'events' => $events,
                'snapshots' => $snapshots,
                'result_id' => $resultId === null ? null : (string) $resultId,
            ];
        })->all();
    }

    /** @return list<array<string,mixed>> */
    private function resultsData(): array
    {
        return DB::table('simulation_run_results as result')
            ->join('simulation_runs as run', 'run.id', '=', 'result.run_id')
            ->orderByDesc('result.sealed_at')
            ->limit(50)
            ->get(['result.*', 'run.run_type', 'run.lifecycle'])
            ->map(function (stdClass $result): array {
                $handoff = DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $result->id)->first();

                return [
                    'id' => (string) $result->id,
                    'run_id' => (string) $result->run_id,
                    'run_type' => (string) $result->run_type,
                    'run_lifecycle' => (string) $result->lifecycle,
                    'outcome' => (string) $result->outcome,
                    'score' => $result->score === null ? null : (float) $result->score,
                    'summary_ar' => (string) $result->summary_ar,
                    'sealed_payload' => $this->decode($result->sealed_payload),
                    'replay_timeline' => $this->decodeList($result->replay_timeline),
                    'artifacts' => $this->decodeList($result->artifacts),
                    'sealed_at' => (string) $result->sealed_at,
                    'candidate_evidence_handoff' => $handoff === null ? null : [
                        'id' => (string) $handoff->id,
                        'status' => (string) $handoff->status,
                        'candidate_manifest' => $this->decode($handoff->candidate_manifest),
                        'intake_contract_ref' => $handoff->intake_contract_ref,
                    ],
                ];
            })->all();
    }

    /** @return array<string,mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) === false || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<mixed> */
    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_is_list($value) ? $value : [];
        }
        if (is_string($value) === false || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) && array_is_list($decoded) ? $decoded : [];
    }
}
