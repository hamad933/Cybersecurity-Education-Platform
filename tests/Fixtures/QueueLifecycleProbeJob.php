<?php

namespace Tests\Fixtures;

use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class QueueLifecycleProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 0;

    public function __construct(
        public readonly string $processingRunId,
        public readonly int $failThroughAttempt,
    ) {}

    public function handle(): void
    {
        $run = ProcessingRun::query()->findOrFail($this->processingRunId);
        if ($run->status === 'pending') {
            $run->transitionTo('running');
        }
        if ($this->attempts() <= $this->failThroughAttempt) {
            throw new RuntimeException('Controlled queue lifecycle probe failure.');
        }
        if ($run->status === 'running') {
            $run->transitionTo('completed');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $run = ProcessingRun::query()->find($this->processingRunId);
        if ($run?->status === 'running') {
            $run->transitionTo('failed', 'queue_probe_failure', 'Controlled queue lifecycle probe reached terminal failure.');
        }
    }
}
