<?php

namespace App\Modules\Evidence\Application;

use App\Modules\Evidence\Models\EvidenceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class Vs003EvidenceService
{
    /**
     * @param  array<string,mixed>  $run
     * @param  array<string,mixed>  $trace
     * @return array<string,mixed>
     */
    public function recordRun(string $actorId, array $run, array $trace): array
    {
        if (
            ($run['actor_id'] ?? null) !== $actorId
            || ($trace['run_id'] ?? null) !== ($run['id'] ?? null)
            || ! hash_equals((string) ($run['trace_digest'] ?? ''), (string) ($trace['timeline_digest'] ?? ''))
        ) {
            throw new LogicException('VS-003 evidence must match the actor-bound completed run and trace.');
        }

        $existing = EvidenceRecord::query()->where('run_id', $run['id'])->first();
        if ($existing !== null) {
            if (
                (string) $existing->actor_id !== $actorId
                || $existing->origin !== 'SIMULATED'
                || ! $existing->locked
                || ! hash_equals((string) $existing->trace_digest, (string) $trace['timeline_digest'])
            ) {
                throw new LogicException('Existing VS-003 evidence conflicts with the actor-bound run.');
            }

            return $existing->toArray();
        }

        $body = [
            'origin' => 'SIMULATED',
            'actor_id' => $actorId,
            'capability_id' => config('vs003.capability_id'),
            'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
            'scenario_revision_id' => $run['scenario_revision_id'],
            'rule_set_revision_id' => $run['rule_set_revision_id'],
            'enterprise_baseline_revision_id' => $run['enterprise_baseline_revision_id'],
            'run_id' => $run['id'],
            'case_id' => $run['case_id'],
            'input_digest' => $run['input_digest'],
            'trace_digest' => $trace['timeline_digest'],
            'result' => $trace['outcome'],
            'limitations' => [
                'synthetic_dataset',
                'no_live_windows_host',
                'no_legal_admissibility',
                'no_live_containment',
            ],
            'source_claim_ids' => config('vs003.required_claim_ids'),
            'locked' => true,
        ];
        $body['content_digest'] = $this->digest($body);

        return EvidenceRecord::query()->create($body)->toArray();
    }

    /** @return list<array<string,mixed>> */
    public function evidenceForActor(string $actorId): array
    {
        return EvidenceRecord::query()
            ->where('actor_id', $actorId)
            ->where('knowledge_unit_id', config('vs003.knowledge_unit_id'))
            ->orderBy('created_at')
            ->get()
            ->map(fn (EvidenceRecord $record): array => $record->toArray())
            ->all();
    }

    /**
     * @param  array<string,mixed>  $trace
     * @return array<string,mixed>
     */
    public function preserveEvidence(string $runId, string $actorId, array $trace): array
    {
        if (($trace['run_id'] ?? null) !== $runId || ($trace['evidence_origin'] ?? null) !== 'SIMULATED') {
            throw new LogicException('Custody preservation requires the matching simulated trace.');
        }

        $existing = DB::table('vs003_custody_events')
            ->where('scenario_run_id', $runId)
            ->where('actor_id', $actorId)
            ->where('copy_kind', 'PRESERVED_ORIGINAL')
            ->first();
        if ($existing !== null) {
            if (! hash_equals((string) $existing->source_digest, (string) $trace['timeline_digest'])) {
                throw new LogicException('Existing custody evidence conflicts with the trace digest.');
            }

            return (array) $existing;
        }

        $sourceIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $event): mixed => is_array($event) ? ($event['id'] ?? null) : null,
            is_array($trace['events'] ?? null) ? $trace['events'] : [],
        ), 'is_string')));
        sort($sourceIds, SORT_STRING);
        $digest = $this->digest([
            'run_id' => $runId,
            'actor_id' => $actorId,
            'source_event_ids' => $sourceIds,
            'source_digest' => $trace['timeline_digest'],
            'copy_kind' => 'PRESERVED_ORIGINAL',
        ]);
        $row = [
            'id' => (string) Str::uuid7(),
            'scenario_run_id' => $runId,
            'actor_id' => $actorId,
            'origin' => 'SIMULATED',
            'source_event_ids' => json_encode($sourceIds, JSON_THROW_ON_ERROR),
            'source_digest' => $trace['timeline_digest'],
            'collected_at' => now(),
            'storage_reference' => "simulated://vs003/{$runId}/preserved-original",
            'copy_kind' => 'PRESERVED_ORIGINAL',
            'limitations' => json_encode([
                'synthetic_only',
                'not_legal_admissibility',
                'working_copy_not_created',
            ], JSON_THROW_ON_ERROR),
            'digest' => $digest,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('vs003_custody_events')->insert($row);

        return (array) DB::table('vs003_custody_events')->where('id', $row['id'])->first();
    }

    /**
     * @param  array<string,mixed>  $run
     * @param  array<string,mixed>  $triage
     * @return array<string,mixed>
     */
    public function proposeContainment(
        array $run,
        array $triage,
        string $actorId,
        string $expectedEffect,
        string $risk,
        string $rollback,
    ): array {
        if (
            ($run['actor_id'] ?? null) !== $actorId
            || ($run['outcome'] ?? null) !== 'INCIDENT_CONFIRMED'
            || ($triage['actor_id'] ?? null) !== $actorId
            || ($triage['scenario_run_id'] ?? null) !== ($run['id'] ?? null)
            || ($triage['outcome'] ?? null) !== 'INCIDENT_CONFIRMED'
        ) {
            throw new LogicException('Actor-bound confirmed triage is required before containment can be proposed.');
        }

        if (! DB::table('vs003_custody_events')
            ->where('scenario_run_id', $run['id'])
            ->where('actor_id', $actorId)
            ->where('copy_kind', 'PRESERVED_ORIGINAL')
            ->exists()) {
            throw new LogicException('Preserved actor-bound evidence is required before containment is proposed.');
        }

        $normalized = [
            'expected_effect' => $this->boundedText($expectedEffect, 'Expected effect'),
            'risk' => $this->boundedText($risk, 'Risk'),
            'rollback_condition' => $this->boundedText($rollback, 'Rollback condition'),
        ];
        $proposalDigest = $this->digest([
            'run_id' => $run['id'],
            'actor_id' => $actorId,
            'triage_record_id' => $triage['id'],
            ...$normalized,
            'live_action' => false,
        ]);
        $existing = DB::table('vs003_containment_proposals')
            ->where('scenario_run_id', $run['id'])
            ->where('actor_id', $actorId)
            ->first();
        if ($existing !== null) {
            if (! hash_equals((string) $existing->digest, $proposalDigest)) {
                throw new LogicException('Existing containment proposal conflicts with the requested bounded proposal.');
            }

            return (array) $existing;
        }

        $row = [
            'id' => (string) Str::uuid7(),
            'scenario_run_id' => $run['id'],
            'actor_id' => $actorId,
            'triage_record_id' => $triage['id'],
            'state' => 'PROPOSED',
            'proposal_type' => 'disable_synthetic_remote_path',
            ...$normalized,
            'digest' => $proposalDigest,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('vs003_containment_proposals')->insert($row);

        return (array) DB::table('vs003_containment_proposals')->where('id', $row['id'])->first();
    }

    /** @return array<string,mixed> */
    public function approveContainment(string $proposalId, string $actorId): array
    {
        return DB::transaction(function () use ($proposalId, $actorId): array {
            $proposal = DB::table('vs003_containment_proposals')
                ->where('id', $proposalId)
                ->lockForUpdate()
                ->first();
            if ($proposal === null || (string) $proposal->actor_id !== $actorId) {
                throw new LogicException('The containment proposal is actor-bound.');
            }
            if ($proposal->state === 'APPROVED') {
                if ((string) $proposal->approved_by !== $actorId) {
                    throw new LogicException('The containment approval belongs to another actor.');
                }

                return (array) $proposal;
            }
            if ($proposal->state !== 'PROPOSED') {
                throw new LogicException('Only a proposed containment record can be approved.');
            }

            DB::table('vs003_containment_proposals')->where('id', $proposalId)->update([
                'state' => 'APPROVED',
                'approved_by' => $actorId,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

            return (array) DB::table('vs003_containment_proposals')->where('id', $proposalId)->first();
        });
    }

    /** @return array<string,mixed> */
    public function publishControl(string $proposalId, string $actorId): array
    {
        return DB::transaction(function () use ($proposalId, $actorId): array {
            $proposal = DB::table('vs003_containment_proposals')
                ->where('id', $proposalId)
                ->lockForUpdate()
                ->first();
            if (
                $proposal === null
                || $proposal->state !== 'APPROVED'
                || (string) $proposal->actor_id !== $actorId
                || (string) $proposal->approved_by !== $actorId
                || $proposal->triage_record_id === null
            ) {
                throw new LogicException('An actor-bound approved containment proposal is required.');
            }

            $existing = DB::table('vs003_control_revisions')->where('proposal_id', $proposalId)->first();
            if ($existing !== null) {
                if ((string) $existing->actor_id !== $actorId) {
                    throw new LogicException('Existing control revision belongs to another actor.');
                }

                return (array) $existing;
            }

            $controlId = 'CTRL-VS003-AUTH-PATH';
            $latest = DB::table('vs003_control_revisions')
                ->where('control_id', $controlId)
                ->orderByDesc('revision')
                ->lockForUpdate()
                ->first();
            $latestRevision = $latest === null ? 0 : (int) $latest->revision;
            $definition = [
                'behavior_version' => 'vs003_auth_path_control_v1',
                'proposal_id' => $proposalId,
                'triage_record_id' => $proposal->triage_record_id,
                'effect' => $proposal->expected_effect,
                'risk' => $proposal->risk,
                'rollback' => $proposal->rollback_condition,
                'origin' => 'SIMULATED',
                'live_action' => false,
            ];
            $row = [
                'id' => (string) Str::uuid7(),
                'control_id' => $controlId,
                'revision' => $latestRevision + 1,
                'state' => 'published',
                'actor_id' => $actorId,
                'proposal_id' => $proposalId,
                'triage_record_id' => $proposal->triage_record_id,
                'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
                'digest' => $this->digest($definition),
                'remediates_run_id' => $proposal->scenario_run_id,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('vs003_control_revisions')->insert($row);

            return (array) DB::table('vs003_control_revisions')->where('id', $row['id'])->first();
        });
    }

    /**
     * @param  array<string,mixed>  $original
     * @param  array<string,mixed>  $verification
     * @param  array<string,mixed>  $control
     * @return array<string,mixed>
     */
    public function recordVerification(array $original, array $verification, array $control): array
    {
        $actorId = (string) ($original['run']['actor_id'] ?? '');
        if (
            $actorId === ''
            || ($verification['run']['actor_id'] ?? null) !== $actorId
            || ($control['actor_id'] ?? null) !== $actorId
            || ($verification['run']['verification_of_run_id'] ?? null) !== ($original['run']['id'] ?? null)
            || ($control['remediates_run_id'] ?? null) !== ($original['run']['id'] ?? null)
            || ($verification['trace']['verification']['control_revision_id'] ?? null) !== ($control['id'] ?? null)
            || ($verification['trace']['verification']['original_timeline_digest'] ?? null) !== ($original['trace']['timeline_digest'] ?? null)
        ) {
            throw new LogicException('Verification replay does not match its actor, original run, and pinned control.');
        }

        $existing = DB::table('vs003_verification_replays')
            ->where('verification_run_id', $verification['run']['id'])
            ->first();
        if ($existing !== null) {
            if (
                (string) $existing->actor_id !== $actorId
                || (string) $existing->original_run_id !== (string) $original['run']['id']
                || (string) $existing->control_revision_id !== (string) $control['id']
                || (string) $existing->triage_record_id !== (string) $control['triage_record_id']
                || ! hash_equals((string) $existing->original_timeline_digest, (string) $original['trace']['timeline_digest'])
                || ! hash_equals((string) $existing->verification_timeline_digest, (string) $verification['trace']['timeline_digest'])
            ) {
                throw new LogicException('Existing verification replay conflicts with this request.');
            }

            return (array) $existing;
        }

        $passed = ($verification['run']['outcome'] ?? null) === 'BENIGN_EXPLAINED'
            && ($verification['trace']['verification']['result'] ?? null) === 'CONTROL_EFFECT_OBSERVED_IN_SYNTHETIC_REPLAY';
        $payload = [
            'actor_id' => $actorId,
            'original_run_id' => $original['run']['id'],
            'verification_run_id' => $verification['run']['id'],
            'control_revision_id' => $control['id'],
            'triage_record_id' => $control['triage_record_id'],
            'original_timeline_digest' => $original['trace']['timeline_digest'],
            'verification_timeline_digest' => $verification['trace']['timeline_digest'],
            'passed' => $passed,
        ];
        $row = [
            'id' => (string) Str::uuid7(),
            ...$payload,
            'digest' => $this->digest($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('vs003_verification_replays')->insert($row);

        return (array) DB::table('vs003_verification_replays')->where('id', $row['id'])->first();
    }

    /** @return array<string,mixed>|null */
    public function verificationForControl(string $controlId, string $originalRunId, string $actorId): ?array
    {
        $record = DB::table('vs003_verification_replays')
            ->where('control_revision_id', $controlId)
            ->where('original_run_id', $originalRunId)
            ->where('actor_id', $actorId)
            ->first();

        return $record === null ? null : (array) $record;
    }

    /** @return array<string,mixed>|null */
    public function latestPassedVerificationForActor(string $actorId): ?array
    {
        $record = DB::table('vs003_verification_replays')
            ->where('actor_id', $actorId)
            ->where('passed', true)
            ->latest('created_at')
            ->first();

        return $record === null ? null : (array) $record;
    }

    /** @return array<string,mixed> */
    public function masteryFacts(string $actorId): array
    {
        $control = DB::table('vs003_control_revisions')
            ->where('actor_id', $actorId)
            ->where('state', 'published')
            ->orderByDesc('published_at')
            ->first();
        $proposal = $control === null
            ? null
            : DB::table('vs003_containment_proposals')
                ->where('id', $control->proposal_id)
                ->where('actor_id', $actorId)
                ->where('state', 'APPROVED')
                ->where('approved_by', $actorId)
                ->first();
        $controlChainValid = $control !== null
            && $proposal !== null
            && (string) $control->remediates_run_id === (string) $proposal->scenario_run_id
            && (string) $control->triage_record_id === (string) $proposal->triage_record_id;
        $custodyPreserved = $controlChainValid && DB::table('vs003_custody_events')
            ->where('scenario_run_id', $control->remediates_run_id)
            ->where('actor_id', $actorId)
            ->where('copy_kind', 'PRESERVED_ORIGINAL')
            ->exists();
        $verificationRecord = $controlChainValid
            ? DB::table('vs003_verification_replays')
                ->where('actor_id', $actorId)
                ->where('original_run_id', $control->remediates_run_id)
                ->where('control_revision_id', $control->id)
                ->where('triage_record_id', $control->triage_record_id)
                ->where('passed', true)
                ->latest('created_at')
                ->first()
            : null;
        $verification = $verificationRecord === null ? null : (array) $verificationRecord;

        return [
            'custody_preserved' => $custodyPreserved,
            'approved_containment' => $controlChainValid,
            'published_control' => $controlChainValid,
            'verification_passed' => $verification !== null,
            'verification' => $verification,
        ];
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        $evidence = $this->evidenceForActor($actorId);
        $custody = DB::table('vs003_custody_events')->where('actor_id', $actorId)->latest()->get();
        $proposals = DB::table('vs003_containment_proposals')->where('actor_id', $actorId)->latest()->get();
        $controls = DB::table('vs003_control_revisions')->where('actor_id', $actorId)->latest()->get();
        $replays = DB::table('vs003_verification_replays')->where('actor_id', $actorId)->latest()->get();

        return [
            'evidence' => $evidence,
            'custody' => $custody->map(fn (object $row): array => (array) $row)->all(),
            'proposals' => $proposals->map(fn (object $row): array => (array) $row)->all(),
            'controls' => $controls->map(fn (object $row): array => (array) $row)->all(),
            'verification_replays' => $replays->map(fn (object $row): array => (array) $row)->all(),
        ];
    }

    private function boundedText(string $value, string $label): string
    {
        $normalized = mb_substr(trim($value), 0, 500);
        if (mb_strlen($normalized) < 12) {
            throw new LogicException($label.' must contain at least 12 characters.');
        }

        return $normalized;
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode(
            $this->canonical($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
        }
        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
    }
}
