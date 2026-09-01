<?php

namespace App\Modules\Platform\Processing;

use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;

/**
 * Bounded SYSOPS subscriber mapping shared runtime queue events to governed heartbeat states.
 */
class QueueWorkerHeartbeatSubscriber
{
    public function __construct(private readonly WorkerHeartbeatService $heartbeatService) {}

    public function handleJobProcessing(JobProcessing $event): void
    {
        $this->recordLiveness($event->connectionName);
    }

    public function handleLooping(Looping $event): void
    {
        $this->recordLiveness($event->connectionName);
    }

    private function recordLiveness(string $provider): void
    {
        $workerId = gethostname() . ':' . getmypid();
        $this->heartbeatService->recordHeartbeat($workerId, $provider, 120);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            JobProcessing::class => 'handleJobProcessing',
            Looping::class => 'handleLooping',
        ];
    }
}
