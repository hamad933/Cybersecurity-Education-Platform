<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleBoundaryTest extends TestCase
{
    private array $namespaceToId = ['IdentityAccess' => 'MOD-IAM', 'Platform' => 'MOD-PLT'];

    #[Test]
    public function registry_contains_all_ten_modules_and_only_two_are_active(): void
    {
        $modules = config('platform.modules');
        $this->assertCount(10, $modules);
        $this->assertSame(['IdentityAccess', 'Platform'], $this->activeModuleDirectories());
        $this->assertSame(['MOD-IAM', 'MOD-PLT'], collect($this->activeModuleDirectories())->map(fn ($name) => $this->namespaceToId[$name])->sort()->values()->all());
    }

    #[Test]
    public function locked_graph_is_acyclic(): void
    {
        $graph = collect(config('platform.modules'))->map(fn ($module) => $module['dependencies'])->all();
        $visiting = [];
        $visited = [];
        $visit = function (string $node) use (&$visit, &$visiting, &$visited, $graph): void {
            $this->assertFalse(isset($visiting[$node]), "Cycle detected at {$node}");
            if (isset($visited[$node])) {
                return;
            }
            $visiting[$node] = true;
            foreach ($graph[$node] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$node]);
            $visited[$node] = true;
        };
        foreach (array_keys($graph) as $node) {
            $visit($node);
        }
        $this->assertCount(10, $visited);
    }

    #[Test]
    public function imports_obey_dependencies_and_platform_imports_no_domain_module(): void
    {
        foreach ($this->phpFiles() as $file) {
            $source = file_get_contents($file);
            preg_match('/namespace App\\\\Modules\\\\([^;\\\\]+)/', $source, $owner);
            if (! isset($owner[1])) {
                continue;
            }
            $consumer = $this->namespaceToId[$owner[1]];
            preg_match_all('/use App\\\\Modules\\\\([^;\\\\]+)/', $source, $imports);
            foreach ($imports[1] as $importName) {
                $dependency = $this->namespaceToId[$importName];
                $this->assertTrue($consumer === $dependency || in_array($dependency, config("platform.modules.{$consumer}.dependencies"), true), "Illegal import {$consumer} -> {$dependency} in {$file}");
                if ($consumer === 'MOD-PLT') {
                    $this->assertSame('MOD-PLT', $dependency);
                }
            }
        }
    }

    #[Test]
    public function no_learning_evidence_cycle_or_cross_module_model_import_exists(): void
    {
        $source = collect($this->phpFiles())->map(fn ($file) => file_get_contents($file))->join("\n");
        $this->assertStringNotContainsString('Modules\\Learning', $source);
        $this->assertStringNotContainsString('Modules\\Evidence', $source);
        $this->assertDoesNotMatchRegularExpression('/namespace App\\\\Modules\\\\Platform.*use App\\\\Modules\\\\IdentityAccess\\\\Models/s', $source);
    }

    #[Test]
    public function raw_table_writes_stay_with_the_owning_module(): void
    {
        $ownership = ['IdentityAccess' => ['owner_accounts', 'application_sessions'], 'Platform' => ['audit_records', 'blob_objects', 'processing_runs', 'outbox_messages', 'jobs', 'job_batches', 'failed_jobs']];
        foreach ($this->phpFiles() as $file) {
            $source = file_get_contents($file);
            preg_match('/namespace App\\\\Modules\\\\([^;\\\\]+)/', $source, $owner);
            if (! isset($owner[1])) {
                continue;
            }
            preg_match_all('/DB::table\\([\'\"]([^\'\"]+)/', $source, $tables);
            foreach ($tables[1] as $table) {
                $this->assertContains($table, $ownership[$owner[1]], "Cross-module write to {$table}");
            }
        }
    }

    private function activeModuleDirectories(): array
    {
        $names = array_map('basename', glob(app_path('Modules/*'), GLOB_ONLYDIR));
        sort($names);

        return $names;
    }

    private function phpFiles(): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path('Modules')));

        return array_values(array_filter(array_map(fn ($file) => $file->getPathname(), iterator_to_array($iterator)), fn ($file) => str_ends_with($file, '.php')));
    }
}
