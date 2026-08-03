<?php

namespace App\Application\Vs003;

use App\Modules\Evidence\Application\Vs003EvidenceService;
use App\Modules\Learning\Application\Vs003LearningService;
use App\Modules\Simulator\Application\Vs003SimulationService;
use Illuminate\Support\Facades\DB;
use LogicException;

final class Vs003Lifecycle
{
    public function __construct(
        private readonly Vs003SimulationService $simulation,
        private readonly Vs003EvidenceService $evidence,
        private readonly Vs003LearningService $learning,
    ) {}

    /** @return array<string,mixed> */
    public function runCase(string $caseId, int $seed, string $idempotencyKey, string $actorId): array
    {
        return DB::transaction(function () use ($caseId, $seed, $idempotencyKey, $actorId): array {
            $result = $this->simulation->run($caseId, $seed, $idempotencyKey, $actorId);
            $evidence = $this->evidence->recordRun($actorId, $result['run'], $result['trace']);

            return $result + ['evidence' => $evidence];
        });
    }

    /** @return array<string,mixed> */
    public function triage(
        string $runId,
        string $actorId,
        string $outcome,
        string $rationale,
    ): array {
        return $this->simulation->triage($runId, $actorId, $outcome, $rationale);
    }

    /** @return array<string,mixed> */
    public function preserveEvidence(string $runId, string $actorId): array
    {
        $snapshot = $this->simulation->runSnapshot($runId, $actorId);
        if (
            ($snapshot['run']['outcome'] ?? null) !== 'INCIDENT_CONFIRMED'
            || ! is_array($snapshot['triage'])
            || ($snapshot['triage']['outcome'] ?? null) !== 'INCIDENT_CONFIRMED'
        ) {
            throw new LogicException('Confirmed actor-bound triage is required before custody preservation.');
        }

        return $this->evidence->preserveEvidence($runId, $actorId, $snapshot['trace']);
    }

    /** @return array<string,mixed> */
    public function proposeContainment(
        string $runId,
        string $actorId,
        string $effect,
        string $risk,
        string $rollback,
    ): array {
        $snapshot = $this->simulation->runSnapshot($runId, $actorId);
        if (! is_array($snapshot['triage'])) {
            throw new LogicException('Triage must be completed before containment is proposed.');
        }

        return $this->evidence->proposeContainment(
            $snapshot['run'],
            $snapshot['triage'],
            $actorId,
            $effect,
            $risk,
            $rollback,
        );
    }

    /** @return array<string,mixed> */
    public function approveContainment(string $proposalId, string $actorId): array
    {
        return $this->evidence->approveContainment($proposalId, $actorId);
    }

    /** @return array<string,mixed> */
    public function verifyApprovedControl(
        string $proposalId,
        string $originalRunId,
        string $actorId,
        string $idempotencyKey,
    ): array {
        return DB::transaction(function () use (
            $proposalId,
            $originalRunId,
            $actorId,
            $idempotencyKey,
        ): array {
            $control = $this->evidence->publishControl($proposalId, $actorId);
            $original = $this->simulation->runSnapshot($originalRunId, $actorId);
            $existingReplay = $this->evidence->verificationForControl(
                (string) $control['id'],
                $originalRunId,
                $actorId,
            );
            if ($existingReplay !== null) {
                return [
                    'control' => $control,
                    'verification' => $this->simulation->runSnapshot(
                        (string) $existingReplay['verification_run_id'],
                        $actorId,
                    ),
                    'replay' => $existingReplay,
                ];
            }

            $verification = $this->simulation->verifyControl(
                $originalRunId,
                $actorId,
                $control,
                $idempotencyKey,
            );
            $replay = $this->evidence->recordVerification($original, $verification, $control);

            return [
                'control' => $control,
                'verification' => $verification,
                'replay' => $replay,
            ];
        });
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
    public function evaluateMastery(string $actorId): array
    {
        return $this->learning->evaluateMastery(
            $actorId,
            $this->evidence->evidenceForActor($actorId),
            $this->simulation->masteryFacts($actorId),
            $this->evidence->masteryFacts($actorId),
        );
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        return [
            'simulation' => $this->simulation->workspace($actorId),
            'evidence' => $this->evidence->workspace($actorId),
            'learning' => $this->learning->workspace($actorId),
        ];
    }
}
