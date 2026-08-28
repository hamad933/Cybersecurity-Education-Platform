<?php

namespace App\Modules\Platform\Processing;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ProcessingRun extends Model
{
    use UsesUuidV7;

    protected $fillable = ['type', 'input_digest', 'idempotency_key', 'status', 'attempt_count', 'max_attempts', 'worker_identifier', 'started_at', 'completed_at', 'cancelled_at', 'error_category', 'safe_error_message', 'leased_until', 'next_attempt_at'];

    protected function casts(): array
    {
        return ['started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'leased_until' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime'];
    }

    public function transitionTo(string $next, ?string $category = null, ?string $safeMessage = null): void
    {
        $allowed = [
            'pending' => ['running', 'cancelled'],
            'running' => ['completed', 'failed', 'cancelled', 'pending'],
            'completed' => [], 'failed' => ['pending'], 'cancelled' => [],
        ];
        if (! in_array($next, $allowed[$this->status] ?? [], true)) {
            throw new InvalidArgumentException("Invalid processing transition {$this->status} -> {$next}");
        }
        $this->status = $next;
        if ($next === 'pending') {
            $this->started_at = null;
            $this->completed_at = null;
            $this->cancelled_at = null;
            $this->error_category = null;
            $this->safe_error_message = null;
            $this->leased_until = null;
            $this->next_attempt_at = null;
        }
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
            $this->safe_error_message = $safeMessage ? mb_substr($safeMessage, 0, 500) : null;
        }
        $this->save();
    }

    public function failIfLeaseExpired(string $safeMessage = 'Worker lease expired before completion.'): void
    {
        if ($this->status === 'running' && $this->leased_until && $this->leased_until->isPast()) {
            $this->transitionTo('failed', 'WORKER_CRASHED', $safeMessage);
        }
    }
}
