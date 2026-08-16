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
            'AGENTS.md',
            'CONTRIBUTING.md',
            'README.md',
            'SECURITY.md',
            'docs/governance/PARALLEL_EXECUTION_MODEL.md',
            'docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md',
            'docs/development/TESTING_AND_QUALITY_GATES.md',
            'docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md',
            '.github/workflows/core-ci.yml',
        ] as $path) {
            $this->assertFileExists(base_path($path), "Canonical governance source missing: {$path}");
            $this->assertGreaterThan(0, filesize(base_path($path)), "Canonical governance source is empty: {$path}");
        }

        $agents = file_get_contents(base_path('AGENTS.md'));
        $contributing = file_get_contents(base_path('CONTRIBUTING.md'));
        $readme = file_get_contents(base_path('README.md'));
        $security = file_get_contents(base_path('SECURITY.md'));
        $githubGovernance = file_get_contents(base_path('docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md'));
        $testing = file_get_contents(base_path('docs/development/TESTING_AND_QUALITY_GATES.md'));
        $evidence = file_get_contents(base_path('docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md'));

        $this->assertStringContainsString('canonical repository execution governance', $agents);
        $this->assertStringContainsString('This public code repository', $contributing);
        $this->assertStringContainsString('This public GitHub repository', $readme);
        $this->assertStringContainsString('The GitHub repository is public', $githubGovernance);
        $this->assertStringContainsString('This repository is public', $security);

        $visibilityGovernance = implode("\n", [$agents, $contributing, $readme, $security, $githubGovernance]);
        foreach (['This private repository', 'repository remains private', 'Keep repository visibility private'] as $stalePrivateAssumption) {
            $this->assertStringNotContainsString($stalePrivateAssumption, $visibilityGovernance);
        }

        foreach (['Report suspected vulnerabilities privately', 'Do not place credentials', 'Rotate and revoke any value accidentally disclosed'] as $invariant) {
            $this->assertStringContainsString($invariant, $security);
        }
        $this->assertStringContainsString('Every required result propagates truthfully', $testing);
        $this->assertStringContainsString('GitHub-hosted', $evidence);
        $this->assertStringContainsString('Artifact retention is 14 days', $evidence);
    }

    #[Test]
    public function generated_review_archives_remain_excluded_while_canonical_packaging_sources_are_preserved(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));
        $dockerignore = file_get_contents(base_path('.dockerignore'));

        foreach (['/public/build/', '/review-packets/', '/.env.*', '!/.env.example'] as $entry) {
            $this->assertStringContainsString($entry, $gitignore);
        }
        $this->assertStringContainsString('review-packets', $dockerignore);

        foreach ([
            'review-packets/TASK_004_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv',
            'review-packets/TASK_004_REVIEW_HANDOFF/SHA256SUMS.txt',
            'review-packets/TASK_006_REVIEW_HANDOFF/MANIFEST.sha256',
        ] as $obsolete) {
            $this->assertFileDoesNotExist(base_path($obsolete), "Obsolete pre-migration path was recreated: {$obsolete}");
        }

        foreach ([
            'scripts/package_task006_handoff.php',
            'scripts/Support/HandoffPathPolicy.php',
        ] as $canonicalSource) {
            $this->assertFileExists(base_path($canonicalSource), "Canonical packaging source missing: {$canonicalSource}");
            $this->assertGreaterThan(0, filesize(base_path($canonicalSource)), "Canonical packaging source is empty: {$canonicalSource}");
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
        $trackedPaths = array_values(array_filter(array_map(
            static fn (string $path): string => str_replace('\\', '/', trim($path)),
            $trackedOutput,
        )));
        $tracked = implode("\n", $trackedPaths)."\n";

        $forbiddenEnvironmentFiles = array_values(array_filter($trackedPaths, static function (string $path): bool {
            $name = basename($path);

            return ($name === '.env' || str_starts_with($name, '.env.')) && ! str_ends_with($name, '.example');
        }));
        $this->assertSame([], $forbiddenEnvironmentFiles, 'Tracked runtime environment files are forbidden; only *.example templates are allowed.');
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
