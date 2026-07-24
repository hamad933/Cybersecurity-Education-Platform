<?php

namespace Tests\Architecture;

use Tests\TestCase;

class FoundationCorrectionsTest extends TestCase
{
    public function test_docker_build_orders_source_before_composer_scripts_and_installs_platform_extensions(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $sourceCopy = strpos($dockerfile, 'COPY . .');
        $composerInstall = strpos($dockerfile, 'composer install');

        $this->assertIsInt($sourceCopy);
        $this->assertIsInt($composerInstall);
        $this->assertTrue($sourceCopy < $composerInstall);
        $this->assertStringNotContainsString('--no-scripts', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-install intl mbstring pdo_pgsql zip', $dockerfile);
        $this->assertStringContainsString('composer check-platform-reqs --no-dev', $dockerfile);
    }

    public function test_docker_context_and_handoff_packager_exclude_runtime_residuals(): void
    {
        $ignore = file_get_contents(base_path('.dockerignore'));
        foreach (['vendor', 'node_modules', 'public/build', 'bootstrap/cache/*', 'storage/framework/sessions/*', 'storage/logs/*', 'review-packets'] as $entry) {
            $this->assertStringContainsString($entry, $ignore);
        }

        $packager = file_get_contents(base_path('scripts/package_task006_handoff.php'))
            .file_get_contents(base_path('scripts/Support/HandoffPathPolicy.php'));
        foreach (['public/build', 'bootstrap/cache', 'storage/framework/(?:cache|sessions|testing|views)', 'browser-profiles?', 'database-volumes?'] as $entry) {
            $this->assertStringContainsString($entry, $packager);
        }
    }

    public function test_persistent_login_is_absent_from_request_and_interface(): void
    {
        $controller = file_get_contents(app_path('Modules/IdentityAccess/Http/Controllers/AuthenticatedSessionController.php'));
        $login = file_get_contents(resource_path('js/pages/Auth/Login.vue'));

        $this->assertStringNotContainsString('remember', strtolower($controller));
        $this->assertStringNotContainsString('remember', strtolower($login));
        $this->assertStringNotContainsString('input[type="checkbox"]', $login);
    }

    public function test_task004_manifest_hash_uses_the_recomputed_value(): void
    {
        $path = base_path('review-packets/TASK_004_REVIEW_HANDOFF/SHA256SUMS.txt');

        $this->assertSame(
            '896E800B2810EBB789E875B3A227C0B402DBB12B2218D2EA8DCA386E41925108',
            strtoupper(hash_file('sha256', $path)),
        );
    }
}
