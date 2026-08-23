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

    public function test_docker_context_and_current_handoff_policy_exclude_runtime_residuals(): void
    {
        $ignore = file_get_contents(base_path('.dockerignore'));
        foreach (['vendor', 'node_modules', 'public/build', 'bootstrap/cache/*', 'storage/framework/sessions/*', 'storage/logs/*', 'review-packets'] as $entry) {
            $this->assertStringContainsString($entry, $ignore);
        }

        $policy = file_get_contents(base_path('scripts/Support/HandoffPathPolicy.php'));
        foreach (['public/build', 'bootstrap/cache', 'storage/framework/(?:cache|sessions|testing|views)', 'browser-profiles?', 'database-volumes?', '.env.example', 'REVIEW_HANDOFF'] as $entry) {
            $this->assertStringContainsString($entry, $policy);
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

    public function test_current_canonical_validation_contract_uses_live_repository_paths(): void
    {
        foreach ([
            'README.md',
            'SECURITY.md',
            'composer.lock',
            'package-lock.json',
            '.github/workflows/core-ci.yml',
            'docs/development/TESTING_AND_QUALITY_GATES.md',
            'docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md',
        ] as $path) {
            $this->assertFileExists(base_path($path), "Current canonical file missing: {$path}");
        }

        $testing = file_get_contents(base_path('docs/development/TESTING_AND_QUALITY_GATES.md'));
        $evidence = file_get_contents(base_path('docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md'));
        $workflow = file_get_contents(base_path('.github/workflows/core-ci.yml'));

        $this->assertStringContainsString('Core CI / PHP quality and tests', $testing);
        $this->assertStringContainsString('Core CI / Repository secret scan', $testing);
        foreach (['Artifact retention is 14 days', 'ARTIFACT_MANIFEST.json', 'SHA256SUMS.txt', 'commit SHA', 'run ID'] as $invariant) {
            $this->assertStringContainsString($invariant, $evidence);
        }
        foreach (['Upload CI-only frontend assets', 'Upload PHP evidence', 'retention-days: 14'] as $invariant) {
            $this->assertStringContainsString($invariant, $workflow);
        }

        $this->assertFileDoesNotExist(base_path('review-packets/TASK_004_REVIEW_HANDOFF/SHA256SUMS.txt'));
    }
}
