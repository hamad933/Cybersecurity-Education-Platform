<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_identifier', 160)->nullable();
            $table->string('action', 120);
            $table->string('target_type', 120);
            $table->string('target_identifier', 160)->nullable();
            $table->uuid('correlation_id');
            $table->string('outcome', 32);
            $table->jsonb('safe_metadata')->default('{}');
            $table->timestampTz('occurred_at');
            $table->index(['action', 'occurred_at']);
            $table->index('correlation_id');
        });
        DB::statement("ALTER TABLE audit_records ADD CONSTRAINT audit_outcome_check CHECK (outcome IN ('success','failure','denied'))");

        Schema::create('blob_objects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('storage_key', 512)->unique();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('media_type', 160)->nullable();
            $table->string('status', 24)->default('ready');
            $table->timestampTz('created_at');
        });
        DB::statement("ALTER TABLE blob_objects ADD CONSTRAINT blob_status_check CHECK (status IN ('ready','quarantined','deleted'))");
        DB::statement("ALTER TABLE blob_objects ADD CONSTRAINT blob_sha_check CHECK (sha256 ~ '^[0-9a-f]{64}$')");

        Schema::create('processing_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 120);
            $table->char('input_digest', 64);
            $table->string('idempotency_key', 200)->unique();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('error_category', 80)->nullable();
            $table->string('safe_error_message', 500)->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE processing_runs ADD CONSTRAINT processing_status_check CHECK (status IN ('pending','running','completed','failed','cancelled'))");
        DB::statement("ALTER TABLE processing_runs ADD CONSTRAINT processing_digest_check CHECK (input_digest ~ '^[0-9a-f]{64}$')");

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('schema_version');
            $table->string('type', 160);
            $table->string('producer_module', 16);
            $table->uuid('correlation_id');
            $table->uuid('causation_id')->nullable();
            $table->jsonb('payload');
            $table->string('idempotency_key', 200)->unique();
            $table->timestampTz('occurred_at');
            $table->string('dispatch_state', 24)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('leased_until')->nullable();
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('dispatched_at')->nullable();
            $table->index(['dispatch_state', 'next_attempt_at']);
        });
        DB::statement("ALTER TABLE outbox_messages ADD CONSTRAINT outbox_state_check CHECK (dispatch_state IN ('pending','leased','dispatched','failed'))");
        DB::statement('ALTER TABLE outbox_messages ADD CONSTRAINT outbox_payload_size_check CHECK (octet_length(payload::text) <= 16384)');
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('processing_runs');
        Schema::dropIfExists('blob_objects');
        Schema::dropIfExists('audit_records');
    }
};
