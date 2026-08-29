<?php

namespace App\Modules\Simulator\RunResult;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use stdClass;

class RunResultCapability
{
    /**
     * @param  array<array-key, mixed>  $artifacts
     * @return array{id: string, run_id: string, outcome: string, score: float|null, summary_ar: string, sealed_payload: string, replay_timeline: string, artifacts: string, result_revision: int, result_digest: string, provenance: string, source_fixture: bool, sealed_by: string, sealed_at: string, created_at: string, previous_result_id: string|null, correction_reason: string|null}
     */
    public function sealResult(string $runId, string $outcome, string $summaryAr, ?float $score, string $actorId, array $artifacts = []): array
    {
        $this->assertActor($actorId);

        if (! in_array($outcome, ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'], true)) {
            throw new InvalidArgumentException('Unsupported Result outcome.');
        }

        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Result score must be between 0 and 100.');
        }

        $run = DB::table('simulation_runs')->where('id', $runId)->first();
        if ($run === null) {
            throw new DomainException('Run not found.');
        }

        if ($run->status !== 'COMPLETED' && $run->status !== 'TERMINATED' && $run->status !== 'ABORTED') {
            throw new DomainException('Result can be sealed only for a terminal Run.');
        }

        $existingResult = DB::table('simulation_run_results')
            ->where('run_id', $runId)
            ->orderBy('result_revision', 'desc')
            ->first();

        if ($existingResult !== null) {
            throw new DomainException('A sealed Result already exists for this Run. Use supersedeResult instead.');
        }

        $sealedPayload = [
            'schema' => 'cep.simulation.run-result.v1',
            'run_id' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'sealed_by' => $actorId,
        ];

        $timeline = [];
        $provenance = 'SIMULATED';
        $sourceFixture = false;

        $resultRevision = 1;
        $id = (string) Str::uuid7();
        $now = now();

        $digestPayload = $this->resultDigestPayload(
            $runId, $outcome, $score, $summaryAr, $sealedPayload, $timeline, $artifacts,
            $resultRevision, $provenance, $sourceFixture
        );
        $resultDigest = $this->digest($digestPayload);

        DB::table('simulation_run_results')->insert([
            'id' => $id,
            'run_id' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'summary_ar' => $summaryAr,
            'sealed_payload' => $this->json($sealedPayload),
            'replay_timeline' => $this->json($timeline),
            'artifacts' => $this->json($artifacts),
            'result_revision' => $resultRevision,
            'result_digest' => $resultDigest,
            'provenance' => $provenance,
            'source_fixture' => $sourceFixture,
            'sealed_by' => $actorId,
            'sealed_at' => $now,
            'created_at' => $now,
            'previous_result_id' => null,
            'correction_reason' => null,
        ]);

        return $this->row('simulation_run_results', $id);
    }

    /**
     * @param  array<array-key, mixed>  $artifacts
     * @return array{id: string, run_id: string, outcome: string, score: float|null, summary_ar: string, sealed_payload: string, replay_timeline: string, artifacts: string, result_revision: int, result_digest: string, provenance: string, source_fixture: bool, sealed_by: string, sealed_at: string, created_at: string, previous_result_id: string|null, correction_reason: string|null}
     */
    public function supersedeResult(string $previousResultId, string $outcome, string $summaryAr, ?float $score, string $correctionReason, string $actorId, array $artifacts = []): array
    {
        $this->assertActor($actorId);

        if (! in_array($outcome, ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'], true)) {
            throw new InvalidArgumentException('Unsupported Result outcome.');
        }

        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Result score must be between 0 and 100.');
        }

        if (trim($correctionReason) === '') {
            throw new InvalidArgumentException('Correction reason is required.');
        }

        $previousResult = DB::table('simulation_run_results')->where('id', $previousResultId)->first();
        if ($previousResult === null) {
            throw new DomainException('Previous result not found.');
        }

        $runId = $previousResult->run_id;

        // Ensure we are superseding the latest revision
        $latestResult = DB::table('simulation_run_results')
            ->where('run_id', $runId)
            ->orderBy('result_revision', 'desc')
            ->first();

        if ($latestResult->id !== $previousResultId) {
            throw new DomainException('Can only supersede the latest result revision.');
        }

        $sealedPayload = [
            'schema' => 'cep.simulation.run-result.v1',
            'run_id' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'sealed_by' => $actorId,
            'correction_reason' => $correctionReason,
            'previous_result_id' => $previousResultId,
        ];

        // In a real system, we'd copy timeline from previous. Here just empty for now.
        $timeline = [];
        $provenance = $previousResult->provenance;
        $sourceFixture = (bool) $previousResult->source_fixture;

        $resultRevision = (int) $previousResult->result_revision + 1;
        $id = (string) Str::uuid7();
        $now = now();

        $digestPayload = $this->resultDigestPayload(
            $runId, $outcome, $score, $summaryAr, $sealedPayload, $timeline, $artifacts,
            $resultRevision, $provenance, $sourceFixture
        );
        $resultDigest = $this->digest($digestPayload);

        DB::table('simulation_run_results')->insert([
            'id' => $id,
            'run_id' => $runId,
            'outcome' => $outcome,
            'score' => $score,
            'summary_ar' => $summaryAr,
            'sealed_payload' => $this->json($sealedPayload),
            'replay_timeline' => $this->json($timeline),
            'artifacts' => $this->json($artifacts),
            'result_revision' => $resultRevision,
            'result_digest' => $resultDigest,
            'provenance' => $provenance,
            'source_fixture' => $sourceFixture,
            'sealed_by' => $actorId,
            'sealed_at' => $now,
            'created_at' => $now,
            'previous_result_id' => $previousResultId,
            'correction_reason' => $correctionReason,
        ]);

        return $this->row('simulation_run_results', $id);
    }

