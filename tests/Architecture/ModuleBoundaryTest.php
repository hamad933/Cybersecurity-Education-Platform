<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleBoundaryTest extends TestCase
{
    private array $namespaceToId = [
        'IdentityAccess' => 'MOD-IAM',
        'Platform' => 'MOD-PLT',
        'SourceGovernance' => 'MOD-SRC',
        'Knowledge' => 'MOD-KNO',
        'Curriculum' => 'MOD-CUR',
        'Enterprise' => 'MOD-ENT',
        'Simulator' => 'MOD-SIM',
        'Evidence' => 'MOD-EVD',
        'Learning' => 'MOD-LRN',
        'ManualAiBridge' => 'MOD-AIB',
    ];

    #[Test]
    public function registry_contains_all_ten_v1_modules(): void
    {
        $modules = config('platform.modules');
        $this->assertCount(10, $modules);
        $expected = ['Curriculum', 'Enterprise', 'Evidence', 'IdentityAccess', 'Knowledge', 'Learning', 'ManualAiBridge', 'Platform', 'Simulator', 'SourceGovernance'];
        $this->assertSame($expected, $this->activeModuleDirectories());
        $this->assertSame(['MOD-AIB', 'MOD-CUR', 'MOD-ENT', 'MOD-EVD', 'MOD-IAM', 'MOD-KNO', 'MOD-LRN', 'MOD-PLT', 'MOD-SIM', 'MOD-SRC'], collect($this->activeModuleDirectories())->map(fn ($name) => $this->namespaceToId[$name])->sort()->values()->all());
        $this->assertDirectoryExists(app_path('Modules/ManualAiBridge'));
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
        foreach ($this->modulePhpFiles() as $file) {
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
    public function no_forbidden_cross_module_model_import_exists(): void
    {
        foreach ($this->modulePhpFiles() as $file) {
            $source = file_get_contents($file);
            preg_match('/namespace App\\\\Modules\\\\([^;\\\\]+)/', $source, $owner);
            preg_match_all('/use App\\\\Modules\\\\([^;\\\\]+)\\\\Models\\\\/', $source, $imports);
            foreach ($imports[1] as $importName) {
                $this->assertSame($owner[1], $importName, "Cross-module ORM import in {$file}");
            }
            if (($owner[1] ?? null) === 'Simulator') {
                $this->assertStringNotContainsString('Modules\\Evidence', $source);
                $this->assertStringNotContainsString('Modules\\Learning', $source);
            }
        }
    }

    #[Test]
    public function raw_table_writes_stay_with_the_owning_module(): void
    {
        $ownership = [
            'IdentityAccess' => ['owner_accounts', 'application_sessions'],
            'Platform' => ['audit_records', 'blob_objects', 'processing_runs', 'outbox_messages', 'portable_packages', 'search_documents', 'backup_manifests', 'restore_runs', 'jobs', 'job_batches', 'failed_jobs'],
            'SourceGovernance' => ['source_records', 'source_claims', 'source_imports'],
            'Knowledge' => ['knowledge_units', 'lesson_revisions'],
            'Curriculum' => ['curriculum_placements'],
            'Enterprise' => ['enterprise_baseline_revisions', 'improvement_proposals'],
            'Simulator' => ['simulator_rule_revisions', 'scenario_revisions', 'scenario_runs', 'decision_traces', 'replay_records', 'vs003_telemetry_dataset_revisions', 'vs003_investigation_cases', 'vs003_investigation_alerts', 'vs003_triage_records'],
            'Evidence' => ['evidence_records', 'evidence_decisions', 'imported_evidence_records', 'vs003_custody_events', 'vs003_containment_proposals', 'vs003_control_revisions', 'vs003_verification_replays'],
            'Learning' => ['micro_practices', 'practice_attempts', 'mastery_rule_revisions', 'mastery_states', 'review_triggers'],
            'ManualAiBridge' => ['prompt_packages', 'prompt_package_revisions', 'imported_ai_results', 'ai_proposal_decisions'],
        ];
        foreach ($this->modulePhpFiles() as $file) {
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

    #[Test]
    public function coordinators_controllers_jobs_and_listeners_do_not_import_module_orm_models(): void
    {
        foreach ($this->applicationBoundaryPhpFiles() as $file) {
            $source = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression(
                '/use App\\\\Modules\\\\[^;]+\\\\Models\\\\/',
                $source,
                "Application boundary imports a module ORM model: {$file}",
            );
        }
    }

    #[Test]
    public function controllers_do_not_coordinate_multiple_module_model_owners(): void
    {
        foreach ($this->phpFilesUnder(app_path('Http/Controllers')) as $file) {
            $source = file_get_contents($file);
            preg_match_all('/use App\\\\Modules\\\\([^;\\\\]+)\\\\Models\\\\/', $source, $imports);
            $this->assertLessThanOrEqual(1, count(array_unique($imports[1])), "Controller imports models from multiple owned modules: {$file}");
        }
    }

    #[Test]
    public function simulator_has_no_evidence_or_learning_implementation_dependency(): void
    {
        foreach ($this->phpFilesUnder(app_path('Modules/Simulator')) as $file) {
            $source = file_get_contents($file);
            $this->assertStringNotContainsString('App\\Modules\\Evidence', $source, "Simulator depends on Evidence implementation: {$file}");
            $this->assertStringNotContainsString('App\\Modules\\Learning', $source, "Simulator depends on Learning implementation: {$file}");
        }
    }

    #[Test]
    public function every_module_import_resolves_to_the_registered_graph(): void
    {
        $registered = array_keys(config('platform.modules'));
        foreach ($this->allApplicationPhpFiles() as $file) {
            $source = file_get_contents($file);
            preg_match_all('/App\\\\Modules\\\\([^;\\\\]+)/', $source, $imports);
            foreach ($imports[1] as $namespace) {
                $this->assertArrayHasKey($namespace, $this->namespaceToId, "Unregistered module namespace {$namespace} in {$file}");
                $this->assertContains($this->namespaceToId[$namespace], $registered);
            }
        }
    }

    private function activeModuleDirectories(): array
    {
        $names = array_map('basename', glob(app_path('Modules/*'), GLOB_ONLYDIR));
        sort($names);

        return $names;
    }

    private function modulePhpFiles(): array
    {
        return $this->phpFilesUnder(app_path('Modules'));
    }

    private function applicationBoundaryPhpFiles(): array
    {
        return array_merge(
            $this->phpFilesUnder(app_path('Application')),
            $this->phpFilesUnder(app_path('Http/Controllers')),
            $this->phpFilesUnder(app_path('Jobs')),
            $this->phpFilesUnder(app_path('Listeners')),
        );
    }

    private function allApplicationPhpFiles(): array
    {
        return $this->phpFilesUnder(app_path());
    }

    private function phpFilesUnder(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        return array_values(array_filter(array_map(fn ($file) => $file->getPathname(), iterator_to_array($iterator)), fn ($file) => str_ends_with($file, '.php')));
    }
}
