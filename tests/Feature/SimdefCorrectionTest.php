<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimdefCorrectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_definition_workflows_preserve_ownership_revision_and_graph_semantics(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $enterpriseId = (string) DB::table('simulation_enterprises')->value('id');

        foreach ([
            ['entity_key' => 'APP-CRM', 'entity_type' => 'APPLICATION', 'name_ar' => 'تطبيق إدارة العملاء'],
            ['entity_key' => 'IDP-CORE', 'entity_type' => 'IDENTITY', 'name_ar' => 'خدمة الهوية'],
        ] as $entity) {
            $this->actingAs($owner)
                ->post("/simulation/enterprise/{$enterpriseId}/entities", $entity + ['properties' => ['criticality' => 'HIGH']])
                ->assertRedirect(route('cep.simulation.index'))
                ->assertSessionHasNoErrors();
        }
        $applicationId = (string) DB::table('simulation_enterprise_entities')->where('entity_key', 'APP-CRM')->value('id');
        $identityId = (string) DB::table('simulation_enterprise_entities')->where('entity_key', 'IDP-CORE')->value('id');
        $this->actingAs($owner)->post("/simulation/enterprise/{$enterpriseId}/relationships", [
            'source_entity_id' => $applicationId,
            'target_entity_id' => $identityId,
            'relationship_type' => 'AUTHENTICATES_WITH',
            'properties' => ['protocol' => 'SIMULATED_OIDC'],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();

        $this->actingAs($owner)->post("/simulation/enterprise/{$enterpriseId}/device-templates", [
            'template_key' => 'WEB-APP-V1',
            'device_type' => 'Web Application',
            'name_ar' => 'قالب تطبيق ويب',
            'capabilities' => ['HTTP_REQUEST', 'APPLICATION_LOGGING'],
            'state_model' => ['service' => ['type' => 'enum', 'values' => ['UP', 'DOWN']]],
            'actions' => [['key' => 'REQUEST']],
            'events' => [['key' => 'REQUEST_RECEIVED']],
            'telemetry' => [['key' => 'HTTP_LOG']],
            'behavior_rules' => [['when' => 'REQUEST', 'emit' => 'HTTP_LOG']],
            'validation_hooks' => [['key' => 'REQUEST_OBSERVED']],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $templateRevisionId = (string) DB::table('simulation_device_template_revisions')->value('id');
        $this->actingAs($owner)->post("/simulation/device-template-revisions/{$templateRevisionId}/validate")
            ->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/simulation/device-template-revisions/{$templateRevisionId}/publish")
            ->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('simulation_device_template_revisions', ['id' => $templateRevisionId, 'status' => 'PUBLISHED']);

        $this->actingAs($owner)->post("/simulation/enterprise/{$enterpriseId}/digital-twins", [
            'slug' => 'application-security-twin',
            'name_ar' => 'توأم أمن التطبيقات',
            'behavior_model' => ['causality' => 'STATE_EVENT_TELEMETRY_VALIDATION'],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $twinRevision = DB::table('simulation_digital_twin_revisions')->where('status', 'DRAFT')->firstOrFail();
        $twinId = (string) $twinRevision->digital_twin_id;
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/components", [
            'component_key' => 'CRM-APPLICATION',
            'ownership_scope' => 'ENTERPRISE_ENTITY',
            'enterprise_entity_id' => $applicationId,
            'device_template_revision_id' => $templateRevisionId,
            'name_ar' => 'تطبيق العملاء المحاكى',
            'simulation_definition' => ['initial_state' => ['service' => 'UP']],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/components", [
            'component_key' => 'TRAINING-SENDER',
            'ownership_scope' => 'SIMULATION_LOCAL',
            'enterprise_entity_id' => null,
            'device_template_revision_id' => null,
            'name_ar' => 'مرسل تدريبي محلي',
            'simulation_definition' => ['purpose' => 'TRAINING_ONLY'],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $components = DB::table('simulation_digital_twin_components')->where('digital_twin_revision_id', $twinRevision->id)->orderBy('component_key')->get();
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/relationships", [
            'source_component_id' => $components[0]->id,
            'target_component_id' => $components[1]->id,
            'relationship_type' => 'CONNECTS_TO',
            'properties' => ['channel' => 'SIMULATED_HTTP'],
        ])->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/validate")
            ->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/publish")
            ->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/simulation/digital-twin-revisions/{$twinRevision->id}/clone")
            ->assertRedirect(route('cep.simulation.index'))->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('simulation_digital_twin_revisions')->where('digital_twin_id', $twinId)->count());
        $this->assertSame(1, DB::table('simulation_digital_twins')->where('id', $twinId)->count());
        $this->assertDatabaseHas('simulation_digital_twin_components', ['ownership_scope' => 'ENTERPRISE_ENTITY', 'enterprise_entity_id' => $applicationId]);
        $this->assertDatabaseHas('simulation_digital_twin_components', ['ownership_scope' => 'SIMULATION_LOCAL', 'enterprise_entity_id' => null]);

        $this->actingAs($owner)->post('/simulation/labs/drafts', [
            'slug' => 'lab-branching-investigation',
            'title_ar' => 'مختبر تحقيق متفرع',
            'environment_binding_mode' => 'LAB_LOCAL',
            'enterprise_id' => null,
            'baseline_id' => null,
            'environment_contract' => [
                'schema' => 'cep.simulation.lab-environment-contract.v1',
                'execution_model' => 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
                'required_capabilities' => ['HTTP_REQUEST', 'APPLICATION_LOGGING'],
            ],
            'configuration' => ['initial_state' => ['service' => 'UP']],
            'validation' => ['result_schema' => 'cep.lab-result.v1'],
        ])->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();
        $labDefinitionId = (string) DB::table('simulation_lab_definitions')->where('slug', 'lab-branching-investigation')->value('id');
        $stableLabId = (string) DB::table('simulation_lab_definitions')->where('id', $labDefinitionId)->value('lab_id');
        $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/device-template-references", [
            'device_template_revision_id' => $templateRevisionId,
            'reference_key' => 'TARGET-WEB-APP',
            'required_capabilities' => ['HTTP_REQUEST', 'APPLICATION_LOGGING'],
            'parameters' => ['profile' => 'ISOLATED'],
        ])->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();

        foreach ([
            ['task_key' => 'OBSERVE', 'title_ar' => 'رصد الإشارة', 'objective' => 'رصد سجل الطلب المحاكى.'],
            ['task_key' => 'TRACE', 'title_ar' => 'تتبع السببية', 'objective' => 'تتبع انتقال الحالة إلى السجل.'],
            ['task_key' => 'VERIFY', 'title_ar' => 'تحقق مستقل', 'objective' => 'تحقق من إشارة مستقلة بالتوازي.'],
        ] as $task) {
            $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/tasks", $task + [
                'permitted_tools' => ['Browser', 'Event Viewer'],
                'required_capabilities' => ['APPLICATION_LOGGING'],
                'expected_signals' => ['HTTP_LOG'],
                'validation_rule' => ['signal' => 'HTTP_LOG', 'operator' => 'EXISTS'],
                'completion_weight' => 1,
                'is_optional' => false,
            ])->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();
        }
        $tasks = DB::table('simulation_lab_task_nodes')->where('lab_definition_id', $labDefinitionId)->pluck('id', 'task_key');
        foreach ([
            ['successor' => 'TRACE', 'type' => 'REQUIRED', 'condition' => null],
            ['successor' => 'VERIFY', 'type' => 'CONDITIONAL', 'condition' => ['when' => 'HTTP_LOG_PRESENT']],
        ] as $edge) {
            $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/task-dependencies", [
                'predecessor_task_id' => $tasks['OBSERVE'],
                'successor_task_id' => $tasks[$edge['successor']],
                'dependency_type' => $edge['type'],
                'condition' => $edge['condition'],
            ])->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();
        }
        $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/validate")
            ->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('simulation_lab_definitions', ['id' => $labDefinitionId, 'status' => 'VALIDATED']);
        $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/publish")
            ->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();

        $this->actingAs($owner)->get('/simulation/labs')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'labs')
            ->has('labs', 2)
            ->where('labs.1.lab_id', $stableLabId)
            ->where('labs.1.environment_binding_mode', 'LAB_LOCAL')
            ->where('labs.1.status', 'PUBLISHED')
            ->where('labs.1.can_prepare', false)
            ->has('labs.1.tasks', 3)
            ->has('labs.1.task_dependencies', 2));

        $this->actingAs($owner)->post("/simulation/labs/{$labDefinitionId}/clone")
            ->assertRedirect(route('cep.simulation.labs'))->assertSessionHasNoErrors();
        $labRevisions = DB::table('simulation_lab_definitions')->where('lab_id', $stableLabId)->orderBy('revision')->get();
        $this->assertCount(2, $labRevisions);
        $this->assertSame([1, 2], $labRevisions->pluck('revision')->map(fn (mixed $value): int => (int) $value)->all());
        $this->assertNotSame((string) $labRevisions[0]->id, (string) $labRevisions[1]->id);
    }

    #[Test]
    public function published_lab_graph_children_are_database_immutable(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $taskId = (string) DB::table('simulation_lab_task_nodes')->value('id');

        $this->expectException(QueryException::class);
        DB::table('simulation_lab_task_nodes')->where('id', $taskId)->update(['objective' => 'tampered']);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'SIMDEF Owner',
            'simdef-owner@example.test',
            'ReviewReady!Pass9',
            (string) Str::uuid7(),
        );
    }
}
