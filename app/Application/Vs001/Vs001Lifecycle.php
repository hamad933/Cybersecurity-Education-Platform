<?php

namespace App\Application\Vs001;

use App\Modules\Enterprise\Application\EnterpriseBaselineService;
use App\Modules\Evidence\Application\Vs001EvidenceService;
use App\Modules\Learning\Application\Vs001LearningService;
use App\Modules\Platform\Messaging\OutboxPublisher;
use App\Modules\Simulator\Application\Vs001SimulationService;
use Illuminate\Support\Facades\DB;
use LogicException;

final class Vs001Lifecycle
{
    public function __construct(
        private readonly Vs001SimulationService $simulation,
        private readonly EnterpriseBaselineService $enterprise,
        private readonly Vs001EvidenceService $evidence,
        private readonly Vs001LearningService $learning,
        private readonly OutboxPublisher $outbox,
    ) {}

    /** @return array{run:array<string,mixed>,trace:array<string,mixed>,evidence:array<string,mixed>} */
    public function runCase(string $caseId, int $seed, string $idempotencyKey, string $actorId): array
    {
        $plan = $this->simulation->latestPlan($caseId, $seed);

        return $this->executePlan($plan, $idempotencyKey, $actorId);
    }

    /** @return array<string,mixed> */
    public function replay(string $originalRunId, string $idempotencyKey): array
    {
        $plan = $this->simulation->replayPlan($originalRunId);
        $result = $this->executePlan($plan, $idempotencyKey, $plan['actor_id']);
        $record = $this->simulation->recordReplay(
            $plan['original_run_id'],
            (string) $result['run']['id'],
            $plan['original_trace_digest'],
            (string) $result['run']['trace_digest'],
        );
        if (! $record['digest_match']) {
            $this->learning->recordObservedFailure(
                $plan['actor_id'],
                $plan['case_id'],
                'replay',
                (string) $record['id'],
                $plan['rule_set_revision_id'],
                ['replay_match' => false],
            );
        }

        return $record;
    }

    /**
     * @param  array<string,mixed>  $proposal
     * @return array<string,mixed>
     */
    public function proposeImprovement(string $runId, string $baselineRevisionId, array $proposal): array
    {
        return $this->enterprise->proposeImprovement($baselineRevisionId, $runId, $proposal);
    }

    /** @return array<string,mixed> */
    public function decideEvidence(string $evidenceId, string $decision, string $rationale, string $reviewerActorId): array
    {
        return $this->evidence->decide($evidenceId, $decision, $rationale, $reviewerActorId);
    }

    /** @return array<string,mixed> */
    public function evaluateMastery(string $actorId): array
    {
        return $this->learning->evaluateMastery(
            $actorId,
            $this->evidence->acceptedForActor($actorId, config('vs001.knowledge_unit_id')),
            $this->simulation->matchingReplayRunIds(),
        );
    }

    /**
     * @param  array{selected_outcome:string,decisive_step_id:string,decisive_ace_id:string,relevant_requested_mask:string,remaining_mask:string,rationale:string}  $answer
     * @return array{attempt:array<string,mixed>,failure_class:?string}
     */
    public function submitPractice(string $actorId, array $answer): array
    {
        return $this->learning->submitPractice($actorId, $answer);
    }

    /**
     * @param  array<string,mixed>  $observation
     * @return array<string,mixed>|null
     */
    public function recordObservedFailure(string $actorId, string $caseId, string $sourceType, string $sourceId, string $ruleRevisionId, array $observation): ?array
    {
        return $this->learning->recordObservedFailure($actorId, $caseId, $sourceType, $sourceId, $ruleRevisionId, $observation);
    }

    /**
     * @param  array{scenario_revision_id:string,rule_set_revision_id:string,enterprise_baseline_revision_id:string,case_id:string,seed:int,ordered_actions:list<string>,input:array<string,mixed>,input_digest:string,request_digest:string}  $plan
     * @return array{run:array<string,mixed>,trace:array<string,mixed>,evidence:array<string,mixed>}
     */
    private function executePlan(array $plan, string $idempotencyKey, string $actorId): array
    {
        return DB::transaction(function () use ($plan, $idempotencyKey, $actorId): array {
            $baselineBefore = $this->enterprise->publishedRevision($plan['enterprise_baseline_revision_id']);
            $result = $this->simulation->execute($plan, $actorId, $baselineBefore['snapshot_digest'], $idempotencyKey);
            $baselineAfter = $this->enterprise->publishedRevision($plan['enterprise_baseline_revision_id']);
            if (! hash_equals($baselineBefore['snapshot_digest'], $baselineAfter['snapshot_digest'])) {
                throw new LogicException('Enterprise baseline changed during an isolated run.');
            }
            $evidence = $this->evidence->recordRunEvidence($actorId, $result['run'], $result['trace']);
            $this->outbox->publish('vs001.scenario.completed.v1', 'MOD-SIM', "{$idempotencyKey}:completed", (string) $result['run']['id'], [
                'run_id' => $result['run']['id'],
                'actor_id' => $actorId,
                'outcome' => $result['run']['outcome'],
                'trace_digest' => $result['run']['trace_digest'],
                'origin' => 'SIMULATED',
            ]);

            return $result + ['evidence' => $evidence];
        });
    }
}
