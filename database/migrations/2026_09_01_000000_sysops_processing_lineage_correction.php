<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_heartbeats', function (Blueprint $table): void {
            $table->string('worker_identifier', 160)->primary();
            $table->string('provider', 80);
            $table->timestampTz('last_seen_at');
            $table->unsignedInteger('ttl_seconds')->default(120);
            $table->timestampsTz();
        });

        Schema::table('processing_runs', function (Blueprint $table): void {
            if (!Schema::hasColumn('processing_runs', 'worker_identifier')) {
                $table->string('worker_identifier', 160)->nullable();
            }
            if (!Schema::hasColumn('processing_runs', 'retry_of_id')) {
                $table->uuid('retry_of_id')->nullable()->unique();
                $table->foreign('retry_of_id')->references('id')->on('processing_runs')->nullOnDelete();
            }
            if (!Schema::hasColumn('processing_runs', 'max_attempts')) {
                $table->unsignedInteger('max_attempts')->default(3);
            }
            if (!Schema::hasColumn('processing_runs', 'retry_ordinal')) {
                $table->unsignedInteger('retry_ordinal')->default(0);
            }
            $table->unique(['idempotency_key', 'retry_ordinal']);
        });
        
        Schema::table('processing_runs', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
        });
    }

    public function down(): void
    {
        // Safe downgrade invariant: verify we aren't truncating intentional active data loops
        // Ensure no duplicated idempotency keys exist before restoring unique constraint.
        if (Schema::hasColumn('processing_runs', 'retry_ordinal')) {
            $duplicateCount = (int) DB::table('processing_runs')
                ->select('idempotency_key')
                ->groupBy('idempotency_key')
                ->havingRaw('COUNT(*) > 1')
                ->count();
            
            if ($duplicateCount > 0) {
                throw new \RuntimeException('Cannot reverse processing_runs idempotency constraint. There are ' . $duplicateCount . ' retried runs grouping identical keys.');
            }
        }

        Schema::table('processing_runs', function (Blueprint $table): void {
            $table->unique(['idempotency_key']);
            $table->dropUnique(['idempotency_key', 'retry_ordinal']);
            $table->dropForeign(['retry_of_id']);
            $table->dropUnique(['retry_of_id']);
            $table->dropColumn(['worker_identifier', 'retry_of_id', 'max_attempts', 'retry_ordinal']);
        });
        Schema::dropIfExists('worker_heartbeats');
    }
};
