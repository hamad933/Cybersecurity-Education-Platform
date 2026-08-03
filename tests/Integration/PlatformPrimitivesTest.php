<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Blobs\StoredBlob;
use App\Modules\Platform\Health\FoundationHealth;
use App\Modules\Platform\Messaging\FoundationPingConsumer;
use App\Modules\Platform\Messaging\OutboxMessage;
use App\Modules\Platform\Messaging\OutboxPublisher;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Tests\Fixtures\QueueLifecycleProbeJob;
use Tests\TestCase;

class PlatformPrimitivesTest extends TestCase
{
    use RefreshDatabase;

    public function test_identifier_round_trips_through_postgresql(): void
    {
        $owner = OwnerAccount::query()->create(['display_name' => 'Owner', 'email' => 'roundtrip@example.test', 'password' => 'VeryStrong!Pass9', 'is_active' => true]);
        $this->assertTrue(Str::isUuid($owner->id, 7));
        $this->assertSame($owner->id, OwnerAccount::query()->findOrFail($owner->id)->id);
    }

    public function test_audit_is_append_only_bounded_and_rejects_sensitive_metadata(): void
    {
        $record = app(AuditWriter::class)->append(['actor_identifier' => null, 'action' => 'foundation.test', 'target_type' => 'platform', 'target_identifier' => null, 'correlation_id' => (string) Str::uuid7(), 'outcome' => 'success', 'safe_metadata' => ['check' => 'ok']]);
        $this->assertDatabaseHas('audit_records', ['id' => $record->id]);
        $this->expectException(LogicException::class);
        $record->delete();
    }

