<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SystemOperationsCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_pending_processing_run_can_be_cancelled_and_audited_without_automatic_retry(): void
    {
        $owner = $this->owner();
        $run = ProcessingRun::query()->create([
            'type' => 'system.validation.fixture',
            'input_digest' => str_repeat('d', 64),
            'idempotency_key' => 'w05-c01-'.Str::uuid7(),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post('/system/processing/runs/'.$run->id.'/cancel')
            ->assertRedirect();

        $run->refresh();
        $this->assertSame('cancelled', $run->status);
        $this->assertNotNull($run->cancelled_at);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'processing.run.cancelled',
            'target_type' => 'processing_run',
            'target_identifier' => (string) $run->id,
            'actor_identifier' => (string) $owner->getAuthIdentifier(),
            'outcome' => 'success',
        ]);

        $this->actingAs($owner)
            ->post('/system/processing/runs/'.$run->id.'/cancel')
            ->assertSessionHasErrors('processing');
        $this->actingAs($owner)
            ->post('/system/processing/runs/'.$run->id.'/retry')
            ->assertNotFound();
    }

    public function test_processing_surface_declares_bounded_cancellation_without_knowledge_decision_authority(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/processing')->assertInertia(fn (Assert $page) => $page
            ->where('state.policy.cancellation', 'PENDING_OR_RUNNING_ONLY')
            ->where('state.policy.retry_route_available', false)
            ->where('state.policy.knowledge_decisions', false));

        $this->actingAs($owner)
            ->post('/system/processing/knowledge/decide')
            ->assertNotFound();
    }

    public function test_configuration_surface_is_a_non_secret_read_only_whitelist(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/system/configuration')->assertInertia(fn (Assert $page) => $page
            ->where('state.configuration_policy.mode', 'READ_ONLY_WHITELIST')
            ->where('state.configuration_policy.runtime_mutation_available', false)
            ->where('state.configuration_policy.secrets_exposed', false));

        $this->actingAs($owner)
            ->post('/system/configuration')
            ->assertStatus(405);
    }

    public function test_validation_and_release_package_context_is_scoped_to_current_actor(): void
    {
        $owner = $this->owner();
        // We cannot use CreateOwner::class again since an active owner already exists.
        // So we create the foreign actor via the raw insert to bypass the invariant check.
        $foreignOwnerId = (string) Str::uuid7();
        DB::table('owner_accounts')->insert([
            'id' => $foreignOwnerId,
            'display_name' => 'Foreign Owner',
            'email' => 'foreign-owner@example.test',
            'password' => 'password',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actorId = (string) $owner->getAuthIdentifier();
        $foreignActorId = $foreignOwnerId;
        $ownedPackageId = $this->portablePackage($actorId, 'owned-package');
        $foreignPackageId = $this->portablePackage($foreignActorId, 'foreign-package');

        $this->actingAs($owner)->get('/system/validation')->assertInertia(fn (Assert $page) => $page
            ->where('state.packages.records.0.id', $ownedPackageId)
            ->missing('state.packages.records.1'));

        $this->actingAs($owner)->get('/system/releases')->assertInertia(fn (Assert $page) => $page
            ->where('state.packages.0.id', $ownedPackageId)
            ->missing('state.packages.1'));

        $this->assertDatabaseHas('portable_packages', ['id' => $foreignPackageId, 'actor_id' => $foreignActorId]);
    }

    private function portablePackage(string $actorId, string $type): string
    {
        $blobId = (string) Str::uuid7();
        $packageId = (string) Str::uuid7();
        $digest = hash('sha256', $packageId.'|'.$type);
        $scope = ['target' => $type];
        $manifest = [
            'format' => 'cyber-platform-portable-package',
            'package_type' => $type,
            'schema_version' => 1,
            'actor_id' => $actorId,
            'owner_module' => 'MOD-PLT',
            'scope' => $scope,
            'files' => [],
            'package_digest' => $digest,
        ];

        DB::table('blob_objects')->insert([
            'id' => $blobId,
            'storage_key' => 'w05-c01/'.$blobId.'.zip',
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

        return $packageId;
    }

    private function owner()
    {
        return app(CreateOwner::class)->execute(
            'W05 C01 Owner',
            'w05-c01-owner-'.Str::lower(Str::random(8)).'@example.test',
            'VeryStrong!Pass9',
            (string) Str::uuid7(),
        );
    }
}
