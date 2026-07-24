<?php

namespace App\Application\Vs002;

use App\Modules\Enterprise\Application\EnterpriseBaselineService;
use App\Modules\Evidence\Application\Vs001EvidenceService;
use App\Modules\Evidence\Application\Vs002EvidenceService;
use App\Modules\Learning\Application\Vs002LearningService;
use App\Modules\Simulator\Application\Vs002SimulationService;
use Illuminate\Support\Facades\DB;
use LogicException;

final class Vs002Lifecycle
{
    public function __construct(
        private readonly Vs002SimulationService $simulation,
        private readonly EnterpriseBaselineService $enterprise,
        private readonly Vs002EvidenceService $evidence,
        private readonly Vs001EvidenceService $decisions,
        private readonly Vs002LearningService $learning,
    ) {}

    /** @return array<string,mixed> */
    public function runCase(string $caseId, int $seed, string $idempotencyKey, string $learnerActorId): array
    {
        return $this->executePlan($this->simulation->latestPlan($caseId, $seed), $idempotencyKey, $learnerActorId);
    }

    /** @return array<string,mixed> */
    public function replay(string $runId, string $idempotencyKey): array
    {
        $plan = $this->simulation->replayPlan($runId);
        $result = $this->executePlan($plan, $idempotencyKey, $plan['learner_actor_id']);
        $record = $this->simulation->recordReplay($plan['original_run_id'], (string) $result['run']['id'], $plan['original_trace_digest'], (string) $result['run']['trace_digest']);
        if (! $record['digest_match']) {
            $this->learning->recordObservedFailure($plan['learner_actor_id'], $plan['case_id'], 'replay', (string) $record['id'], $plan['policy_revision_id'], ['replay_match' => false]);
        }

        return $record;
    }

    /** @return array<string,mixed> */
    public function remediate(): array
    {
        return $this->simulation->createRemediation();
    }

    /** @return array<string,mixed> */
    public function verify(string $findingId, string $vulnerableRunId, string $remediationPolicyRevisionId, string $idempotencyKey, string $learnerActorId): array
    {
        $plan = $this->simulation->verificationPlan($vulnerableRunId, $remediationPolicyRevisionId);
        if ($plan['learner_actor_id'] !== $learnerActorId) {
            throw new LogicException('Only the learner actor who owns the vulnerable run can verify it.');
        }
        $result = $this->executePlan($plan, $idempotencyKey, $learnerActorId);
        $facts = $this->simulation->verificationFacts($vulnerableRunId, (string) $result['run']['id'], $remediationPolicyRevisionId);
        $verification = $this->evidence->completeVerification($findingId, $facts);

        return $result + ['verification' => $verification];
    }

    /**
     * @param  array<string,string>  $answer
     * @return array<string,mixed>
     */
    public function submitPractice(string $actorId, array $answer): array
    {
        return $this->learning->submitPractice($actorId, $answer);
    }

    /** @return array<string,mixed> */
    public function decideEvidence(string $evidenceId, string $decision, string $rationale, string $reviewerActorId): array
    {
        return $this->decisions->decide($evidenceId, $decision, $rationale, $reviewerActorId);
    }

    /** @return array<string,mixed> */
    public function evaluateMastery(string $actorId): array
    {
        return $this->learning->evaluateMastery($actorId, $this->evidence->acceptedForActor($actorId), $this->simulation->matchingReplayRunIds());
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
     * @param  array<string,mixed>  $plan
     * @return array<string,mixed>
     */
    private function executePlan(array $plan, string $idempotencyKey, string $learnerActorId): array
    {
        return DB::transaction(function () use ($plan, $idempotencyKey, $learnerActorId) {
            $baselineBefore = $this->enterprise->publishedRevision($plan['enterprise_baseline_revision_id']);
            $result = $this->simulation->execute($plan, $learnerActorId, $baselineBefore['snapshot_digest'], $idempotencyKey);
            $baselineAfter = $this->enterprise->publishedRevision($plan['enterprise_baseline_revision_id']);
            if (! hash_equals($baselineBefore['snapshot_digest'], $baselineAfter['snapshot_digest'])) {
                throw new LogicException('VS-002 run mutated the Enterprise Baseline.');
            }
            $evidence = $this->evidence->recordRun($learnerActorId, $result['run'], $result['trace']);
            return $result + $evidence;
        });
    }
}
