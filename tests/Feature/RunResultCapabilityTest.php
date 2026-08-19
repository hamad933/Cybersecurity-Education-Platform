<?php

namespace Tests\Feature;

use App\Modules\Simulator\RunResult\RunResultCapability;
use App\Modules\Simulator\RunResult\RunResultVocabulary;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RunResultCapabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deterministic_internal_execution_preserves_run_type_lifecycle_causality_telemetry_validation_and_snapshots(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');

        $first = $this->completeLabRun($capability, $labId, 4242, ['mode' => 'SOLO']);
        $second = $this->completeLabRun($capability, $labId, 4242, ['mode' => 'SOLO']);
        $firstState = $this->runtimeState((string) $first['id']);
        $secondState = $this->runtimeState((string) $second['id']);

        $this->assertSame(RunResultVocabulary::RUN_STANDALONE_LAB, $first['run_type']);
        $this->assertSame('COMPLETED', $first['lifecycle']);
        $this->assertSame('INTERNAL_HIGH_FIDELITY_V1', $firstState['engine']);
        $this->assertSame($firstState['trace_digest'], $secondState['trace_digest']);
        $this->assertSame($firstState['causality'], $secondState['causality']);
        $this->assertSame($firstState['telemetry'], $secondState['telemetry']);
        $this->assertSame($firstState['validation'], $secondState['validation']);
        $this->assertTrue($firstState['validation']['traceable']);
        $this->assertTrue($firstState['validation']['deterministic']);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $first['id'], 'event_type' => 'CAUSAL_CHAIN_APPLIED']);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $first['id'], 'event_type' => 'TELEMETRY_CAPTURED']);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $first['id'], 'event_type' => 'VALIDATION_EVALUATED']);
        $this->assertGreaterThanOrEqual(2, DB::table('simulation_runtime_snapshots')->where('run_id', $first['id'])->count());
    }

    #[Test]
    public function lifecycle_transitions_support_pause_resume_stop_and_fail_without_becoming_result_outcomes(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');

        $run = $capability->prepareStandaloneLabRun($labId, 5100);
        $capability->markReady((string) $run['id']);
        $capability->start((string) $run['id']);
        $capability->pause((string) $run['id']);
        $capability->captureSnapshot((string) $run['id']);
        $capability->resume((string) $run['id']);
        $stopped = $capability->stop((string) $run['id']);
        $result = $capability->sealResult((string) $run['id'], 'NOT_EVALUATED', 'تم إيقاف التشغيل قبل اكتمال التقييم.');

        $this->assertSame('STOPPED', $stopped['lifecycle']);
        $this->assertSame('NOT_EVALUATED', $result['effective_revision']['outcome']);
        $this->assertNotContains('NOT_EVALUATED', RunResultVocabulary::LIFECYCLES);
        $this->assertNotContains('STOPPED', RunResultVocabulary::RESULT_OUTCOMES);

        $failed = $capability->prepareStandaloneLabRun($labId, 5200);
        $capability->markReady((string) $failed['id']);
        $failed = $capability->fail((string) $failed['id'], 'VALIDATION_PRECONDITION', ['check' => 'fixture']);
        $failedState = $this->runtimeState((string) $failed['id']);
        $this->assertSame('FAILED', $failed['lifecycle']);
        $this->assertSame('VALIDATION_PRECONDITION', $failedState['failure']['reason_code']);
    }

    #[Test]
    public function scenario_run_instantiates_lab_modules_without_turning_them_into_standalone_runs(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');

        $run = $capability->prepareScenarioRun($scenarioId, 6100, ['mode' => 'TEAM']);

        $this->assertSame(RunResultVocabulary::RUN_SCENARIO, $run['run_type']);
        $this->assertSame($scenarioId, (string) $run['scenario_definition_id']);
        $this->assertNull($run['standalone_lab_definition_id']);
        $this->assertGreaterThanOrEqual(1, DB::table('simulation_run_lab_module_instances')->where('run_id', $run['id'])->count());
    }

    #[Test]
    public function result_is_sealed_once_per_run_and_corrections_create_immutable_result_revisions(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $run = $this->completeLabRun($capability, $labId, 7001);
        $result = $capability->sealResult((string) $run['id'], 'PARTIAL', 'النتيجة الأصلية المختومة.', 72.5, [['kind' => 'timeline-segment']]);
        $resultId = (string) $result['id'];

        $this->assertDatabaseCount('simulation_run_result_revisions', 1);
        $corrected = $capability->correctResult(
            $resultId,
            'ACHIEVED',
            'تصحيح تحليلي مختوم مع بقاء السجل الأصلي دون تعديل.',
            'تم اكتشاف خطأ في تفسير معيار النتيجة بعد الختم.',
            91.0,
        );

        $this->assertSame('PARTIAL', DB::table('simulation_run_results')->where('id', $resultId)->value('outcome'));
        $this->assertSame(2, $corrected['effective_revision']['revision']);
        $this->assertSame('ACHIEVED', $corrected['effective_revision']['outcome']);
        $this->assertDatabaseCount('simulation_run_result_revisions', 2);

        $revisionId = (string) DB::table('simulation_run_result_revisions')->where('result_id', $resultId)->where('revision', 2)->value('id');
        try {
            DB::table('simulation_run_result_revisions')->where('id', $revisionId)->update(['outcome' => 'PARTIAL']);
            $this->fail('Sealed Result revision update unexpectedly succeeded.');
        } catch (QueryException $exception) {
            $this->assertSame('55000', (string) $exception->getCode());
        }
    }

    #[Test]
    public function replay_aar_and_compare_are_derived_from_sealed_result_history(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $firstRun = $this->completeLabRun($capability, $labId, 8101);
        $secondRun = $this->completeLabRun($capability, $labId, 8102);
        $first = $capability->sealResult((string) $firstRun['id'], 'PARTIAL', 'محاولة أولى.', 76.0, [['kind' => 'log-extract']]);
        $second = $capability->sealResult((string) $secondRun['id'], 'ACHIEVED', 'محاولة ثانية.', 93.0, [['kind' => 'log-extract']]);

        $replay = $capability->replay((string) $first['id']);
        $aar = $capability->afterActionReview((string) $first['id']);
        $comparison = $capability->compareResults([(string) $first['id'], (string) $second['id']]);

        $this->assertSame('EVENT_SEMANTIC_REPLAY', $replay['kind']);
        $this->assertFalse($replay['full_environment_state_replay']);
        $this->assertGreaterThanOrEqual(1, count($replay['timeline']));
        $this->assertGreaterThanOrEqual(2, count($replay['runtime_snapshots']));
        $this->assertSame('AFTER_ACTION_REVIEW', $aar['kind']);
        $this->assertSame('RESULT_ANALYSIS_ONLY', $aar['interpretation']['governance_boundary']);
        $this->assertArrayHasKey('causality', $aar['facts']);
        $this->assertArrayHasKey('telemetry', $aar['facts']);
        $this->assertArrayHasKey('validation', $aar['facts']);
        $this->assertSame('COMPARE_RUNS', $comparison['kind']);
        $this->assertTrue($comparison['comparable_scope']);
        $this->assertCount(2, $comparison['results']);
        $this->assertSame(17.0, $comparison['results'][1]['score_delta_from_first']);
    }

    #[Test]
    public function candidate_evidence_handoff_contains_source_provenance_but_stops_before_progress_and_evidence_intake(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $run = $this->completeLabRun($capability, $labId, 9101);
        $result = $capability->sealResult((string) $run['id'], 'ACHIEVED', 'نتيجة مؤهلة للتسليم.', 96.0, [['kind' => 'runtime-artifact', 'ref' => 'artifact://run/9101']]);

        $handoff = $capability->createCandidateEvidenceHandoff(
            (string) $result['id'],
            'أظهر التشغيل قدرة على ربط الأحداث السببية بالقياس والتحقق.',
            'capability-evidence',
            'owner',
            ['criterion:causal-analysis'],
            ['timeline', 'runtime-snapshot', 'artifact://run/9101'],
        );
        $manifest = json_decode((string) $handoff['candidate_manifest'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('READY_FOR_INTAKE', $handoff['status']);
        $this->assertSame('RUN_RESULT_CANDIDATE_EVIDENCE_HANDOFF', $manifest['handoff_kind']);
        $this->assertSame((string) $result['id'], $manifest['source']['result_id']);
        $this->assertSame((string) $run['id'], $manifest['source']['run_id']);
        $this->assertNotEmpty($manifest['source']['result_integrity']);
        $this->assertDatabaseCount('evidence_records', 0);

        $delivered = $capability->markHandoffHandedOff((string) $handoff['id']);
        $this->assertSame('HANDED_OFF', $delivered['status']);
        $this->assertNotNull($delivered['handed_off_at']);
    }

    #[Test]
    public function terminal_run_cannot_be_executed_or_snapshotted_again(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $capability = app(RunResultCapability::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $run = $this->completeLabRun($capability, $labId, 9901);

        try {
            $capability->captureSnapshot((string) $run['id']);
            $this->fail('Terminal Run snapshot unexpectedly succeeded.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Terminal Run snapshots', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $capability->executeInternal((string) $run['id']);
    }

    /**
     * @param  array<string,mixed>  $policies
     * @return array<string,mixed>
     */
    private function completeLabRun(RunResultCapability $capability, string $labId, int $seed, array $policies = []): array
    {
        $run = $capability->prepareStandaloneLabRun($labId, $seed, $policies);
        $capability->markReady((string) $run['id']);
        $capability->start((string) $run['id']);

        return $capability->executeInternal((string) $run['id']);
    }

    /** @return array<string,mixed> */
    private function runtimeState(string $runId): array
    {
        return json_decode(
            (string) DB::table('simulation_runs')->where('id', $runId)->value('runtime_state'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
