<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Audit\AuditRecord;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_health_and_processing_surfaces_distinguish_requested_input_from_actual_state(): void
    {
        $owner = $this->owner();
        $inputDigest = str_repeat('a', 64);
        $run = ProcessingRun::query()->create([
            'type' => 'fixture.validation',
            'input_digest' => $inputDigest,
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
            ->where('state.processing.runs.0.input_digest', $inputDigest)
            ->where('state.processing.runs.0.status', 'failed')
            ->where('state.processing.runs.0.attempt_count', 1)
            ->where('state.processing.runs.0.safe_error_message', 'Persisted W05 diagnostic fixture.'));
    }

    public function test_validation_is_technical_only_and_exposes_recorded_package_context(): void
    {
        $owner = $this->owner();
        $package = $this->portablePackage((string) $owner->getAuthIdentifier(), 'source-review', [
            'target' => 'knowledge-unit:KU-VAL-01',
            'handling' => 'technical-validation',
        ]);

        $this->actingAs($owner)->get('/system/validation')->assertInertia(fn (Assert $page) => $page
            ->where('state.scope.technical_validation_only', true)
            ->where('state.scope.knowledge_quality_decisions', false)
            ->where('state.scope.canonical_knowledge_decisions', false)
            ->where('state.packages.records.0.id', $package['id'])
            ->where('state.packages.records.0.scope.target', 'knowledge-unit:KU-VAL-01')
            ->where('state.packages.records.0.scope.handling', 'technical-validation')
            ->where('state.packages.records.0.manifest.package_digest', $package['digest']));

        $this->actingAs($owner)->post('/system/validation/knowledge/decide')->assertNotFound();
    }

    public function test_manual_ai_bridge_exposes_complete_result_and_authoritative_provenance_before_decision(): void
    {
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();
        $promptId = (string) Str::uuid7();
        $revisionId = (string) Str::uuid7();
        $resultId = (string) Str::uuid7();
        $inputDigest = str_repeat('b', 64);
        $resultDigest = str_repeat('c', 64);
        $declaredScope = [
            'prompt_package_id' => $promptId,
            'prompt_revision' => 1,
            'purpose' => 'Review KU-42',
            'scope' => ['knowledge_unit_id' => 'KU-42'],
            'manual_execution_only' => true,
            'automatic_network_provider' => false,
        ];
        $structuredResult = [
            'knowledge_unit_id' => 'KU-42',
            'proposed_blocks' => [
                ['type' => 'paragraph', 'body' => 'Material operator-review content.'],
            ],
            'citation_claim_ids' => ['claim-42'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => 'baseline-42',
            'limitations' => ['Manual review required.'],
            'confidence' => 'bounded',
        ];
        $promptPackage = $this->portablePackage($actorId, 'manual-ai-prompt', $declaredScope);
        $returnedScope = [
            'prompt_package_id' => $promptId,
            'prompt_revision' => 1,
            'input_digest' => $inputDigest,
        ];
        $returnedPackage = $this->portablePackage($actorId, 'manual-ai-result', $returnedScope);

        DB::table('prompt_packages')->insert([
            'id' => $promptId,
            'actor_id' => $actorId,
            'purpose' => 'Review KU-42',
            'status' => 'result_imported',
            'current_revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('prompt_package_revisions')->insert([
            'id' => $revisionId,
            'prompt_package_id' => $promptId,
            'revision' => 1,
            'portable_package_id' => $promptPackage['id'],
            'input_digest' => $inputDigest,
            'declared_scope' => json_encode($declaredScope, JSON_THROW_ON_ERROR),
            'exported_at' => now(),
        ]);
        DB::table('imported_ai_results')->insert([
            'id' => $resultId,
            'actor_id' => $actorId,
            'prompt_package_revision_id' => $revisionId,
            'portable_package_id' => $returnedPackage['id'],
            'result_digest' => $resultDigest,
            'structured_result' => json_encode($structuredResult, JSON_THROW_ON_ERROR),
            'status' => 'pending_review',
            'imported_at' => now(),
        ]);

        $this->actingAs($owner)->get('/system/ai-bridge')->assertInertia(fn (Assert $page) => $page
            ->where('state.policy.execution', 'MANUAL_ONLY')
            ->where('state.policy.automatic_provider_enabled', false)
            ->where('state.policy.automatic_publish', false)
            ->where('state.policy.polling', false)
            ->where('state.policy.embeddings', false)
            ->where('state.prompt_revisions.0.id', $revisionId)
            ->where('state.prompt_revisions.0.input_digest', $inputDigest)
            ->where('state.prompt_revisions.0.portable_package_id', $promptPackage['id'])
            ->where('state.prompt_revisions.0.declared_scope.scope.knowledge_unit_id', 'KU-42')
            ->where('state.results.0.id', $resultId)
            ->where('state.results.0.result_digest', $resultDigest)
            ->where('state.results.0.prompt_input_digest', $inputDigest)
            ->where('state.results.0.prompt_package_revision_id', $revisionId)
            ->where('state.results.0.portable_package_id', $returnedPackage['id'])
            ->where('state.results.0.structured_result.knowledge_unit_id', 'KU-42')
            ->where('state.results.0.structured_result.proposed_blocks.0.body', 'Material operator-review content.')
            ->where('state.results.0.returned_package_scope.input_digest', $inputDigest)
            ->where('state.results.0.status', 'pending_review'));

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

    public function test_audit_records_expose_investigation_context_and_remain_append_only(): void
    {
        $owner = $this->owner();
        $correlationId = (string) Str::uuid7();
        app(AuditWriter::class)->append([
            'actor_identifier' => (string) $owner->getAuthIdentifier(),
            'action' => 'w05.fixture.checked',
            'target_type' => 'system_operations',
            'target_identifier' => 'fixture-target-42',
            'correlation_id' => $correlationId,
            'outcome' => 'success',
            'safe_metadata' => ['fixture' => true, 'source' => 'w05-r02'],
        ]);
        $record = AuditRecord::query()->firstOrFail();

        $this->actingAs($owner)->get('/system/audit')->assertInertia(fn (Assert $page) => $page
            ->where('state.records.0.actor_identifier', (string) $owner->getAuthIdentifier())
            ->where('state.records.0.target_identifier', 'fixture-target-42')
            ->where('state.records.0.correlation_id', $correlationId)
            ->where('state.records.0.safe_metadata.fixture', true)
            ->where('state.records.0.safe_metadata.source', 'w05-r02')
            ->where('state.records.0.record_hash', $record->record_hash));

        try {
            $record->delete();
            $this->fail('Audit record deletion must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Audit records are append-only.', $exception->getMessage());
        }

        $this->actingAs($owner)->delete('/system/audit/'.$record->id)->assertNotFound();
    }

    public function test_release_surface_exposes_package_follow_up_context_without_deployment_authority(): void
    {
        $owner = $this->owner();
        $package = $this->portablePackage((string) $owner->getAuthIdentifier(), 'release-evidence', [
            'target' => 'release-candidate:W05',
            'handling' => 'package-and-release-validation-only',
        ]);

        $this->actingAs($owner)->get('/system/releases')->assertInertia(fn (Assert $page) => $page
            ->where('state.authorization.deployment_authorized', false)
            ->where('state.authorization.deployment_workflow_available', false)
            ->where('state.authorization.scope', 'PACKAGE_AND_RELEASE_VALIDATION_ONLY')
            ->where('state.packages.0.id', $package['id'])
            ->where('state.packages.0.scope.target', 'release-candidate:W05')
            ->where('state.packages.0.scope.handling', 'package-and-release-validation-only')
            ->where('state.packages.0.manifest.package_digest', $package['digest']));

        $this->actingAs($owner)->post('/system/releases/deploy')->assertNotFound();
    }

    /** @return array{id: string, digest: string} */
    private function portablePackage(string $actorId, string $type, array $scope): array
    {
        $blobId = (string) Str::uuid7();
        $packageId = (string) Str::uuid7();
        $digest = hash('sha256', $packageId.'|'.$type);
        $manifest = [
            'format' => 'cyber-platform-portable-package',
            'package_type' => $type,
            'schema_version' => 1,
            'actor_id' => $actorId,
            'owner_module' => 'MOD-PLT',
            'scope' => $scope,
            'files' => [
                ['path' => 'fixture.json', 'bytes' => 2, 'sha256' => hash('sha256', '{}')],
            ],
            'package_digest' => $digest,
        ];

        DB::table('blob_objects')->insert([
            'id' => $blobId,
            'storage_key' => 'w05-r02/'.$blobId.'.zip',
            'size_bytes' => 2,
            'sha256' => hash('sha256', $blobId),
            'media_type' => 'application/zip',
            'status' => 'ready',
            'created_at' => now(),
            'owner_module' => 'MOD-PLT',
            'purpose' => $type,
            'owner_identifier' => $actorId,
        ]);
        DB::table('portable_packages')->insert([
            'id' => $packageId,
            'package_type' => $type,
            'schema_version' => 1,
            'owner_module' => 'MOD-PLT',
            'actor_id' => $actorId,
            'scope' => json_encode($scope, JSON_THROW_ON_ERROR),
            'manifest' => json_encode($manifest, JSON_THROW_ON_ERROR),
            'package_digest' => $digest,
            'blob_object_id' => $blobId,
            'status' => 'exported',
            'created_at' => now(),
        ]);

        return ['id' => $packageId, 'digest' => $digest];
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
