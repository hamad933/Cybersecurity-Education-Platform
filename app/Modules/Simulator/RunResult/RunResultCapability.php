<?php

declare(strict_types=1);

namespace App\Modules\Simulator\RunResult;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class RunResultCapability
{
    /**
     * Projects the Replay state reading ONLY from canonical sealed history. (Zero-write projection)
     * Reconstructs the state strictly via timeline events and governed CEP_INTERNAL_OPERATION_V1 semantics.
     *
     * @param string $revisionId
     * @return string
     */
    public function projectReplayState(string $revisionId): string
    {
        $revision = DB::table('simulation_run_result_revisions')->where('id', $revisionId)->first();
        if (!$revision) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Effective revision not found.');
        }

        $canonicalResult = DB::table('simulation_run_results')->where('id', $revision->result_id)->first();
        if (!$canonicalResult || !$canonicalResult->sealed_payload) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Sealed canonical history not found.');
        }
        
        $this->verifyCanonicalResultDigest($canonicalResult);

        $sealedPayload = json_decode($canonicalResult->sealed_payload, true, 512, JSON_THROW_ON_ERROR);
        
        if (!isset($sealedPayload['operations']) || !is_array($sealedPayload['operations']) || !array_is_list($sealedPayload['operations'])) {
            throw new InvalidArgumentException('Canonical operation history must be a valid list.');
        }

        $timeline = json_decode($canonicalResult->replay_timeline, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($timeline)) {
            $timeline = [];
        }

        $this->validateTimelineSequence($timeline);
        
        $appliedOperations = $this->extractAppliedOperations($timeline, $sealedPayload['operations']);
        
        // Removed the synthetic logic attempting to decode MFA booleans and inventing state.
        // Bounded projection guarantees sequence/timeline integrity but delegates full engine simulation
        return 'REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED';
    }
    
    /**
     * Projects the AAR state reading ONLY from canonical sealed history. (Zero-write projection)
     *
     * @param string $revisionId
     * @return array{final_state: string, operation_count: int, state_change_count: string, outcome: string|null, score: string|null, summary_ar: string|null}
     */
    public function projectAarState(string $revisionId): array
    {
        $revision = DB::table('simulation_run_result_revisions')->where('id', $revisionId)->first();
        if (!$revision) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Effective revision not found.');
        }

        $canonicalResult = DB::table('simulation_run_results')->where('id', $revision->result_id)->first();
        if (!$canonicalResult || !$canonicalResult->sealed_payload) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Sealed canonical history not found.');
        }

        $this->verifyCanonicalResultDigest($canonicalResult);

        $sealedPayload = json_decode($canonicalResult->sealed_payload, true, 512, JSON_THROW_ON_ERROR);
        
        if (!isset($sealedPayload['operations']) || !is_array($sealedPayload['operations']) || !array_is_list($sealedPayload['operations'])) {
            throw new InvalidArgumentException('Canonical operation history must be a valid list.');
        }
        
        $timeline = json_decode($canonicalResult->replay_timeline, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($timeline)) {
            $timeline = [];
        }

        $this->validateTimelineSequence($timeline);

        $appliedOperations = $this->extractAppliedOperations($timeline, $sealedPayload['operations']);

        return [
            'final_state' => 'REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED',
            'operation_count' => count($appliedOperations),
            'state_change_count' => 'REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED',
            'outcome' => $revision->outcome,
            'score' => $revision->score !== null ? (string) $revision->score : null,
            'summary_ar' => $revision->summary_ar,
        ];
    }
    
    /**
     * Compares multiple distinct sealed Results/Runs (Zero-write projection)
     *
     * @param array<int, string> $revisionIds
     * @return array<string, array{final_state: string, outcome: string|null, score: string|null}>
     */
    public function projectCompareRuns(array $revisionIds): array
    {
        if (count($revisionIds) < 2) {
            throw new InvalidArgumentException('Must compare two or more distinct canonical Results/Runs.');
        }
        if (count(array_unique($revisionIds)) !== count($revisionIds)) {
            throw new InvalidArgumentException('Cannot compare duplicate Run Revisions.');
        }
        
        $canonicalResultIds = [];
        $canonicalRunIds = [];
        $results = [];
        
        foreach ($revisionIds as $revisionId) {
            $revision = DB::table('simulation_run_result_revisions')->where('id', $revisionId)->first();
            if (!$revision) {
                throw new RuntimeException('DEPENDENCY_REQUIRED: Effective revision not found.');
            }
            $canonicalResult = DB::table('simulation_run_results')->where('id', $revision->result_id)->first();
            if (!$canonicalResult) {
                throw new RuntimeException('DEPENDENCY_REQUIRED: Canonical result missing.');
            }
            
            $canonicalResultIds[] = $revision->result_id;
            $canonicalRunIds[] = $canonicalResult->run_id;
            
            $aar = $this->projectAarState($revisionId);
            $results[$revisionId] = [
                'final_state' => $aar['final_state'],
                'outcome' => $aar['outcome'],
                'score' => $aar['score']
            ];
        }
        
        if (count(array_unique($canonicalResultIds)) !== count($revisionIds) || count(array_unique($canonicalRunIds)) !== count($revisionIds)) {
            throw new InvalidArgumentException('Must compare two or more distinct canonical Results/Runs.');
        }
        
        return $results;
    }
    
    /**
     * Creates an immutable additive result revision. Mutates ONLY allowed derived fields.
     * Operations are anchored to the canonical run result and are NOT overwritten.
     *
     * @param string $resultId Canonical ID from simulation_run_results
     * @param array<string, mixed> $derivedFields
     * @param string|null $actorIdentity
     * @param string|null $baseRevisionId
     * @param string|null $correctionReason
     * @return string
     */
    public function createResultRevision(
        string $resultId,
        array $derivedFields,
        ?string $actorIdentity = null,
        ?string $baseRevisionId = null,
        ?string $correctionReason = null
    ): string {
        $canonicalResult = DB::table('simulation_run_results')->where('id', $resultId)->first();
        if (!$canonicalResult) {
            throw new InvalidArgumentException('Canonical result lineage does not exist.');
        }
        
        $this->verifyCanonicalResultDigest($canonicalResult);

        if ($actorIdentity !== null) {
            $actorIdentity = trim($actorIdentity);
            if ($actorIdentity === '') {
                throw new InvalidArgumentException('Actor identity cannot be blank.');
            }
            if (mb_strlen($actorIdentity) > 120) {
                throw new InvalidArgumentException('Actor identity cannot exceed 120 characters.');
            }
        }
        
        $allowedKeys = ['outcome', 'score', 'summary_ar'];
        foreach (array_keys($derivedFields) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new InvalidArgumentException("Unsupported derived field key: {$key}");
            }
        }
        
        $effectivePayload = [
            'outcome' => $canonicalResult->outcome,
            'score' => $canonicalResult->score === null ? null : (float) $canonicalResult->score,
            'summary_ar' => $canonicalResult->summary_ar,
        ];
        
        if ($baseRevisionId !== null) {
            $baseRevision = DB::table('simulation_run_result_revisions')->where('id', $baseRevisionId)->first();
            if (!$baseRevision) {
                throw new InvalidArgumentException('Base revision does not exist.');
            }
            if ($baseRevision->result_id !== $resultId) {
                throw new InvalidArgumentException('Base revision belongs to a different canonical result lineage.');
            }
            
            if ($correctionReason !== null) {
                $correctionReason = trim($correctionReason);
            }
            if (empty($correctionReason)) {
                throw new InvalidArgumentException('Correction reason is required when appending a superseding revision.');
            }
            
            $effectivePayload['outcome'] = $baseRevision->outcome;
            $effectivePayload['score'] = $baseRevision->score !== null ? (float) $baseRevision->score : null;
            $effectivePayload['summary_ar'] = $baseRevision->summary_ar;
        }
        
        if (array_key_exists('outcome', $derivedFields)) {
            $outcome = $derivedFields['outcome'];
            if ($outcome !== null) {
                $allowedOutcomes = ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'];
                if (!in_array($outcome, $allowedOutcomes, true)) {
                    throw new InvalidArgumentException("Unsupported Result outcome: {$outcome}");
                }
            }
            $effectivePayload['outcome'] = $outcome;
        }
        if (array_key_exists('score', $derivedFields)) {
            if ($derivedFields['score'] !== null && ($derivedFields['score'] < 0 || $derivedFields['score'] > 100)) {
                throw new InvalidArgumentException('Result score must be between 0 and 100.');
            }
            $effectivePayload['score'] = $derivedFields['score'];
        }
        if (array_key_exists('summary_ar', $derivedFields)) {
            $effectivePayload['summary_ar'] = $derivedFields['summary_ar'];
        }
        
        $canonicalizedDerived = $this->canonicalizeJson(json_encode($effectivePayload, JSON_THROW_ON_ERROR));
        $revisionDigest = hash('sha256', $canonicalizedDerived);
        
        $id = (string) Str::uuid();
        
        $inserted = DB::table('simulation_run_result_revisions')->insertOrIgnore([
            'id' => $id,
            'result_id' => $resultId,
            'actor_identity' => $actorIdentity,
            'outcome' => $effectivePayload['outcome'],
            'score' => $effectivePayload['score'],
            'summary_ar' => $effectivePayload['summary_ar'],
            'revision_digest' => $revisionDigest,
            'base_revision_id' => $baseRevisionId,
            'correction_reason' => $correctionReason,
            'created_at' => now(),
        ]);
        
        $query = DB::table('simulation_run_result_revisions')
            ->where('result_id', $resultId)
            ->where('revision_digest', $revisionDigest);
            
        if ($baseRevisionId === null) {
            $query->whereNull('base_revision_id');
        } else {
            $query->where('base_revision_id', $baseRevisionId);
        }
            
        $existing = $query->first();
        
        if (!$inserted) {
            $actorMatch = $existing->actor_identity === $actorIdentity;
            $reasonMatch = $existing->correction_reason === $correctionReason;
            if (!$actorMatch || !$reasonMatch) {
                throw new InvalidArgumentException('Conflicting provenance for the same result revision idempotency key.');
            }
        }
            
        return $existing->id;
    }
    
    /**
     * Generates a pure Candidate Evidence Handoff envelope.
     * Does NOT write to the database (delegating to SimulationEnterpriseService).
     *
     * @param string $revisionId
     * @param string $status
     * @return array<string, mixed>
     */
    public function generateCandidateEvidenceHandoffEnvelope(
        string $revisionId, 
        string $status
    ): array {
        $status = trim($status);
        if ($status === '') {
            throw new InvalidArgumentException('Status cannot be blank.');
        }

        $revision = DB::table('simulation_run_result_revisions')->where('id', $revisionId)->first();
        if (!$revision) {
            throw new InvalidArgumentException('Effective revision does not exist for handoff.');
        }
        
        $canonicalResult = DB::table('simulation_run_results')->where('id', $revision->result_id)->first();
        if (!$canonicalResult) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Canonical result missing.');
        }
        
        return [
            'result_id' => $revision->result_id,
            'status' => $status,
            'candidate_manifest' => ['effective_revision_id' => $revisionId],
            'source_result_revision' => $canonicalResult->result_revision,
            'source_result_digest' => $canonicalResult->result_digest,
            'provenance' => $canonicalResult->provenance,
            'source_fixture' => $canonicalResult->source_fixture,
            '_integration_contract' => 'RESULTS_HANDOFF_EXISTING_WRITER_WIRING_REQUIRED',
        ];
    }
    
    /**
     * Extracts applied operations dynamically enforcing strict list boundaries and event payloads mapping.
     * Prevents PHP array key coercion for numeric string keys using a structured map prefix.
     * @param array<int, mixed> $timeline
     * @param array<int, array<string, mixed>> $operationsList
     * @return array<int, mixed>
     */
    private function extractAppliedOperations(array $timeline, array $operationsList): array
    {
        $indexedOperations = [];
        foreach ($operationsList as $op) {
            if (!is_array($op) || !isset($op['operation_key'])) {
                throw new InvalidArgumentException('Sealed operations list contains malformed elements missing operation_key.');
            }
            $opKey = $op['operation_key'];
            
            if (!is_string($opKey) || !preg_match('/^[A-Za-z0-9._:-]{12,120}$/', $opKey)) {
                throw new InvalidArgumentException('Outer operation_key must be a bounded string (12-120 chars) matching baseline constraints.');
            }
            
            $mapKey = 'op:' . $opKey;
            
            if (isset($indexedOperations[$mapKey])) {
                throw new InvalidArgumentException('Duplicate operation_key found in sealed operations list.');
            }
            
            $this->validateOperation($op);
            
            $indexedOperations[$mapKey] = $op;
        }

        $applied = [];
        $appliedKeys = [];
        
        $hasAppliedEvent = false;
        
        foreach ($timeline as $event) {
            if (($event['event_type'] ?? '') === 'SIMULATION_OPERATION_APPLIED' && isset($event['payload']['operation_key'])) {
                $hasAppliedEvent = true;
                $opKey = $event['payload']['operation_key'];
                
                if (!is_string($opKey)) {
                    throw new InvalidArgumentException('Timeline references non-string operation_key.');
                }
                
                $mapKey = 'op:' . $opKey;
                
                if (!isset($indexedOperations[$mapKey])) {
                    throw new InvalidArgumentException('Timeline references unknown operation_key.');
                }
                
                if (isset($appliedKeys[$mapKey])) {
                    throw new InvalidArgumentException('Timeline applies the same operation_key multiple times.');
                }
                
                $op = $indexedOperations[$mapKey];
                
                if ($op['operation_key'] !== $opKey) {
                    throw new InvalidArgumentException('Timeline event key identity strict mismatch.');
                }
                
                $this->verifyEventPayloadAgainstOperation($event, $op);
                
                $applied[] = $op;
                $appliedKeys[$mapKey] = true;
            }
        }
        
        // Zero-operation terminal support: if operations list is empty, timeline must not contain applied events.
        if (empty($operationsList)) {
            if ($hasAppliedEvent) {
                throw new InvalidArgumentException('Timeline references operations but operations list is empty.');
            }
            return [];
        }
        
        if (!$hasAppliedEvent) {
             throw new InvalidArgumentException('No applied operations found in the canonical history.');
        }
        
        if (count($appliedKeys) !== count($indexedOperations)) {
            throw new InvalidArgumentException('Timeline does not map all sealed operations exactly once.');
        }
        
        return $applied;
    }
    
    /**
     * @param array<int, mixed> $timeline
     */
    private function validateTimelineSequence(array $timeline): void
    {
        if (empty($timeline)) {
            return; // Empty timeline is valid for zero-operation terminal runs
        }
        if (!array_is_list($timeline)) {
            throw new InvalidArgumentException('Timeline must be a valid list.');
        }
        
        $expectedSequence = 1;
        foreach ($timeline as $event) {
            if (!is_array($event) || !isset($event['sequence'])) {
                throw new InvalidArgumentException('Timeline event missing sequence.');
            }
            if (!is_int($event['sequence'])) {
                throw new InvalidArgumentException('Timeline event sequence must be an integer.');
            }
            if ($event['sequence'] !== $expectedSequence) {
                throw new InvalidArgumentException('Timeline sequence is gap/reordered or duplicated.');
            }
            $expectedSequence++;
        }
    }
    
    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $operation
     */
    private function verifyEventPayloadAgainstOperation(array $event, array $operation): void
    {
        $payload = $event['payload'];
        if (($payload['grammar_version'] ?? '') !== $operation['grammar_version']) {
            throw new InvalidArgumentException('Timeline event grammar version mismatch.');
        }
        if (($event['actor_id'] ?? '') !== ($operation['actor_id'] ?? null)) {
            throw new InvalidArgumentException('Timeline event actor mismatch.');
        }
        if (($payload['pre_state_digest'] ?? '') !== ($operation['pre_state_digest'] ?? null)) {
            throw new InvalidArgumentException('Timeline event pre_state_digest mismatch.');
        }
        if (($payload['post_state_digest'] ?? '') !== ($operation['post_state_digest'] ?? null)) {
            throw new InvalidArgumentException('Timeline event post_state_digest mismatch.');
        }
        
        if (($payload['verb'] ?? null) !== ($operation['input']['verb'] ?? null)) {
            throw new InvalidArgumentException('Timeline event verb mismatch.');
        }
        if (($payload['target'] ?? null) !== ($operation['input']['target'] ?? null)) {
            throw new InvalidArgumentException('Timeline event target mismatch.');
        }
        if (!array_key_exists('value', $payload) || $payload['value'] !== $operation['input']['value']) {
            throw new InvalidArgumentException('Timeline event value mismatch.');
        }
    }
    
    /**
     * Asserts the baseline composite canonical digest matches perfectly.
     * @param \stdClass $canonicalResult
     */
    private function verifyCanonicalResultDigest(\stdClass $canonicalResult): void
    {
        $payload = [
            'runId' => (string) $canonicalResult->run_id,
            'outcome' => (string) $canonicalResult->outcome,
            'score' => $canonicalResult->score === null ? null : (float) $canonicalResult->score,
            'summaryAr' => (string) $canonicalResult->summary_ar,
            'sealedPayload' => $this->decodeJson($canonicalResult->sealed_payload),
            'timeline' => $this->decodeList($canonicalResult->replay_timeline),
            'artifacts' => $this->decodeList($canonicalResult->artifacts),
            'revision' => (int) $canonicalResult->result_revision,
            'provenance' => (string) $canonicalResult->provenance,
            'sourceFixture' => (bool) $canonicalResult->source_fixture,
        ];
        
        $expectedDigest = hash('sha256', json_encode($this->canonicalizeObject($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
        if ($expectedDigest !== $canonicalResult->result_digest) {
            throw new InvalidArgumentException('Sealed canonical history payload tampered/inconsistent with digest.');
        }
    }
    
    /**
     * Verifies grammar dynamically depending on Baseline operation structure natively
     * Applies schema constraints to ensure no array structures bypass boundaries.
     * @param array<string, mixed> $operation
     */
    private function validateOperation(array $operation): void
    {
        $grammar = $operation['grammar_version'] ?? '';
        
        if ($grammar !== RunResultVocabulary::OPERATION_ENGINE_V1) {
            throw new InvalidArgumentException('Invalid operation engine grammar.');
        }
        
        if (!isset($operation['input']) || !is_array($operation['input'])) {
            throw new InvalidArgumentException('Operation must contain array input schema.');
        }
        
        $input = $operation['input'];
        if (!isset($input['operation_key']) || !isset($input['verb']) || !isset($input['target']) || !array_key_exists('value', $input)) {
            throw new InvalidArgumentException('Operation input is missing required verb/target/value/operation_key properties.');
        }
        
        if (!is_string($input['operation_key']) || !preg_match('/^[A-Za-z0-9._:-]{12,120}$/', $input['operation_key'])) {
            throw new InvalidArgumentException('Input operation_key must be a bounded string (12-120 chars) matching baseline constraints.');
        }
        
        if ($input['verb'] !== 'SET_CONTROL_STATE') {
            throw new InvalidArgumentException('Input verb must be exactly SET_CONTROL_STATE per exact baseline contract.');
        }
        if ($input['target'] !== 'IDENTITY_MFA') {
            throw new InvalidArgumentException('Input target must be exactly IDENTITY_MFA per exact baseline contract.');
        }
        
        if ($input['operation_key'] !== $operation['operation_key']) {
            throw new InvalidArgumentException('Inner operation_key does not match outer key.');
        }
        
        $expectedInputDigest = hash('sha256', json_encode($this->canonicalizeObject($input), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (($operation['input_digest'] ?? '') !== $expectedInputDigest) {
            throw new InvalidArgumentException('Operation input digest tamper mismatch.');
        }
    }
    
    /**
     * Safely decode string/null JSON
     * @param mixed $value
     * @return mixed
     */
    private function decodeJson(mixed $value): mixed
    {
        if (is_string($value)) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        return $value;
    }
    
    /**
     * Safely decode array lists natively
     * @param mixed $value
     * @return list<mixed>
     */
    private function decodeList(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        return is_array($decoded) && array_is_list($decoded) ? $decoded : [];
    }

    /**
     * Deterministically canonicalize JSON representing structured data.
     * Sorts object keys recursively but preserves array/list order exactly.
     * 
     * @param string $json
     * @return string
     */
    public function canonicalizeJson(string $json): string
    {
        $decoded = json_decode($json);
        if ($decoded === null) {
            return $json;
        }
        $decoded = $this->canonicalizeObject($decoded);
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Exact canonicalization mapping the Baseline's recursive sort logic natively
     * @param mixed $value
     * @return mixed
     */
    private function canonicalizeObject(mixed $value): mixed
    {
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }
        
        $arrayValue = (array) $value;
        
        if (array_is_list($arrayValue)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalizeObject($item), $arrayValue);
        }
        
        ksort($arrayValue, SORT_STRING);
        
        return array_map(fn (mixed $item): mixed => $this->canonicalizeObject($item), $arrayValue);
    }
}
