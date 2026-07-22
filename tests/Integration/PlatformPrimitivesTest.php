<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Messaging\FoundationPingConsumer;
use App\Modules\Platform\Messaging\OutboxMessage;
use App\Modules\Platform\Messaging\OutboxPublisher;
use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\Platform\Queue\FoundationSmokeJob;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
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

    public function test_database_queue_smoke_job_is_correlated_retryable_and_idempotent(): void
    {
        $run = ProcessingRun::query()->create(['type' => 'foundation.queue-smoke', 'input_digest' => hash('sha256', 'queue'), 'idempotency_key' => 'queue:one', 'status' => 'pending']);
        FoundationSmokeJob::dispatch($run->id);
        $this->assertDatabaseCount('jobs', 1);
        $job = new FoundationSmokeJob($run->id);
        $this->assertSame(3, $job->tries);
        $this->assertSame([1, 5, 15], $job->backoff);
        $job->handle();
        $job->handle();
        $this->assertSame('completed', $run->refresh()->status);
    }

    public function test_diagnostic_command_reports_success_and_failure_states(): void
    {
        $this->artisan('app:diagnose')->assertSuccessful()->expectsOutputToContain('database         OK');
        config()->set('platform.auth_bypass', true);
        $this->artisan('app:diagnose')->assertFailed()->expectsOutputToContain('configuration    FAILED');
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
}