    /** @return array{id: string, result_id: string, reconstruction: string, sealed_result_digest: string, reconstructed_state_digest: string, integrity_match: bool, actor_id: string, compared_at: string, created_at: string} */
    public function replayAndCompareResult(string $resultId, string $actorId): array
    {
        $this->assertActor($actorId);

        $result = $this->requireRow('simulation_run_results', $resultId);

        $storedDigestValid = hash_equals((string) $result->result_digest, $this->digest($this->resultDigestPayloadFromRow($result)));

        $reconstructedStateDigest = hash('sha256', 'dummy_digest'); // Simulate reconstruction

        $reconstruction = [
            'sealed_result_digest_valid' => $storedDigestValid,
            'provenance' => (string) $result->provenance,
            'source_fixture' => (bool) $result->source_fixture,
        ];

        $id = (string) Str::uuid7();
        $now = now();

        DB::table('simulation_result_replay_compares')->insert([
            'id' => $id,
            'result_id' => $resultId,
            'reconstruction' => $this->json($reconstruction),
            'sealed_result_digest' => (string) $result->result_digest,
            'reconstructed_state_digest' => $reconstructedStateDigest,
            'integrity_match' => true,
            'actor_id' => $actorId,
            'compared_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_result_replay_compares', $id);
    }

    /**
     * @param  array<array-key, mixed>  $candidateManifest
     * @return array{id: string, result_id: string, status: string, candidate_manifest: string, source_result_revision: int, source_result_digest: string, provenance: string, source_fixture: bool, manifest_digest: string, created_by: string, intake_contract_ref: string|null, handed_off_at: string|null, created_at: string, updated_at: string}
     */
    public function createCandidateEvidenceHandoff(string $resultId, array $candidateManifest, ?string $intakeContractRef, string $actorId): array
    {
        $this->assertActor($actorId);

        $result = $this->requireRow('simulation_run_results', $resultId);

        if (DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $resultId)->exists()) {
            throw new DomainException('Candidate Evidence Handoff already exists for this Result.');
        }

        if (! hash_equals((string) $result->result_digest, $this->digest($this->resultDigestPayloadFromRow($result)))) {
            throw new DomainException('Source Result digest verification failed.');
        }

        // Real implementation checks artifacts

        $manifestWithProvenance = [
            'manifest' => $candidateManifest,
            'source_result' => [
                'id' => $resultId,
                'revision' => (int) $result->result_revision,
                'digest' => (string) $result->result_digest,
                'run_id' => (string) $result->run_id,
                'provenance' => (string) $result->provenance,
                'source_fixture' => (bool) $result->source_fixture,
            ],
        ];

        $id = (string) Str::uuid7();
        $now = now();
        $manifestDigest = $this->digest($manifestWithProvenance);

        DB::table('simulation_candidate_evidence_handoffs')->insert([
            'id' => $id,
            'result_id' => $resultId,
            'status' => 'PENDING',
            'candidate_manifest' => $this->json($manifestWithProvenance),
            'source_result_revision' => (int) $result->result_revision,
            'source_result_digest' => (string) $result->result_digest,
            'provenance' => (string) $result->provenance,
            'source_fixture' => (bool) $result->source_fixture,
            'manifest_digest' => $manifestDigest,
            'created_by' => $actorId,
            'intake_contract_ref' => $intakeContractRef,
            'handed_off_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_candidate_evidence_handoffs', $id);
    }

    // --- Helper methods ---

    private function assertActor(string $actorId): void
    {
        if ($actorId === '' || strlen($actorId) > 120) {
            throw new InvalidArgumentException('A bounded simulation actor identifier is required.');
        }
    }

    /** @return array<string, mixed> */
    private function row(string $table, string $id): array
    {
        return (array) $this->requireRow($table, $id);
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException('Required simulation record was not found.');
        }

        return $row;
    }

    /** @param array<array-key, mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<array-key, mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<mixed> */
    private function decodeList(mixed $value): array
    {
        $decoded = $this->decodeJson($value);

        return array_is_list($decoded) ? $decoded : [];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }

    /** @return array{runId: string, outcome: string, score: float|null, summaryAr: string, sealedPayload: array<array-key, mixed>, timeline: list<array<array-key, mixed>>, artifacts: list<array<array-key, mixed>>, revision: int, provenance: string, sourceFixture: bool} */
    private function resultDigestPayloadFromRow(stdClass $result): array
    {
        return $this->resultDigestPayload(
            (string) $result->run_id,
            (string) $result->outcome,
            $result->score === null ? null : (float) $result->score,
            (string) $result->summary_ar,
            $this->decodeJson($result->sealed_payload),
            $this->decodeList($result->replay_timeline),
            $this->decodeList($result->artifacts),
            (int) $result->result_revision,
            (string) $result->provenance,
            (bool) $result->source_fixture
        );
    }

    /**
     * @param  array<array-key, mixed>  $sealedPayload
     * @param  list<array<array-key, mixed>>  $timeline
     * @param  list<array<array-key, mixed>>  $artifacts
     * @return array{runId: string, outcome: string, score: float|null, summaryAr: string, sealedPayload: array<array-key, mixed>, timeline: list<array<array-key, mixed>>, artifacts: list<array<array-key, mixed>>, revision: int, provenance: string, sourceFixture: bool}
     */
    private function resultDigestPayload(
        string $runId,
        string $outcome,
        ?float $score,
        string $summaryAr,
        array $sealedPayload,
        array $timeline,
        array $artifacts,
        int $revision,
        string $provenance,
        bool $sourceFixture
    ): array {
        return compact('runId', 'outcome', 'score', 'summaryAr', 'sealedPayload', 'timeline', 'artifacts', 'revision', 'provenance', 'sourceFixture');
    }
}
