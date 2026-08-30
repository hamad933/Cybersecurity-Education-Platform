<?php

namespace Tests\Feature\Modules\Platform\SystemOperations;

use App\Models\User;
use App\Modules\Platform\SystemOperations\SystemOperationsState;
use App\Modules\Platform\Release\ReleaseReadiness;
use App\Modules\Platform\Audit\AuditChainVerifier;
use App\Modules\Platform\Health\FoundationHealth;
use App\Modules\Platform\SystemOperations\Contracts\ManualAiStateProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemOperationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_update_local_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/system/configuration/settings', [
            'language' => 'ar',
            'direction' => 'rtl',
            'appearance' => 'dark',
            'behavior' => ['compact_mode' => true]
        ]);

        $response->assertSessionHas('success', 'Local configuration settings updated.');
        $response->assertSessionHas('local_settings.language', 'ar');
        $response->assertSessionHas('local_settings.direction', 'rtl');
        $response->assertSessionHas('local_settings.appearance', 'dark');
        $response->assertSessionHas('local_settings.behavior.compact_mode', true);
    }
    
    public function test_it_rejects_invalid_local_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/system/configuration/settings', [
            'language' => 'fr', // invalid
            'direction' => 'up', // invalid
            'appearance' => 'purple', // invalid
        ]);

        $response->assertSessionHasErrors(['language', 'direction', 'appearance']);
    }

    public function test_it_loads_health_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system');
        $response->assertOk();
    }
    
    public function test_it_loads_processing_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system/processing');
        $response->assertOk();
    }
    
    public function test_it_loads_validation_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system/validation');
        $response->assertOk();
    }
    
    public function test_it_loads_audit_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system/audit');
        $response->assertOk();
    }
    
    public function test_it_loads_releases_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system/releases');
        $response->assertOk();
    }
    
    public function test_it_loads_configuration_surface(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/system/configuration');
        $response->assertOk();
    }
    
    public function test_state_provides_truthful_validation_observation_status(): void
    {
        $state = app(SystemOperationsState::class);
        $result = $state->forSurface('validation', 'actor-1');
        
        $this->assertTrue($result['observation_status']['unavailable_truth']);
        $this->assertTrue($result['observation_status']['error_truth']);
        $this->assertTrue($result['observation_status']['observed_empty_zero_truth']);
        
        $this->assertTrue($result['scope']['technical_validation_only']);
        $this->assertFalse($result['scope']['knowledge_quality_decisions']);
        $this->assertFalse($result['scope']['canonical_knowledge_decisions']);
    }
    
    public function test_state_provides_truthful_release_mapping(): void
    {
        // We mock ReleaseReadiness to return true for readiness to test the technical verification mapping
        $mockReadiness = $this->createMock(ReleaseReadiness::class);
        $mockReadiness->method('evaluate')->willReturn(['ready' => true, 'checks' => []]);
        $this->app->instance(ReleaseReadiness::class, $mockReadiness);
        
        $state = app(SystemOperationsState::class);
        $result = $state->forSurface('releases', 'actor-1');
        
        $this->assertEquals('VERIFIED_TECHNICALLY', $result['technical_verification_status']);
        $this->assertEquals('OWNER_PENDING', $result['owner_acceptance_status']);
        $this->assertFalse($result['authorization']['deployment_authorized']);
    }
    
    public function test_state_provides_truthful_release_mapping_when_not_ready(): void
    {
        $mockReadiness = $this->createMock(ReleaseReadiness::class);
        $mockReadiness->method('evaluate')->willReturn(['ready' => false, 'checks' => []]);
        $this->app->instance(ReleaseReadiness::class, $mockReadiness);
        
        $state = app(SystemOperationsState::class);
        $result = $state->forSurface('releases', 'actor-1');
        
        $this->assertEquals('PENDING_TECHNICAL_VERIFICATION', $result['technical_verification_status']);
        $this->assertEquals('OWNER_PENDING', $result['owner_acceptance_status']);
        $this->assertFalse($result['authorization']['deployment_authorized']);
    }
    
    public function test_configuration_state_includes_local_settings(): void
    {
        session(['local_settings' => [
            'language' => 'en',
            'direction' => 'ltr',
            'appearance' => 'dark',
            'behavior' => ['compact_mode' => true]
        ]]);
        
        $state = app(SystemOperationsState::class);
        $result = $state->forSurface('configuration', 'actor-1');
        
        $this->assertArrayHasKey('local_settings', $result);
        $this->assertEquals('en', $result['local_settings']['effective']['language']);
        $this->assertEquals('ltr', $result['local_settings']['effective']['direction']);
        $this->assertEquals('dark', $result['local_settings']['effective']['appearance']);
        $this->assertTrue($result['local_settings']['effective']['behavior']['compact_mode']);
        $this->assertEquals('SAVED', $result['local_settings']['status']);
        
        // Assert platform config is strictly read-only
        $this->assertEquals('READ_ONLY_WHITELIST', $result['configuration_policy']['mode']);
        $this->assertFalse($result['configuration_policy']['runtime_mutation_available']);
        $this->assertFalse($result['configuration_policy']['secrets_exposed']);
    }
}
