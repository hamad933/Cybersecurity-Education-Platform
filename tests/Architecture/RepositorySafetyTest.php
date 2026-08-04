<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositorySafetyTest extends TestCase
{
    #[Test]
    public function canonical_governance_and_validation_documents_define_current_evidence_boundaries(): void
    {
        foreach ([
            'README.md',
            'SECURITY.md',
            'docs/development/TESTING_AND_QUALITY_GATES.md',
            'docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md',
            '.github/workflows/core-ci.yml',
        ] as $path) {
            $this->assertFileExists(base_path($path), "Canonical governance source missing: {$path}");
            $this->assertGreaterThan(0, filesize(base_path($path)), "Canonical governance source is empty: {$path}");
        }

        $security = file_get_contents(base_path('SECURITY.md'));
        $testing = file_get_contents(base_path('docs/development/TESTING_AND_QUALITY_GATES.md'));
        $evidence = file_get_contents(base_path('docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md'));

        $this->assertStringContainsString('Do not commit production credentials', $security);
        $this->assertStringContainsString('Every required result propagates truthfully', $testing);
        $this->assertStringContainsString('GitHub-hosted', $evidence);
        $this->assertStringContainsString('artifacts/ci-core/', $evidence);
    }

    #[Test]
    public function obsolete_review_archives_are_not_required_and_remain_excluded_from_canonical_source(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));
        $dockerignore = file_get_contents(base_path('.dockerignore'));

        foreach (['/artifacts', '/public/build', 'review-packets/**/*.zip'] as $entry) {
            $this->assertStringContainsString($entry, $gitignore);
        }
        $this->assertStringContainsString('review-packets', $dockerignore);

        foreach ([
            'review-packets/TASK_004_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv',
            'review-packets/TASK_004_REVIEW_HANDOFF/SHA256SUMS.txt',
            'review-packets/TASK_006_REVIEW_HANDOFF/MANIFEST.sha256',
            'scripts/package_task006_handoff.php',
        ] as $obsolete) {
            $this->assertFileDoesNotExist(base_path($obsolete), "Obsolete pre-migration path was recreated: {$obsolete}");
        }
    }

    #[Test]
    public function repository_root_lockfiles_and_tracked_secret_exclusions_match_the_clean_baseline(): void
    {
        $safe = str_replace('\\', '/', base_path());
        $git = 'git -c safe.directory='.escapeshellarg($safe);

        $rootOutput = [];
        $rootCode = 0;
        exec($git.' rev-parse --show-toplevel 2>&1', $rootOutput, $rootCode);
        $this->assertSame(0, $rootCode, 'Git metadata must be available to repository-safety tests.');
        $root = trim(implode("\n", $rootOutput));
        $this->assertSame(strtolower($safe), strtolower(str_replace('\\', '/', $root)));

        $this->assertFileExists(base_path('composer.lock'));
        $this->assertFileExists(base_path('package-lock.json'));
        $this->assertFileExists(base_path('artisan'));

        $trackedOutput = [];
        $trackedCode = 0;
        exec($git.' ls-files 2>&1', $trackedOutput, $trackedCode);
        $this->assertSame(0, $trackedCode, 'Tracked-file inventory must be available to repository-safety tests.');
        $tracked = implode("\n", $trackedOutput)."\n";

        $this->assertDoesNotMatchRegularExpression('/(^|\/)\.env(?:\.[^\/]*)?$/m', $tracked);
        $this->assertDoesNotMatchRegularExpression('/(^|\/)(vendor|node_modules|public\/build|artifacts)\//m', $tracked);
        $this->assertDoesNotMatchRegularExpression('/(^|\/)review-packets\/.*\.zip$/mi', $tracked);
    }

    #[Test]
    public function runtime_application_has_no_source_vault_dependency_or_prohibited_services(): void
    {
        $files = array_merge($this->filesUnder(app_path()), $this->filesUnder(config_path()), $this->filesUnder(base_path('routes')));
        $runtime = collect($files)->map(fn ($file) => file_get_contents($file))->join("\n");
        $this->assertStringNotContainsString('source-vault', $runtime);
        foreach (['Kafka', 'GraphQL', 'Kubernetes', 'OpenAI', 'AIInteractionPort'] as $prohibited) {
            $this->assertStringNotContainsString($prohibited, $runtime);
        }
    }

    private function filesUnder(string $root): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        return array_values(array_filter(array_map(fn ($file) => $file->getPathname(), iterator_to_array($iterator)), fn ($file) => str_ends_with($file, '.php')));
    }
}
