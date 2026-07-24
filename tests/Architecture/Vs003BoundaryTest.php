<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Vs003BoundaryTest extends TestCase
{
    #[Test]
    public function vs003_has_no_live_execution_or_production_connector_path(): void
    {
        $files = [
            app_path('Application/Vs003/Vs003Lifecycle.php'),
            app_path('Http/Controllers/Vs003Controller.php'),
            app_path('Modules/Simulator/Application/Vs003SimulationService.php'),
            app_path('Modules/Evidence/Application/Vs003EvidenceService.php'),
            app_path('Modules/Learning/Application/Vs003LearningService.php'),
        ];
        $source = collect($files)->map(fn (string $file): string => file_get_contents($file))->join("\n");

        foreach ([
            'Process::',
            'Http::',
            'shell_exec(',
            'exec(',
            'proc_open(',
            'WinRM',
            'SSH',
            'PowerShell',
            'OpenAI',
            'AIInteractionPort',
        ] as $prohibited) {
            $this->assertStringNotContainsString($prohibited, $source);
        }
        $this->assertStringContainsString("'live_action' => false", $source);
        $this->assertStringContainsString("'origin' => 'SIMULATED'", $source);
    }

    #[Test]
    public function mastery_endpoint_reads_server_records_and_accepts_no_replay_payload(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Vs003Controller.php'));
        $lifecycle = file_get_contents(app_path('Application/Vs003/Vs003Lifecycle.php'));
        $learning = file_get_contents(app_path('Modules/Learning/Application/Vs003LearningService.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/function mastery\([^)]*array|function mastery\([^)]*replay/i',
            $controller,
        );
        $this->assertStringContainsString('$this->evidence->masteryFacts($actorId)', $lifecycle);
        $this->assertStringContainsString('$this->simulation->masteryFacts($actorId)', $lifecycle);
        $this->assertStringContainsString("->where('actor_id', \$actorId)", $learning);
        $this->assertStringNotContainsString("\$request->input('verification", $controller);
    }

    #[Test]
    public function vs003_routes_are_authenticated_throttled_and_uuid_bound(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("Route::middleware('auth')->group", $routes);
        $this->assertStringContainsString("Route::prefix('vs003')->name('vs003.')", $routes);
        foreach ([
            "Route::post('/lab/run', [Vs003Controller::class, 'run'])->middleware('throttle:30,1')",
            "Route::post('/triage', [Vs003Controller::class, 'triage'])->middleware('throttle:20,1')",
            "Route::post('/evidence/preserve', [Vs003Controller::class, 'preserve'])->middleware('throttle:20,1')",
            "Route::post('/containment/propose', [Vs003Controller::class, 'proposeContainment'])->middleware('throttle:10,1')",
            "Route::post('/containment/{proposal}/approve', [Vs003Controller::class, 'approveContainment'])->middleware('throttle:10,1')->whereUuid('proposal')",
            "Route::post('/containment/{proposal}/verify', [Vs003Controller::class, 'verifyContainment'])->middleware('throttle:10,1')->whereUuid('proposal')",
            "Route::post('/practice', [Vs003Controller::class, 'practice'])->middleware('throttle:20,1')",
            "Route::post('/mastery/evaluate', [Vs003Controller::class, 'mastery'])->middleware('throttle:10,1')",
        ] as $boundedRoute) {
            $this->assertStringContainsString($boundedRoute, $routes);
        }
    }

    #[Test]
    public function ui_uses_safe_rendering_bidi_isolation_and_accessible_bounded_layouts(): void
    {
        $page = file_get_contents(resource_path('js/pages/Vs003/AuthenticationInvestigation.vue'));

        $this->assertStringContainsString('dir="rtl"', $page);
        $this->assertStringContainsString('dir="ltr"', $page);
        $this->assertStringContainsString('focus-ring', $page);
        $this->assertStringContainsString('max-h-80 overflow-auto', $page);
        $this->assertStringContainsString('SIMULATED', $page);
        $this->assertStringNotContainsString('v-html', $page);
        $this->assertDoesNotMatchRegularExpression('/innerHTML|outerHTML|document\.write/', $page);
        $this->assertStringNotContainsString('<img', $page);
    }

    #[Test]
    public function hardening_migration_declares_actor_links_and_append_only_final_records(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_07_24_000011_harden_vs003_actor_replay_and_control_scope.php'));

        foreach (['triage_record_id', 'proposal_id', 'actor_id'] as $column) {
            $this->assertStringContainsString($column, $migration);
        }
        foreach ([
            'vs003_dataset_immutable',
            'vs003_triage_immutable',
            'vs003_custody_immutable',
            'vs003_control_immutable',
            'vs003_replay_immutable',
        ] as $trigger) {
            $this->assertStringContainsString($trigger, $migration);
        }
        $this->assertStringContainsString('restrictOnDelete()', $migration);
        $this->assertStringContainsString('vs003_approved_actor_check', $migration);
    }
}
