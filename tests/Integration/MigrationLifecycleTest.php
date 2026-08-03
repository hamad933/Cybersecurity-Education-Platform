<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\Support\DestructiveDatabaseGuard;
use Tests\TestCase;

class MigrationLifecycleTest extends TestCase
{
    public function test_postgresql_fresh_rollback_and_reapply_lifecycle(): void
    {
        DestructiveDatabaseGuard::fromRuntime()->assertSafe();
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));

        $tables = [
            'owner_accounts',
            'application_sessions',
            'audit_records',
            'blob_objects',
            'processing_runs',
            'outbox_messages',
            'jobs',
            'job_batches',
            'failed_jobs',
            'source_records',
            'source_claims',
            'knowledge_units',
            'lesson_revisions',
            'curriculum_placements',
            'enterprise_baseline_revisions',
            'improvement_proposals',
            'simulator_rule_revisions',
            'scenario_revisions',
            'scenario_runs',
            'decision_traces',
            'replay_records',
            'evidence_records',
            'evidence_decisions',
            'micro_practices',
            'practice_attempts',
            'mastery_rule_revisions',
            'mastery_states',
            'review_triggers',
            'web_endpoint_contract_revisions',
            'web_authorization_policy_revisions',
            'security_findings',
            'finding_verifications',
            'vs003_telemetry_dataset_revisions',
            'vs003_investigation_cases',
            'vs003_investigation_alerts',
            'vs003_triage_records',
            'vs003_custody_events',
            'vs003_containment_proposals',
            'vs003_control_revisions',
            'vs003_verification_replays',
        ];
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing PostgreSQL table {$table}");
        }
        $this->assertTrue(Schema::hasColumns('vs003_containment_proposals', ['triage_record_id']));
        $this->assertTrue(Schema::hasColumns('vs003_control_revisions', ['actor_id', 'proposal_id', 'triage_record_id']));
        $this->assertTrue(Schema::hasColumns('vs003_verification_replays', ['actor_id', 'triage_record_id']));

        $migrationCount = count(glob(database_path('migrations/*.php')));
        $this->assertSame(12, $migrationCount);
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => $migrationCount, '--force' => true]));
        $this->assertFalse(Schema::hasTable('owner_accounts'));
        $this->assertFalse(Schema::hasTable('vs003_telemetry_dataset_revisions'));

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertTrue(Schema::hasTable('owner_accounts'));
        $this->assertTrue(Schema::hasTable('vs003_verification_replays'));
    }
}
