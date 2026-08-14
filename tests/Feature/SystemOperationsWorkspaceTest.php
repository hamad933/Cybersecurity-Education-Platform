<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Audit\AuditRecord;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

final class SystemOperationsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_system_route_family_is_authenticated_and_uses_w05_workspace(): void
    {
        $surfaces = [
            '/system' => 'health',
            '/system/processing' => 'processing',
            '/system/validation' => 'validation',
            '/system/ai-bridge' => 'ai-bridge',
            '/system/backups' => 'backups',
            '/system/audit' => 'audit',
            '/system/releases' => 'releases',
            '/system/configuration' => 'configuration',
        ];

        foreach (array_keys($surfaces) as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $owner = $this->owner();
        foreach ($surfaces as $path => $surface) {
            $this->actingAs($owner)->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('SystemOperations/Workspace')
                ->where('surface', $surface));
        }
    }

    public function test_health_and_processing_surfaces_read_persisted_platform_state(): void
    {
        $owner = $this->owner();
        $run = ProcessingRun::query()->create([
            'type' => 'fixture.validation',
            'input_digest' => str_repeat('a', 64),
            'idempotency_key' => 'w05-fixture-'.Str::uuid7(),
            'status' => 'failed',
            'attempt_count' => 1,
            'error_category' => 'fixture',
            'safe_error_message' => 'Persisted W05 diagnostic fixture.',
        ]);

        $this->actingAs($owner)->get('/system')->assertInertia(fn (Assert $page) => $page
            ->where('state.processing.counts.failed', 1)
            ->where('state.outbox.counts', []));

        $this->actingAs($owner)->get('/system/processing')->assertInertia(fn (Assert $page) => $page
            ->where('state.processing.counts.failed', 1)
            ->where('state.processing.runs.0.id', (string) $run->id)
            ->where('state.processing.runs.0.status', 'failed')
            ->where('state.processing.runs.0.safe_error_message', 'Persisted W05 diagnostic fixture.'));
    }

    public function test_validation_is_technical_only_and_does_not_expose_knowledge_quality_decisions(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/validation')->assertInertia(fn (Assert $page) => $page
            ->where('state.scope.technical_validation_only', true)
            ->where('state.scope.knowledge_quality_decisions', false)
            ->where('state.scope.canonical_knowledge_decisions', false));

        $this->actingAs($owner)->post('/system/validation/knowledge/decide')->assertNotFound();
    }

    public function test_manual_ai_bridge_remains_manual_only_without_provider_execution_route(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/ai-bridge')->assertInertia(fn (Assert $page) => $page
            ->where('state.policy.execution', 'MANUAL_ONLY')
            ->where('state.policy.automatic_provider_enabled', false)
            ->where('state.policy.automatic_publish', false)
            ->where('state.policy.polling', false)
            ->where('state.policy.embeddings', false));

        $this->actingAs($owner)->post('/system/ai-bridge/providers/run')->assertNotFound();
    }

    public function test_restore_is_stage_only_and_no_destructive_activation_route_is_exposed(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/backups')->assertInertia(fn (Assert $page) => $page
            ->where('state.safety.web_restore_mode', 'STAGE_AND_VERIFY_ONLY')
            ->where('state.safety.activation_route_available', false));

        $this->actingAs($owner)->post('/system/backups/restores/activate')->assertNotFound();
    }

    public function test_audit_records_remain_append_only_and_have_no_destructive_w05_route(): void
    {
        $owner = $this->owner();
        app(AuditWriter::class)->append([
            'actor_identifier' => (string) $owner->getAuthIdentifier(),
            'action' => 'w05.fixture.checked',
            'target_type' => 'system_operations',
            'target_identifier' => null,
            'correlation_id' => (string) Str::uuid7(),
            'outcome' => 'success',
            'safe_metadata' => ['fixture' => true],
        ]);
        $record = AuditRecord::query()->firstOrFail();

        try {
            $record->delete();
            $this->fail('Audit record deletion must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Audit records are append-only.', $exception->getMessage());
        }

        $this->actingAs($owner)->delete('/system/audit/'.$record->id)->assertNotFound();
    }

    public function test_release_surface_never_claims_or_exposes_production_deployment(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/releases')->assertInertia(fn (Assert $page) => $page
            ->where('state.authorization.deployment_authorized', false)
            ->where('state.authorization.deployment_workflow_available', false)
            ->where('state.authorization.scope', 'PACKAGE_AND_RELEASE_VALIDATION_ONLY'));

        $this->actingAs($owner)->post('/system/releases/deploy')->assertNotFound();
    }

    private function owner()
    {
        return app(CreateOwner::class)->execute(
            'W05 Owner',
            'w05-owner-'.Str::lower(Str::random(8)).'@example.test',
            'VeryStrong!Pass9',
            (string) Str::uuid7(),
        );
    }
}
