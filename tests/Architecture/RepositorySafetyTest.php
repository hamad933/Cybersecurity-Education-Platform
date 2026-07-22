<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositorySafetyTest extends TestCase
{
    #[Test]
    public function approved_task004_artifacts_remain_byte_for_byte_preserved(): void
    {
        $manifest = base_path('review-packets/TASK_004_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv');
        $rows = array_map('str_getcsv', file($manifest, FILE_IGNORE_NEW_LINES), array_fill(0, count(file($manifest, FILE_IGNORE_NEW_LINES)), "\t"));
        array_shift($rows);
        $checked = 0;
        foreach ($rows as $row) {
            [$source, , $size, $sha] = $row;
            if ($source === 'AGENTS.md' || ! str_starts_with($source, 'product-repo/')) {
                continue;
            }
            $path = base_path(substr($source, strlen('product-repo/')));
            $this->assertFileExists($path, "Prior file missing: {$source}");
            $this->assertSame((int) $size, filesize($path), "Prior file size changed: {$source}");
            $this->assertSame(strtolower($sha), hash_file('sha256', $path), "Prior file hash changed: {$source}");
            $checked++;
        }
        $this->assertGreaterThan(50, $checked);
    }

    #[Test]
    public function root_agents_stable_patch_is_present_without_removing_original_rules(): void
    {
        $rules = file_get_contents(base_path('../AGENTS.md'));
        foreach (['source-vault/originals/', 'Manual AI Bridge', 'single deployable Modular Monolith', 'local single-owner workspace', 'PostgreSQL relational storage', 'design proofs, never product implementation', 'Task 004 is an architecture and UX candidate only'] as $required) {
            $this->assertStringContainsString($required, $rules);
        }
    }

    #[Test]
    public function repository_root_lockfiles_and_secret_exclusions_are_correct(): void
    {
        $safe = str_replace('\\', '/', base_path());
        $root = trim((string) shell_exec('git -c safe.directory="'.$safe.'" rev-parse --show-toplevel'));
        $this->assertSame(strtolower($safe), strtolower(str_replace('\\', '/', $root)));
        $this->assertFileExists(base_path('composer.lock'));
        $this->assertFileExists(base_path('package-lock.json'));
        $tracked = (string) shell_exec('git -c safe.directory="'.$safe.'" ls-files');
        $this->assertDoesNotMatchRegularExpression('/(^|\/)\.env$/m', $tracked);
        $this->assertDoesNotMatchRegularExpression('/(^|\/)(vendor|node_modules)\//m', $tracked);
        $this->assertFileDoesNotExist(base_path('../artisan'));
        $this->assertFileDoesNotExist(base_path('../package.json'));
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
