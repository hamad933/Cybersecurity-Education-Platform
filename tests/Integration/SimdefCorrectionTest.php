<?php

namespace Tests\Integration;

use App\Modules\Simulator\Application\SimulationDefinitionService;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimdefCorrectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function enterprise_bound_lab_preserves_exact_published_lineage_and_existing_runtime_preparation(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);

        $lab = DB::table('simulation_lab_definitions')->where('slug', 'lab-auth-investigation')->firstOrFail();
        $baseline = DB::table('simulation_baselines')->where('id', $lab->baseline_id)->firstOrFail();

        $this->assertSame('PUBLISHED', $lab->status);
        $this->assertSame(SimulationDefinitionService::ENTERPRISE_BASELINE, $lab->environment_binding_mode);
        $this->assertSame((string) $baseline->enterprise_id, (string) $lab->enterprise_id);
        $this->assertDatabaseHas('simulation_labs', ['id' => $lab->lab_id, 'slug' => $lab->slug]);
        $this->assertDatabaseHas('simulation_digital_twin_revisions', [
            'id' => $baseline->digital_twin_revision_id,
            'enterprise_id' => $baseline->enterprise_id,
            'digital_twin_id' => $baseline->digital_twin_id,
            'status' => 'PUBLISHED',
        ]);
        $this->assertSame(3, DB::table('simulation_lab_task_nodes')->where('lab_definition_id', $lab->id)->count());
        $this->assertSame(2, DB::table('simulation_lab_task_dependencies')->where('lab_definition_id', $lab->id)->count());
        $this->assertArrayNotHasKey('steps', $this->decode($lab->configuration));

        $run = app(SimulationEnterpriseService::class)->prepareStandaloneLabRun(
            (string) $lab->id,
            20260829,
            ['mode' => 'GUIDED'],
            'SIMDEF:INTEGRATION',
        );

        $this->assertSame('Standalone Lab Run', $run['run_type']);
        $this->assertSame('PREPARING', $run['lifecycle']);
        $this->assertSame((string) $lab->enterprise_id, $run['enterprise_id']);
        $this->assertSame((string) $lab->baseline_id, $run['baseline_id']);
        $this->assertSame((string) $baseline->digital_twin_revision_id, $run['digital_twin_revision_id']);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $run['id'], 'event_type' => 'RUN_PREPARED']);
        $this->assertDatabaseHas('simulation_runtime_snapshots', ['run_id' => $run['id'], 'snapshot_kind' => 'RUN_PREPARATION']);
        $this->assertDatabaseHas('simulation_runtime_checkpoints', ['run_id' => $run['id'], 'restorable' => true]);
    }

    #[Test]
    public function cyclic_task_graph_cannot_cross_the_validation_publish_gate(): void
    {
        $definitions = app(SimulationDefinitionService::class);
        $draft = $definitions->createLabDraft(
            'lab-cycle-rejection',
            'Cycle rejection lab',
            SimulationDefinitionService::LAB_LOCAL,
            $this->environmentContract(),
            ['profile' => 'ISOLATED'],
            ['result_schema' => 'cep.lab-result.v1'],
            null,
            null,
            'SIMDEF:INTEGRATION',
        );
        $first = $definitions->addLabTask((string) $draft['id'], $this->task('FIRST'), 'SIMDEF:INTEGRATION');
        $second = $definitions->addLabTask((string) $draft['id'], $this->task('SECOND'), 'SIMDEF:INTEGRATION');
        $definitions->addLabTaskDependency((string) $draft['id'], [
            'predecessor_task_id' => $first['id'],
            'successor_task_id' => $second['id'],
            'dependency_type' => 'REQUIRED',
        ], 'SIMDEF:INTEGRATION');
        $definitions->addLabTaskDependency((string) $draft['id'], [
            'predecessor_task_id' => $second['id'],
            'successor_task_id' => $first['id'],
            'dependency_type' => 'CONDITIONAL',
            'condition' => ['when' => 'RETRY_REQUIRED'],
        ], 'SIMDEF:INTEGRATION');

        $validated = $definitions->validateLabDefinition((string) $draft['id'], 'SIMDEF:INTEGRATION');
        $report = $this->decode($validated['validation_report']);

        $this->assertSame('DRAFT', $validated['status']);
        $this->assertFalse($report['valid']);
        $this->assertContains('Lab Task Graph must have at least one entry node.', $report['errors']);
        $this->assertContains('Lab Task Graph must be acyclic.', $report['errors']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Lab publication requires a validated draft.');
        $definitions->publishLabDefinition((string) $draft['id'], 'SIMDEF:INTEGRATION');
    }

    #[Test]
    public function lab_local_definition_can_publish_while_bounded_runtime_rejects_unsupported_preparation(): void
    {
        $definitions = app(SimulationDefinitionService::class);
        $draft = $definitions->createLabDraft(
            'lab-local-publishable',
            'Lab-local publishable definition',
            SimulationDefinitionService::LAB_LOCAL,
            $this->environmentContract(),
            ['profile' => 'ISOLATED'],
            ['result_schema' => 'cep.lab-result.v1'],
            null,
            null,
            'SIMDEF:INTEGRATION',
        );
        $definitions->addLabTask((string) $draft['id'], $this->task('OBSERVE'), 'SIMDEF:INTEGRATION');
        $validated = $definitions->validateLabDefinition((string) $draft['id'], 'SIMDEF:INTEGRATION');
        $published = $definitions->publishLabDefinition((string) $draft['id'], 'SIMDEF:INTEGRATION');

        $this->assertSame('VALIDATED', $validated['status']);
        $this->assertSame('PUBLISHED', $published['status']);
        $this->assertNull($published['enterprise_id']);
        $this->assertNull($published['baseline_id']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Lab-local definitions are valid, but this bounded runtime path currently requires an Enterprise Baseline binding.');
        app(SimulationEnterpriseService::class)->prepareStandaloneLabRun(
            (string) $published['id'],
            20260829,
            ['mode' => 'GUIDED'],
            'SIMDEF:INTEGRATION',
        );
    }

    /** @return array<string, mixed> */
    private function environmentContract(): array
    {
        return [
            'schema' => 'cep.simulation.lab-environment-contract.v1',
            'execution_model' => 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
            'required_capabilities' => ['APPLICATION_LOGGING'],
        ];
    }

    /** @return array<string, mixed> */
    private function task(string $key): array
    {
        return [
            'task_key' => $key,
            'title_ar' => "Task {$key}",
            'objective' => "Complete {$key} without mutating runtime ownership.",
            'permitted_tools' => ['Browser'],
            'required_capabilities' => ['APPLICATION_LOGGING'],
            'expected_signals' => ['HTTP_LOG'],
            'validation_rule' => ['signal' => 'HTTP_LOG', 'operator' => 'EXISTS'],
            'completion_weight' => 1,
            'is_optional' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
