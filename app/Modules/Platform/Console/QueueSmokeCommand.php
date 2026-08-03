<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\Platform\Queue\FoundationSmokeJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

final class QueueSmokeCommand extends Command
{
    protected $signature = 'platform:queue-smoke
        {--timeout=60 : Maximum seconds to wait for the worker}
        {--poll-ms=250 : Poll interval in milliseconds}
        {--json : Emit a machine-readable result}';

    protected $description = 'Dispatch and verify one bounded database-queue smoke job';

    public function handle(): int
    {
        if (! in_array((string) config('platform.profile'), ['release', 'test'], true)) {
            return $this->finish('FAIL', 'profile_not_allowed', null, 0);
        }

        $timeoutSeconds = max(5, min(120, (int) $this->option('timeout')));
        $pollMilliseconds = max(100, min(1000, (int) $this->option('poll-ms')));
        $token = (string) Str::uuid7();

        $run = ProcessingRun::query()->create([
            'type' => 'release_queue_smoke',
            'input_digest' => hash('sha256', $token),
            'idempotency_key' => 'release-queue-smoke:'.$token,
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        try {
            FoundationSmokeJob::dispatch((string) $run->getKey())
                ->onConnection('database')
                ->onQueue((string) config('queue.connections.database.queue', 'default'));
        } catch (Throwable) {
            $run->delete();

            return $this->finish('FAIL', 'dispatch_failed', null, 0);
        }

        $started = microtime(true);
        $deadline = $started + $timeoutSeconds;

        do {
            usleep($pollMilliseconds * 1000);
            $run->refresh();
            $status = (string) $run->status;

            if ($status === 'completed') {
                return $this->finish(
                    'PASS',
                    'job_processed',
                    (string) $run->getKey(),
                    (int) $run->attempt_count,
                    microtime(true) - $started,
                );
            }

            if (in_array($status, ['failed', 'cancelled'], true)) {
                return $this->finish(
                    'FAIL',
                    'job_'.$status,
                    (string) $run->getKey(),
                    (int) $run->attempt_count,
                    microtime(true) - $started,
                );
            }
        } while (microtime(true) < $deadline);

        return $this->finish(
            'FAIL',
            'timeout',
            (string) $run->getKey(),
            (int) $run->attempt_count,
            microtime(true) - $started,
        );
    }

    private function finish(
        string $status,
        string $detail,
        ?string $processingRunId,
        int $attemptCount,
        float $durationSeconds = 0,
    ): int {
        $result = [
            'status' => $status,
            'detail' => $detail,
            'processing_run_id' => $processingRunId,
            'attempt_count' => $attemptCount,
            'duration_seconds' => round($durationSeconds, 3),
            'queue_connection' => 'database',
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line(sprintf(
                'queue_smoke=%s detail=%s attempts=%d duration=%.3fs',
                $status,
                $detail,
                $attemptCount,
                $durationSeconds,
            ));
        }

        return $status === 'PASS' ? self::SUCCESS : self::FAILURE;
    }
}
