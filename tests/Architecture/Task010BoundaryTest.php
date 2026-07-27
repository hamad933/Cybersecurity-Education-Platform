<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Task010BoundaryTest extends TestCase
{
    #[Test]
    public function manual_ai_bridge_has_no_network_provider_or_automatic_publication_path(): void
    {
        $source = collect($this->phpFiles(app_path('Modules/ManualAiBridge')))->map(fn (string $file): string => file_get_contents($file))->join("\n");
        foreach (['OpenAI', 'Anthropic', 'Http::', 'curl_', 'Guzzle', 'autoPublish', 'publish('] as $prohibited) {
            $this->assertStringNotContainsString($prohibited, $source);
        }
        $this->assertFalse(config('platform.ai_network_provider_enabled'));
        $this->assertStringContainsString('ACCEPT_AS_DRAFT', $source);
    }

    #[Test]
    public function release_compose_is_loopback_only_and_app_and_queue_share_one_image(): void
    {
        $compose = file_get_contents(base_path('compose.release.yaml'));
        $this->assertStringContainsString('127.0.0.1:${APP_PORT:-8081}:8080', $compose);
        $this->assertSame(2, substr_count($compose, 'image: ${RELEASE_IMAGE:-cybersecurity-education-platform:v1}'));
        $this->assertStringContainsString('no-new-privileges:true', $compose);
        $this->assertStringContainsString('--max-time=3600', $compose);
        $this->assertMatchesRegularExpression('/\n  queue:\n.*?\n    healthcheck:\n      disable: true(?:\n|$)/s', str_replace("\r\n", "\n", $compose));
        $this->assertStringNotContainsString('5432:5432', $compose);

        $queueSmoke = file_get_contents(app_path('Modules/Platform/Console/QueueSmokeCommand.php'));
        $provider = file_get_contents(app_path('Modules/Platform/Providers/PlatformServiceProvider.php'));
        $this->assertStringContainsString('platform:queue-smoke', $queueSmoke);
        $this->assertStringContainsString('FoundationSmokeJob::dispatch', $queueSmoke);
        $this->assertStringContainsString('QueueSmokeCommand::class', $provider);
    }

    #[Test]
    public function restore_activation_is_cli_only_and_isolated_database_guarded(): void
    {
        $backup = file_get_contents(app_path('Modules/Platform/Backup/BackupService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ReleaseController.php'));
        $this->assertStringContainsString("str_ends_with(\$databaseName, '_restore_drill')", $backup);
        $this->assertStringContainsString('stageRestore', $controller);
        $this->assertStringNotContainsString('applyToIsolatedDatabase(', $controller);
    }

    #[Test]
    public function release_ui_is_rtl_safe_accessible_and_avoids_active_html_rendering(): void
    {
        $page = file_get_contents(resource_path('js/pages/Release/Center.vue'));
        $this->assertStringContainsString('dir="ltr"', $page);
        $this->assertStringContainsString('focus-ring', $page);
        $this->assertStringContainsString('aria-labelledby', $page);
        $this->assertStringNotContainsString('v-html', $page);
        $this->assertStringNotContainsString('innerHTML', $page);
        $this->assertStringContainsString('MANUAL_ONLY', $page);
    }

    /** @return list<string> */
    private function phpFiles(string $root): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        return array_values(array_filter(array_map(fn ($file) => $file->getPathname(), iterator_to_array($iterator)), fn (string $file): bool => str_ends_with($file, '.php')));
    }
}