    public function test_audit_rejects_sensitive_metadata_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(AuditWriter::class)->append(['action' => 'foundation.test', 'target_type' => 'platform', 'correlation_id' => (string) Str::uuid7(), 'outcome' => 'success', 'safe_metadata' => ['password' => 'never']]);
    }

    public function test_blob_store_rejects_traversal_and_streams_with_digest(): void
    {
        Storage::fake('local');
        $store = app(BlobStore::class);
        $input = fopen('php://temp', 'w+b');
        fwrite($input, 'bounded content');
        rewind($input);
        $blob = $store->writeStream($input);
        $this->assertSame(strlen('bounded content'), $blob->size);
        $this->assertSame(hash('sha256', 'bounded content'), $blob->sha256);
        $this->assertSame('bounded content', stream_get_contents($store->readStream($blob->key)));
        $this->expectException(InvalidArgumentException::class);
        $store->readStream('../source-vault/originals/forbidden');
    }

    public function test_diagnostic_blob_probe_is_read_verified_and_deleted(): void
    {
        Storage::fake('local');
        $before = Storage::disk('local')->allFiles();

        $checks = app(FoundationHealth::class)->diagnosticChecks();

        $this->assertSame('ok', $checks['blob']);
        $this->assertSame($before, Storage::disk('local')->allFiles());
    }

    public function test_diagnostic_blob_probe_deletes_the_temporary_blob_when_reading_fails(): void
    {
        $blob = new StoredBlob('2026/07/22/probe.blob', 10, hash('sha256', 'diagnostic'));
        $store = \Mockery::mock(BlobStore::class);
        $store->shouldReceive('writeStream')->once()->andReturn($blob);
        $store->shouldReceive('readStream')->once()->with($blob->key)->andThrow(new RuntimeException('Controlled read failure.'));
        $store->shouldReceive('delete')->once()->with($blob->key);

        $checks = (new FoundationHealth($store))->diagnosticChecks();

        $this->assertSame('failed', $checks['blob']);
    }

    public function test_authenticated_dashboard_health_is_read_only_for_blob_storage(): void
    {
        Storage::fake('local');
        $owner = OwnerAccount::query()->create(['display_name' => 'Owner', 'email' => 'dashboard@example.test', 'password' => 'VeryStrong!Pass9', 'is_active' => true]);
        $before = Storage::disk('local')->allFiles();

        $this->actingAs($owner)->get('/')->assertOk();

        $this->assertSame($before, Storage::disk('local')->allFiles());
    }

    public function test_processing_run_transitions_and_idempotency_are_enforced(): void
    {
        $run = ProcessingRun::query()->create(['type' => 'foundation.smoke', 'input_digest' => hash('sha256', 'input'), 'idempotency_key' => 'run:one', 'status' => 'pending']);
        $run->transitionTo('running');
        $run->transitionTo('completed');
        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->attempt_count);
        $this->expectException(QueryException::class);
        ProcessingRun::query()->create(['type' => 'foundation.smoke', 'input_digest' => hash('sha256', 'other'), 'idempotency_key' => 'run:one', 'status' => 'pending']);
    }

    public function test_processing_run_rejects_invalid_transition(): void
    {
        $run = ProcessingRun::query()->create(['type' => 'foundation.smoke', 'input_digest' => hash('sha256', 'input'), 'idempotency_key' => 'run:invalid', 'status' => 'pending']);
        $this->expectException(InvalidArgumentException::class);
        $run->transitionTo('completed');
    }

    public function test_outbox_publish_is_transactional_and_consumer_is_idempotent(): void
    {
        $correlation = (string) Str::uuid7();
        $message = DB::transaction(fn () => app(OutboxPublisher::class)->foundationPing('ping:one', $correlation, 'hello'));
        $duplicate = app(OutboxPublisher::class)->foundationPing('ping:one', $correlation, 'ignored');
        $this->assertSame($message->id, $duplicate->id);
        $consumer = app(FoundationPingConsumer::class);
        $this->assertTrue($consumer->consume($message));
        $this->assertFalse($consumer->consume($message->refresh()));
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_database_queue_worker_retries_then_completes_a_correlated_job(): void
    {
        $run = ProcessingRun::query()->create(['type' => 'foundation.queue-smoke', 'input_digest' => hash('sha256', 'queue'), 'idempotency_key' => 'queue:one', 'status' => 'pending']);
        QueueLifecycleProbeJob::dispatch($run->id, 1);
        $this->assertDatabaseCount('jobs', 1);

        $this->runOneDatabaseQueueAttempt();
        $this->assertSame('running', $run->refresh()->status);
        $this->assertDatabaseCount('jobs', 1);

        $this->runOneDatabaseQueueAttempt();
        $this->assertSame('completed', $run->refresh()->status);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_database_queue_worker_records_terminal_failure_after_real_retries(): void
    {
        $run = ProcessingRun::query()->create(['type' => 'foundation.queue-failure', 'input_digest' => hash('sha256', 'queue-failure'), 'idempotency_key' => 'queue:failure', 'status' => 'pending']);
        QueueLifecycleProbeJob::dispatch($run->id, 3);

        $this->runOneDatabaseQueueAttempt();
        $this->runOneDatabaseQueueAttempt();
        $this->runOneDatabaseQueueAttempt();

        $this->assertSame('failed', $run->refresh()->status);
        $this->assertSame('queue_probe_failure', $run->error_category);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
    }

    public function test_diagnostic_command_reports_success_and_failure_states(): void
    {
        $this->artisan('app:diagnose')->assertSuccessful()->expectsOutputToContain('database         OK');
        config()->set('platform.auth_bypass', true);
        $this->artisan('app:diagnose')->assertFailed()->expectsOutputToContain('configuration    FAILED');
    }

    public function test_diagnostic_database_failure_is_controlled_and_skips_audit(): void
    {
        $connection = (string) config('database.default');
        $originalHost = config("database.connections.{$connection}.host");
        $originalPort = config("database.connections.{$connection}.port");
        $audit = \Mockery::mock(AuditWriter::class);
        $audit->shouldNotReceive('append');
        app()->instance(AuditWriter::class, $audit);

        try {
            config()->set("database.connections.{$connection}.host", '127.0.0.1');
            config()->set("database.connections.{$connection}.port", 1);
            DB::purge($connection);

            $this->artisan('app:diagnose')
                ->assertFailed()
                ->expectsOutputToContain('database         FAILED')
                ->expectsOutputToContain('audit            SKIPPED_DATABASE_UNAVAILABLE');
        } finally {
            config()->set("database.connections.{$connection}.host", $originalHost);
            config()->set("database.connections.{$connection}.port", $originalPort);
            DB::purge($connection);
            DB::connection($connection)->getPdo();
        }
    }

    public function test_diagnostic_missing_table_is_controlled_and_no_audit_option_skips_writes(): void
    {
        $audit = \Mockery::mock(AuditWriter::class);
        $audit->shouldNotReceive('append');
        app()->instance(AuditWriter::class, $audit);
        Schema::partialMock()->shouldReceive('hasTable')->andReturnUsing(fn (string $table): bool => $table !== 'owner_accounts');

        $this->artisan('app:diagnose', ['--no-audit' => true])
            ->assertFailed()
            ->expectsOutputToContain('migrations       FAILED');
    }

    public function test_postgresql_constraints_reject_invalid_owner_and_outbox_state(): void
    {
        $this->expectException(QueryException::class);
        OwnerAccount::query()->create(['display_name' => 'Owner', 'email' => 'UPPER@example.test', 'password' => 'VeryStrong!Pass9', 'is_active' => true]);
    }

    public function test_postgresql_outbox_status_constraint_is_active(): void
    {
        $this->expectException(QueryException::class);
        OutboxMessage::query()->create(['schema_version' => 1, 'type' => 'foundation.ping.v1', 'producer_module' => 'MOD-PLT', 'correlation_id' => (string) Str::uuid7(), 'payload' => ['message' => 'x'], 'idempotency_key' => 'bad:state', 'occurred_at' => now(), 'dispatch_state' => 'unknown']);
    }

    private function runOneDatabaseQueueAttempt(): void
    {
        $exit = Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'default',
            '--once' => true,
            '--tries' => 3,
            '--backoff' => 0,
            '--sleep' => 0,
            '--timeout' => 30,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
    }
}
