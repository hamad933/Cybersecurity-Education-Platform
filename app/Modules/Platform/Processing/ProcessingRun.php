<?php

namespace App\Modules\Platform\Processing;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ProcessingRun extends Model
{
    use UsesUuidV7;

    protected $fillable = ['type', 'input_digest', 'idempotency_key', 'status', 'attempt_count', 'max_attempts', 'worker_identifier', 'retry_of_id', 'retry_ordinal', 'started_at', 'completed_at', 'cancelled_at', 'error_category', 'safe_error_message', 'leased_until', 'next_attempt_at'];

    protected function casts(): array
    {
        return ['started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'leased_until' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime'];
    }

    public function lockForTransition(): self
    {
        // Require active transaction or this will be a no-op lock. 
        // Best used inside DB::transaction.
        return static::query()->where('id', $this->id)->lockForUpdate()->firstOrFail();
    }

    public function recordWorkerIdentity(string $workerIdentifier, string $provider = 'database'): void
    {
        if ($this->status === 'pending') {
            $this->worker_identifier = $workerIdentifier;
            $this->save();
            
            \App\Modules\Platform\Processing\WorkerHeartbeat::query()->updateOrCreate(
                ['worker_identifier' => $workerIdentifier],
                ['provider' => $provider, 'last_seen_at' => now(), 'ttl_seconds' => 120]
            );
        }
    }
    
    public function refreshWorkerHeartbeat(string $provider = 'database'): void
    {
        if ($this->worker_identifier && in_array($this->status, ['running', 'pending'])) {
            \App\Modules\Platform\Processing\WorkerHeartbeat::query()->updateOrCreate(
                ['worker_identifier' => $this->worker_identifier],
                ['provider' => $provider, 'last_seen_at' => now(), 'ttl_seconds' => 120]
            );
        }
    }

    public function transitionTo(string $next, ?string $category = null, ?string $safeMessage = null): void
    {
        // Terminal states immutable
        $allowed = [
            'pending' => ['running', 'cancelled'],
            'running' => ['completed', 'failed', 'cancelled'],
            'completed' => [], 'failed' => [], 'cancelled' => [],
        ];
        
        if (! in_array($next, $allowed[$this->status] ?? [], true)) {
            throw new InvalidArgumentException("Invalid processing transition {$this->status} -> {$next}");
        }
        
        $this->status = $next;
        
        if ($next === 'running') {
            $this->started_at = now();
            $this->attempt_count++;
        }
        if ($next === 'completed') {
            $this->completed_at = now();
        }
        if ($next === 'cancelled') {
            $this->cancelled_at = now();
        }
        if ($next === 'failed') {
            $this->completed_at = now();
            $this->error_category = $category;
            
            // "Safe error sanitization must be allowlist/category based"
            // We explicitly restrict what text can be persisted based on category definitions
            $safeDefaults = [
                'WORKER_CRASHED' => 'Worker lease expired before completion.',
                'QUEUE_FAILURE' => 'Queue job failed during execution.',
                'INVALID_INPUT' => 'Processing run received invalid input.',
                'DEPENDENCY_UNAVAILABLE' => 'A required remote dependency was unavailable.',
                'INTERNAL_ERROR' => 'An internal system error occurred.',
            ];
            
            // Default to INTERNAL_ERROR format if not within allowlist logic or matches unmapped.
            $mappedMessage = $safeDefaults[$category] ?? 'A processing error occurred.';
            
            $this->safe_error_message = $mappedMessage;
        }
        $this->save();
    }
    
    public function retryRun(): self
    {
        if ($this->status !== 'failed') {
            throw new InvalidArgumentException("Only failed runs can be retried.");
        }

        return \Illuminate\Support\Facades\DB::transaction(function () {
            // Lock original failed model strictly to prevent race against concurrent retries
            $locked = $this->lockForTransition();
            
            // Check for already-created child for this exact failed run deterministically.
            // MUST be done before max-attempts boundary to avoid rejecting idempotency readbacks.
            $existingChild = self::query()->where('retry_of_id', $locked->id)->first();
            if ($existingChild) {
                return $existingChild;
            }
            
            // Compute retry ordinal from locked lineage truth
            $ordinal = ($locked->retry_ordinal ?? 0) + 1;
            
            if ($ordinal >= ($locked->max_attempts ?? 3)) {
                throw new InvalidArgumentException("Max attempts exhausted for run lineage.");
            }

            return self::query()->create([
                'type' => $locked->type,
                'input_digest' => $locked->input_digest,
                'idempotency_key' => $locked->idempotency_key,
                'status' => 'pending',
                'attempt_count' => 0, // Fresh count for this specific run
                'max_attempts' => $locked->max_attempts,
                'retry_of_id' => $locked->id,
                'retry_ordinal' => $ordinal,
            ]);
        });
    }

    public function failIfLeaseExpired(string $safeMessage = 'Worker lease expired before completion.'): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($safeMessage) {
            $locked = $this->lockForTransition();
            if ($locked->status === 'running' && $locked->leased_until && $locked->leased_until->isPast()) {
                $locked->transitionTo('failed', 'WORKER_CRASHED', $safeMessage);
            }
        });
    }
}
