<?php

namespace Tests\Feature;

use App\Modules\Enterprise\Application\EnterpriseDigitalTwinService;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EnterpriseDigitalTwinCapabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function enterprise_is_the_canonical_aggregate_and_catalog_is_only_a_projection(): void
    {
        $service = app(EnterpriseDigitalTwinService::class);
        $enterprise = $service->createEnterprise([
            'slug' => 'acme-canonical',
            'name_ar' => 'مؤسسة أكمي التدريبية',
            'properties' => ['sector' => 'synthetic-training'],
        ]);

        $entities = [];
        foreach (EnterpriseDigitalTwinService::ENTITY_TYPES as $index => $type) {
            $entities[$type] = $service->addEntity((string) $enterprise['id'], [
                'stable_key' => strtolower($type).'-'.($index + 1),
                'entity_type' => $type,
                'name_ar' => 'عنصر '.$type,
                'properties' => ['synthetic' => true],
                'revision_provenance' => ['source' => 'CEP-BUILD-001-W03-C01'],
            ]);
        }

        $service->addRelationship((string) $enterprise['id'], [
            'source_entity_id' => $entities['SYSTEM']['id'],
            'target_entity_id' => $entities['APPLICATION']['id'],
            'relationship_type' => 'HOSTS',
        ]);
        $service->addRelationship((string) $enterprise['id'], [
            'source_entity_id' => $entities['APPLICATION']['id'],
            'target_entity_id' => $entities['SERVICE']['id'],
            'relationship_type' => 'DEPENDS_ON',
        ]);
        $service->addRelationship((string) $enterprise['id'], [
            'source_entity_id' => $entities['SERVICE']['id'],
            'target_entity_id' => $entities['SECURITY_CONTROL']['id'],
            'relationship_type' => 'PROTECTED_BY',
        ]);

        $catalog = $service->catalogProjection((string) $enterprise['id']);

        $this->assertTrue($catalog['projection_only']);
        $this->assertSame('Enterprise', $catalog['canonical_owner']);
        foreach (EnterpriseDigitalTwinService::ENTITY_TYPES as $type) {
            $this->assertCount(1, $catalog['entities_by_type'][$type]);
        }
        $this->assertSame(
            ['DEPENDS_ON', 'HOSTS', 'PROTECTED_BY'],
            array_column($catalog['relationships'], 'relationship_type'),
        );
    }

    #[Test]
    public function one_enterprise_can_own_multiple_twins_that_reference_the_same_canonical_entities(): void
    {
        [$service, $enterprise, $system, $network] = $this->enterpriseFixture();

        $trainingTwin = $service->createDigitalTwin((string) $enterprise['id'], [
            'slug' => 'training',
            'name_ar' => 'توأم التدريب',
            'purpose' => 'Training simulation',
        ]);
        $incidentTwin = $service->createDigitalTwin((string) $enterprise['id'], [
            'slug' => 'incident-response',
            'name_ar' => 'توأم الاستجابة للحوادث',
            'purpose' => 'Incident response simulation',
        ]);

        $trainingDraft = $service->createDraftRevision(
            (string) $trainingTwin['id'],
            [(string) $system['id'], (string) $network['id']],
            [[
                'id' => 'sim-attacker-workstation',
                'object_type' => 'WORKSTATION',
                'name' => 'Synthetic attacker workstation',
                'properties' => ['synthetic' => true],
            ]],
            [[
                'relationship_type' => 'CONNECTS_TO',
                'source_id' => 'sim-attacker-workstation',
                'target_id' => (string) $network['id'],
            ]],
            ['telemetry' => ['network' => true]],
        );
        $incidentDraft = $service->createDraftRevision(
            (string) $incidentTwin['id'],
            [(string) $system['id'], (string) $network['id']],
            [],
            [],
            ['telemetry' => ['network' => true, 'control_events' => true]],
        );

        $this->assertTrue($service->validateDraftRevision((string) $trainingDraft['id'])['valid']);
        $this->assertTrue($service->validateDraftRevision((string) $incidentDraft['id'])['valid']);
        $trainingRevision = $service->publishRevision((string) $trainingDraft['id']);
        $incidentRevision = $service->publishRevision((string) $incidentDraft['id']);

        $trainingModel = $service->operationalModel((string) $trainingRevision['id']);
        $incidentModel = $service->operationalModel((string) $incidentRevision['id']);

        $this->assertCount(2, $service->listDigitalTwins((string) $enterprise['id']));
        $this->assertSame(
            (string) $system['id'],
            collect($trainingModel['nodes'])->firstWhere('origin', 'ENTERPRISE')['id'],
        );
        $this->assertContains((string) $system['id'], array_column($incidentModel['nodes'], 'id'));
        $this->assertSame(
            'SIMULATION_LOCAL',
            collect($trainingModel['nodes'])->firstWhere('id', 'sim-attacker-workstation')['origin'],
        );
        $this->assertDatabaseCount('enterprise_entities', 2);
    }

    #[Test]
    public function digital_twin_revision_requires_validation_is_immutable_after_publish_and_clones_to_a_new_draft(): void
    {
        [$service, $enterprise, $system, $network] = $this->enterpriseFixture();
        $twin = $service->createDigitalTwin((string) $enterprise['id'], [
            'slug' => 'appsec',
            'name_ar' => 'توأم أمن التطبيقات',
        ]);
        $draft = $service->createDraftRevision(
            (string) $twin['id'],
            [(string) $system['id'], (string) $network['id']],
            [],
            [],
            ['service_behavior' => ['latency_ms' => 15]],
        );

        $this->assertTrue($service->validateDraftRevision((string) $draft['id'])['valid']);
        $published = $service->publishRevision((string) $draft['id']);
        $nextDraft = $service->createDraftFromRevision((string) $published['id']);

        $this->assertSame('PUBLISHED', $published['status']);
        $this->assertSame('DRAFT', $nextDraft['status']);
        $this->assertSame($published['id'], $nextDraft['source_revision_id']);
        $this->assertGreaterThan($published['revision'], $nextDraft['revision']);

        $service->updateDraftRevision(
            (string) $nextDraft['id'],
            [(string) $system['id']],
            [],
            [],
            ['service_behavior' => ['latency_ms' => 20]],
        );
        $updatedDraft = $service->inspectRevision((string) $nextDraft['id']);
        $this->assertSame([], $updatedDraft['validation_report']);
        $this->assertNull($updatedDraft['validated_at']);

        try {
            DB::table('simulation_digital_twin_revisions')
                ->where('id', $published['id'])
                ->update(['digest' => str_repeat('a', 64)]);
            $this->fail('Published Digital Twin revision update unexpectedly succeeded.');
        } catch (QueryException $exception) {
            $this->assertSame('55000', (string) $exception->getCode());
        }
    }

    #[Test]
    public function baseline_pins_a_published_revision_and_historical_run_keeps_the_old_binding_after_new_revision_is_published(): void
    {
        [$service, $enterprise, $system, $network] = $this->enterpriseFixture();
        $twin = $service->createDigitalTwin((string) $enterprise['id'], [
            'slug' => 'history',
            'name_ar' => 'توأم تاريخ التشغيل',
        ]);
        $firstDraft = $service->createDraftRevision(
            (string) $twin['id'],
            [(string) $system['id'], (string) $network['id']],
            [],
            [],
            ['version_marker' => 1],
        );
        $service->validateDraftRevision((string) $firstDraft['id']);
        $firstRevision = $service->publishRevision((string) $firstDraft['id']);
        $firstBaseline = $service->createBaselineFromRevision(
            (string) $firstRevision['id'],
            ['control_state' => ['edr' => 'enabled']],
        );

        $runId = $this->createStandaloneRun(
            (string) $enterprise['id'],
            (string) $firstRevision['id'],
            (string) $firstBaseline['id'],
        );

        $secondDraft = $service->createDraftFromRevision((string) $firstRevision['id']);
        $service->updateDraftRevision(
            (string) $secondDraft['id'],
            [(string) $system['id'], (string) $network['id']],
            [],
            [],
            ['version_marker' => 2],
        );
        $service->validateDraftRevision((string) $secondDraft['id']);
        $secondRevision = $service->publishRevision((string) $secondDraft['id']);
        $secondBaseline = $service->createBaselineFromRevision(
            (string) $secondRevision['id'],
            ['control_state' => ['edr' => 'enabled', 'network_sensor' => 'enabled']],
        );

        $run = DB::table('simulation_runs')->where('id', $runId)->firstOrFail();

        $this->assertSame((string) $firstRevision['id'], (string) $run->digital_twin_revision_id);
        $this->assertSame((string) $firstBaseline['id'], (string) $run->baseline_id);
        $this->assertNotSame($firstRevision['id'], $secondRevision['id']);
        $this->assertNotSame($firstBaseline['id'], $secondBaseline['id']);
        $this->assertDatabaseHas('simulation_digital_twin_revisions', [
            'id' => $firstRevision['id'],
            'status' => 'PUBLISHED',
        ]);

        try {
            DB::table('simulation_baselines')
                ->where('id', $firstBaseline['id'])
                ->update(['digest' => str_repeat('b', 64)]);
            $this->fail('Published Baseline update unexpectedly succeeded.');
        } catch (QueryException $exception) {
            $this->assertSame('55000', (string) $exception->getCode());
        }
    }

    #[Test]
    public function enterprise_application_boundary_exposes_catalog_twins_and_operational_model_for_the_workspace(): void
    {
        [$service, $enterprise, $system, $network] = $this->enterpriseFixture();
        $twin = $service->createDigitalTwin((string) $enterprise['id'], [
            'slug' => 'workspace',
            'name_ar' => 'توأم مساحة العمل',
        ]);
        $draft = $service->createDraftRevision(
            (string) $twin['id'],
            [(string) $system['id'], (string) $network['id']],
            [[
                'id' => 'synthetic-telemetry-source',
                'object_type' => 'TELEMETRY_SOURCE',
                'name' => 'Synthetic telemetry source',
            ]],
            [[
                'relationship_type' => 'CONNECTS_TO',
                'source_id' => 'synthetic-telemetry-source',
                'target_id' => (string) $network['id'],
            ]],
            ['event_rules' => ['emit_network_event' => true]],
        );
        $service->validateDraftRevision((string) $draft['id']);
        $revision = $service->publishRevision((string) $draft['id']);
        $baseline = $service->createBaselineFromRevision((string) $revision['id'], [
            'initial_state' => ['network' => 'healthy'],
        ]);

        $state = app(SimulationEnterpriseStateReader::class)->findForSimulation(
            (string) $enterprise['id'],
            (string) $revision['id'],
            (string) $baseline['id'],
        );

        $this->assertNotNull($state);
        $this->assertTrue($state->enterprise['catalog_projection']['projection_only']);
        $this->assertCount(1, $state->enterprise['digital_twins']);
        $this->assertSame((string) $twin['id'], $state->digitalTwinRevision['digital_twin_id']);
        $this->assertContains(
            'ENTERPRISE',
            array_column($state->digitalTwinRevision['operational_model']['nodes'], 'origin'),
        );
        $this->assertContains(
            'SIMULATION_LOCAL',
            array_column($state->digitalTwinRevision['operational_model']['nodes'], 'origin'),
        );
        $this->assertSame((string) $twin['id'], $state->baseline['digital_twin_id']);
    }

    /**
     * @return array{EnterpriseDigitalTwinService,array<string,mixed>,array<string,mixed>,array<string,mixed>}
     */
    private function enterpriseFixture(): array
    {
        $service = app(EnterpriseDigitalTwinService::class);
        $enterprise = $service->createEnterprise([
            'slug' => 'fixture-'.Str::lower(Str::random(8)),
            'name_ar' => 'مؤسسة اختبار اصطناعية',
        ]);
        $system = $service->addEntity((string) $enterprise['id'], [
            'stable_key' => 'system-core',
            'entity_type' => 'SYSTEM',
            'name_ar' => 'النظام الأساسي',
        ]);
        $network = $service->addEntity((string) $enterprise['id'], [
            'stable_key' => 'network-corp',
            'entity_type' => 'NETWORK',
            'name_ar' => 'شبكة المؤسسة',
        ]);
        $service->addRelationship((string) $enterprise['id'], [
            'source_entity_id' => $system['id'],
            'target_entity_id' => $network['id'],
            'relationship_type' => 'CONNECTS_TO',
        ]);

        return [$service, $enterprise, $system, $network];
    }

    private function createStandaloneRun(
        string $enterpriseId,
        string $digitalTwinRevisionId,
        string $baselineId,
    ): string {
        $labId = (string) Str::uuid7();
        $runId = (string) Str::uuid7();
        $now = now();

        DB::table('simulation_lab_definitions')->insert([
            'id' => $labId,
            'enterprise_id' => $enterpriseId,
            'baseline_id' => $baselineId,
            'slug' => 'history-lab-'.Str::lower(Str::random(6)),
            'title_ar' => 'مختبر تاريخي اصطناعي',
            'title_en' => null,
            'revision' => 1,
            'status' => 'PUBLISHED',
            'configuration' => json_encode(['synthetic' => true], JSON_THROW_ON_ERROR),
            'validation' => json_encode(['valid' => true], JSON_THROW_ON_ERROR),
            'digest' => hash('sha256', $labId),
            'created_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('simulation_runs')->insert([
            'id' => $runId,
            'enterprise_id' => $enterpriseId,
            'digital_twin_revision_id' => $digitalTwinRevisionId,
            'baseline_id' => $baselineId,
            'run_type' => 'Standalone Lab Run',
            'scenario_definition_id' => null,
            'standalone_lab_definition_id' => $labId,
            'lifecycle' => 'READY',
            'execution_policies' => json_encode(['mode' => 'SOLO'], JSON_THROW_ON_ERROR),
            'seed' => 101,
            'runtime_state' => json_encode(['engine' => 'INTERNAL_HIGH_FIDELITY_V1'], JSON_THROW_ON_ERROR),
            'input_digest' => hash('sha256', $runId),
            'created_by' => null,
            'prepared_at' => $now,
            'ready_at' => $now,
            'started_at' => null,
            'completed_at' => null,
            'stopped_at' => null,
            'failed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $runId;
    }
}
