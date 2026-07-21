<?php

namespace App\Modules\Platform\Queue;

use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FoundationSmokeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 5, 15];

    public function __construct(public readonly string $processingRunId) {}

    public function handle(): void
    {
        $run = ProcessingRun::query()->findOrFail($this->processingRunId);
        if ($run->status === 'completed') {
            return;
        }
        if ($run->status === 'pending') {
            $run->transitionTo('running');
        }
        $run->transitionTo('completed');
    }

    public function failed(?Throwable $exception): void
    {
        $run = ProcessingRun::query()->find($this->processingRunId);
        if ($run?->status === 'running') {
            $run->transitionTo('failed', 'queue_failure', 'Foundation smoke job failed.');
        }
    }
}
