<?php

namespace App\Modules\Platform\Processing;

use Illuminate\Support\Facades\DB;

class WorkerHeartbeatService
{
    /**
     * Durably upserts heartbeat liveness metrics for the current execution identity.
     */
    public function recordHeartbeat(string $workerIdentifier, string $provider, int $ttlSeconds = 120): WorkerHeartbeat
    {
        return DB::transaction(function () use ($workerIdentifier, $provider, $ttlSeconds) {
            return WorkerHeartbeat::query()->updateOrCreate(
                ['worker_identifier' => $workerIdentifier],
                ['provider' => $provider, 'last_seen_at' => now(), 'ttl_seconds' => $ttlSeconds]
            );
        });
    }
}
