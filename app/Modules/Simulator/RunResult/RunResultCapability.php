<?php

declare(strict_types=1);

namespace App\Modules\Simulator\RunResult;

use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class RunResultCapability
{
    private const REPLAY_PROJECTOR_VERSION = 'cep.results.replay.operation-engine-v1/v1';

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

        $normalizeScore = function ($score): ?string {
            if ($score === null) {
                return null;
            }
            if (is_numeric($score)) {
                $str = (string) $score;
                if (strpos($str, '.') !== false) {
                    $str = rtrim($str, '0');
                    $str = rtrim($str, '.');
                }
                return $str;
            }
            return (string) $score;
        };

        return [
            'final_state' => 'REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED',
            'operation_count' => count($appliedOperations),
            'state_change_count' => 'REPLAY_SEMANTIC_PROJECTOR_DEPENDENCY_REQUIRED',
            'outcome' => $revision->outcome,
            'score' => $normalizeScore($revision->score),
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

        if (count(array_unique($canonicalResultIds)) !== count($revisionIds)) {
            throw new InvalidArgumentException('Cannot compare duplicate Run Revisions.');
        }

        if (count(array_unique($canonicalRunIds)) !== count($revisionIds)) {
            throw new InvalidArgumentException('Cannot compare distinct canonical Results referencing the same Run.');
        }

        return $results;
    }

    /**
     * Builds the authoritative, zero-write Results workstation projection for one
     * canonical sealed Result. The projection consumes the immutable revision
     * lineage; it never creates an initial revision as a read side effect.
     *
     * @return array<string, mixed>
     */
    public function projectResultAnalytics(string $resultId): array
    {
        $canonicalResult = DB::table('simulation_run_results')->where('id', $resultId)->first();
        if (!$canonicalResult) {
            throw new RuntimeException('DEPENDENCY_REQUIRED: Canonical result missing.');
        }

        $this->verifyCanonicalResultDigest($canonicalResult);
        $lineage = $this->resolveEffectiveRevision($resultId);
        $lineageProjection = $lineage['projection'];
        $sealedPayload = $this->decodeJson($canonicalResult->sealed_payload);
        $canonical = [
            'result_id' => (string) $canonicalResult->id,
            'run_id' => (string) $canonicalResult->run_id,
            'result_revision' => (int) $canonicalResult->result_revision,
            'result_digest' => (string) $canonicalResult->result_digest,
            'provenance' => (string) $canonicalResult->provenance,
            'source_fixture' => (bool) $canonicalResult->source_fixture,
            'sealed_by' => (string) $canonicalResult->sealed_by,
            'sealed_at' => (string) $canonicalResult->sealed_at,
            'run_type' => is_array($sealedPayload) ? (string) ($sealedPayload['run_type'] ?? '') : '',
            'run_lifecycle' => is_array($sealedPayload) ? (string) ($sealedPayload['run_lifecycle'] ?? '') : '',
        ];

        if ($lineage['effective'] === null) {
            $status = (string) $lineageProjection['status'];

            return [
                'status' => $status,
                'overview' => [
                    'status' => $status,
                    'canonical' => $canonical,
                    'lineage' => $lineageProjection,
                    'effective' => null,
                ],
                'replay' => $this->unavailableProjection($status),
                'aar' => $this->unavailableProjection($status),
                'candidate_evidence' => $this->unavailableProjection($status),
            ];
        }

        $effective = $lineage['effective'];
        $this->verifyRevisionDigest($effective);
        $effectiveProjection = [
            'id' => (string) $effective->id,
            'result_id' => (string) $effective->result_id,
            'revision_digest' => (string) $effective->revision_digest,
            'base_revision_id' => $effective->base_revision_id === null ? null : (string) $effective->base_revision_id,
            'correction_reason' => $effective->correction_reason,
            'actor_identity' => $effective->actor_identity,
            'created_at' => (string) $effective->created_at,
            'outcome' => $effective->outcome,
            'score' => $this->normalizeScore($effective->score),
            'summary_ar' => $effective->summary_ar,
        ];

        $replay = $this->projectReplayAnalytics($canonicalResult);
        $aar = $this->projectAarAnalytics($canonicalResult, $effectiveProjection, $replay);
        $candidateEvidence = [
            'status' => 'READY',
            'write_behavior' => 'ZERO_WRITE_SOURCE_PREVIEW',
            'w04_state' => 'NOT_CREATED_OR_CLAIMED',
            'envelope' => $this->generateCandidateEvidenceHandoffEnvelope(
                (string) $effective->id,
                'SOURCE_PREVIEW_ONLY',
            ),
        ];

        return [
            'status' => in_array($replay['status'], ['READY', 'EMPTY'], true)
                && $aar['status'] === 'READY'
                    ? 'READY'
                    : 'PARTIAL_ANALYTICS',
            'overview' => [
                'status' => 'READY',
                'canonical' => $canonical,
                'lineage' => $lineageProjection,
                'effective' => $effectiveProjection,
            ],
            'replay' => $replay,
            'aar' => $aar,
            'candidate_evidence' => $candidateEvidence,
        ];
    }

    /**
     * Compares distinct canonical Results/Runs using a backend-owned dimension
     * registry. Missing or incompatible values remain explicit N/A values.
     *
     * @param list<string> $resultIds
     * @return array<string, mixed>
     */
    public function projectResultComparison(array $resultIds): array
    {
        if (count($resultIds) < 2) {
            throw new InvalidArgumentException('COMPARE_MINIMUM_DISTINCT_RUNS_REQUIRED');
        }
        if (count(array_unique($resultIds)) !== count($resultIds)) {
            throw new InvalidArgumentException('COMPARE_DUPLICATE_CANONICAL_RESULT_LINEAGE');
        }

        $items = [];
        $revisionIds = [];
        $runIds = [];

        foreach ($resultIds as $resultId) {
            $analytics = $this->projectResultAnalytics($resultId);
            $overview = $analytics['overview'];
            $effective = $overview['effective'];
            if (!is_array($effective)) {
                throw new InvalidArgumentException('COMPARE_EFFECTIVE_REVISION_UNAVAILABLE:'.$overview['status']);
            }

            $canonical = $overview['canonical'];
            $revisionIds[] = (string) $effective['id'];
            $runIds[] = (string) $canonical['run_id'];
            $items[] = [
                'result_id' => (string) $canonical['result_id'],
                'run_id' => (string) $canonical['run_id'],
                'canonical_result_digest' => (string) $canonical['result_digest'],
                'effective_revision_id' => (string) $effective['id'],
                'effective_revision_digest' => (string) $effective['revision_digest'],
                'analytics' => $analytics,
            ];
        }

        if (count(array_unique($revisionIds)) !== count($revisionIds)) {
            throw new InvalidArgumentException('COMPARE_DUPLICATE_REVISION_SELECTION');
        }
        if (count(array_unique($runIds)) !== count($runIds)) {
            throw new InvalidArgumentException('COMPARE_DUPLICATE_CANONICAL_RUN');
        }

        $registry = [
            ['key' => 'outcome', 'label_ar' => 'النتيجة الفعالة', 'value_type' => 'categorical', 'source' => 'effective_revision'],
            ['key' => 'score', 'label_ar' => 'الدرجة الفعالة', 'value_type' => 'decimal', 'source' => 'effective_revision'],
            ['key' => 'provenance', 'label_ar' => 'المصدر المحكوم', 'value_type' => 'categorical', 'source' => 'canonical_result'],
            ['key' => 'source_fixture', 'label_ar' => 'بيانات Fixture', 'value_type' => 'boolean', 'source' => 'canonical_result'],
            ['key' => 'operation_count', 'label_ar' => 'العمليات المختومة', 'value_type' => 'integer', 'source' => 'sealed_history'],
        ];

        $dimensions = [];
        $hasUnavailable = false;
        foreach ($registry as $definition) {
            $values = [];
            $dimensionCompatible = true;
            foreach ($items as $item) {
                $overview = $item['analytics']['overview'];
                $aar = $item['analytics']['aar'];
                $value = match ($definition['key']) {
                    'outcome' => $overview['effective']['outcome'],
                    'score' => $overview['effective']['score'],
                    'provenance' => $overview['canonical']['provenance'],
                    'source_fixture' => $overview['canonical']['source_fixture'],
                    'operation_count' => $aar['operation_count'] ?? null,
                };
                $available = $value !== null && $this->valueMatchesType($value, $definition['value_type']);
                $dimensionCompatible = $dimensionCompatible && $available;
                $values[] = [
                    'result_id' => $item['result_id'],
                    'run_id' => $item['run_id'],
                    'value' => $available ? $value : null,
                    'display' => $available ? $this->comparisonDisplay($value) : 'N/A',
                    'availability' => $available ? 'READY' : 'N/A',
                    'source_ref' => match ($definition['source']) {
                        'effective_revision' => 'revision:'.$item['effective_revision_id'],
                        'canonical_result' => 'result:'.$item['result_id'],
                        default => 'result:'.$item['result_id'].'/sealed-history',
                    },
                ];
            }

            if (!$dimensionCompatible) {
                $hasUnavailable = true;
            }
            $dimensions[] = [
                ...$definition,
                'status' => $dimensionCompatible ? 'READY' : 'N/A',
                'compatible' => $dimensionCompatible,
                'values' => $values,
            ];
        }

        return [
            'status' => $hasUnavailable ? 'PARTIAL_ANALYTICS' : 'READY',
            'selection_valid' => true,
            'selected_result_ids' => array_values($resultIds),
            'selected_run_ids' => $runIds,
            'items' => array_map(fn (array $item): array => [
                'result_id' => $item['result_id'],
                'run_id' => $item['run_id'],
                'canonical_result_digest' => $item['canonical_result_digest'],
                'effective_revision_id' => $item['effective_revision_id'],
                'effective_revision_digest' => $item['effective_revision_digest'],
            ], $items),
            'dimensions' => $dimensions,
            'comparison_semantics' => 'cep.results.compare.registry/v1',
            'write_behavior' => 'ZERO_WRITE_PROJECTION',
        ];
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
            'score' => $canonicalResult->score === null ? null : $canonicalResult->score,
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
            $effectivePayload['score'] = $baseRevision->score === null ? null : $baseRevision->score;
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
            $effectivePayload['score'] = $derivedFields['score'] === null ? null : $derivedFields['score'];
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

        $this->verifyCanonicalResultDigest($canonicalResult);

        $effectivePayload = [
            'outcome' => $revision->outcome,
            'score' => $revision->score === null ? null : $revision->score,
            'summary_ar' => $revision->summary_ar,
        ];

        $canonicalizedDerived = $this->canonicalizeJson(json_encode($effectivePayload, JSON_THROW_ON_ERROR));
        $expectedRevisionDigest = hash('sha256', $canonicalizedDerived);

        if ($expectedRevisionDigest !== $revision->revision_digest) {
            throw new InvalidArgumentException('Effective revision digest mismatch or tamper detected.');
        }

        $envelope = [
            'result_id' => $revision->result_id,
            'run_id' => $canonicalResult->run_id,
            'status' => $status,
            'effective_revision_id' => $revisionId,
            'effective_revision_digest' => $revision->revision_digest,
            'source_result_revision' => $canonicalResult->result_revision,
            'source_result_digest' => $canonicalResult->result_digest,
            'base_revision_id' => $revision->base_revision_id,
            'correction_reason' => $revision->correction_reason,
            'provenance' => $canonicalResult->provenance,
            'source_fixture' => $canonicalResult->source_fixture,
            '_integration_contract' => 'RESULTS_HANDOFF_EXISTING_WRITER_WIRING_REQUIRED',
        ];

        $canonicalRun = DB::table('simulation_runs')->where('id', $canonicalResult->run_id)->first();
        if ($canonicalRun) {
            $envelope['material_context'] = [
                'enterprise_id' => $canonicalRun->enterprise_id,
                'digital_twin_id' => $canonicalRun->digital_twin_id,
                'digital_twin_revision_id' => $canonicalRun->digital_twin_revision_id,
                'baseline_id' => $canonicalRun->baseline_id,
                'scenario_definition_id' => $canonicalRun->scenario_definition_id,
                'standalone_lab_definition_id' => $canonicalRun->standalone_lab_definition_id,
            ];

            $labIds = [];

            if ($canonicalRun->standalone_lab_definition_id) {
                $labIds[] = $canonicalRun->standalone_lab_definition_id;
            }

            if ($canonicalRun->scenario_definition_id) {
                // Determine lab module instances or scenario lab references
                // We will use simulation_scenario_lab_references and simulation_run_lab_module_instances
                $scenarioLabRefs = DB::table('simulation_scenario_lab_references')
                    ->where('scenario_definition_id', $canonicalRun->scenario_definition_id)
                    ->pluck('lab_definition_id')->toArray();

                $runLabInstances = DB::table('simulation_run_lab_module_instances')
                    ->where('run_id', $canonicalRun->id)
                    ->pluck('lab_definition_id')->toArray();

                $labIds = array_unique(array_merge($labIds, $scenarioLabRefs, $runLabInstances));
            }

            if (!empty($labIds)) {
                $envelope['material_context']['lab_ids'] = array_values($labIds);
            }
        }

        if ($revision->actor_identity !== null) {
            $envelope['actor_identity'] = $revision->actor_identity;
        }

        return $envelope;
    }

    /** @return array{projection: array<string, mixed>, effective: \stdClass|null} */
    private function resolveEffectiveRevision(string $resultId): array
    {
        $revisions = DB::table('simulation_run_result_revisions')
            ->where('result_id', $resultId)
            ->get()
            ->all();

        if ($revisions === []) {
            return [
                'projection' => [
                    'status' => 'INITIAL_REVISION_REQUIRED',
                    'revision_count' => 0,
                    'root_revision_id' => null,
                    'effective_revision_id' => null,
                    'revisions' => [],
                ],
                'effective' => null,
            ];
        }

        $roots = [];
        $byId = [];
        $children = [];
        $projectionRows = [];
        foreach ($revisions as $revision) {
            $this->verifyRevisionDigest($revision);
            $id = (string) $revision->id;
            $baseId = $revision->base_revision_id === null ? null : (string) $revision->base_revision_id;
            $byId[$id] = $revision;
            if ($baseId === null) {
                $roots[] = $id;
            } else {
                $children[$baseId][] = $id;
            }
            $projectionRows[] = [
                'id' => $id,
                'base_revision_id' => $baseId,
                'revision_digest' => (string) $revision->revision_digest,
                'actor_identity' => $revision->actor_identity,
                'correction_reason' => $revision->correction_reason,
                'created_at' => (string) $revision->created_at,
            ];
        }

        $invalidBranch = count($roots) !== 1;
        foreach ($children as $baseId => $childIds) {
            if (!isset($byId[$baseId]) || count($childIds) !== 1) {
                $invalidBranch = true;
            }
        }

        $visited = [];
        $cursor = $roots[0] ?? null;
        while (!$invalidBranch && $cursor !== null) {
            if (isset($visited[$cursor]) || !isset($byId[$cursor])) {
                $invalidBranch = true;
                break;
            }
            $visited[$cursor] = true;
            $next = $children[$cursor] ?? [];
            $cursor = $next[0] ?? null;
        }

        if (count($visited) !== count($revisions)) {
            $invalidBranch = true;
        }

        if ($invalidBranch) {
            return [
                'projection' => [
                    'status' => 'LINEAGE_RECONCILIATION_REQUIRED',
                    'revision_count' => count($revisions),
                    'root_revision_id' => count($roots) === 1 ? $roots[0] : null,
                    'effective_revision_id' => null,
                    'revisions' => $projectionRows,
                ],
                'effective' => null,
            ];
        }

        $leafId = array_key_last($visited);

        return [
            'projection' => [
                'status' => 'READY',
                'revision_count' => count($revisions),
                'root_revision_id' => $roots[0],
                'effective_revision_id' => $leafId,
                'revisions' => $projectionRows,
            ],
            'effective' => $byId[$leafId],
        ];
    }

    /** @return array<string, mixed> */
    private function projectReplayAnalytics(\stdClass $canonicalResult): array
    {
        $sealedPayload = $this->decodeJson($canonicalResult->sealed_payload);
        if (!is_array($sealedPayload)
            || !isset($sealedPayload['operations'])
            || !is_array($sealedPayload['operations'])
            || !array_is_list($sealedPayload['operations'])) {
            throw new InvalidArgumentException('Canonical operation history must be a valid list.');
        }

        $timeline = $this->decodeList($canonicalResult->replay_timeline);
        $this->validateTimelineSequence($timeline);
        $sourceEvents = array_map(fn (array $event): array => $this->replayEventSourceProjection($event), $timeline);
        $operations = $sealedPayload['operations'];
        $appliedOperations = $this->extractAppliedOperations($timeline, $operations);

        if ($operations === []) {
            return [
                'status' => $timeline === [] ? 'EMPTY' : 'PARTIAL_ANALYTICS',
                'projector' => [
                    'availability' => 'NOT_APPLICABLE',
                    'grammar_version' => null,
                    'semantic_version' => null,
                ],
                'events' => $sourceEvents,
                'operation_count' => 0,
                'write_behavior' => 'ZERO_WRITE_PROJECTION',
            ];
        }

        $grammarVersions = [];
        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                throw new InvalidArgumentException('Sealed operations list contains malformed elements.');
            }
            $grammarVersions[] = is_string($operation['grammar_version'] ?? null)
                ? $operation['grammar_version']
                : '';
        }
        $grammarVersions = array_values(array_unique($grammarVersions));
        if (count($grammarVersions) !== 1 || $grammarVersions[0] !== SimulationEnterpriseService::OPERATION_GRAMMAR) {
            return [
                'status' => 'SEMANTIC_PROJECTOR_UNAVAILABLE',
                'projector' => [
                    'availability' => 'SEMANTIC_PROJECTOR_UNAVAILABLE',
                    'grammar_version' => count($grammarVersions) === 1 ? $grammarVersions[0] : 'MIXED_OR_UNKNOWN',
                    'semantic_version' => null,
                ],
                'events' => $sourceEvents,
                'operation_count' => null,
                'write_behavior' => 'ZERO_WRITE_PROJECTION',
            ];
        }

        foreach ($operations as $operation) {
            $input = $operation['input'] ?? null;
            if (!is_array($input)
                || !is_string($operation['operation_key'] ?? null)
                || ($input['operation_key'] ?? null) !== $operation['operation_key']
                || ($input['verb'] ?? null) !== 'SET_CONTROL_STATE'
                || ($input['target'] ?? null) !== 'IDENTITY_MFA'
                || !is_bool($input['value'] ?? null)) {
                return [
                    'status' => 'SEMANTIC_PROJECTOR_UNAVAILABLE',
                    'projector' => [
                        'availability' => 'SEMANTIC_PROJECTOR_UNAVAILABLE',
                        'grammar_version' => SimulationEnterpriseService::OPERATION_GRAMMAR,
                        'semantic_version' => null,
                        'reason' => 'UNSUPPORTED_OPERATION_SEMANTICS',
                    ],
                    'events' => $sourceEvents,
                    'operation_count' => null,
                    'write_behavior' => 'ZERO_WRITE_PROJECTION',
                ];
            }
        }

        $byKey = [];
        foreach ($appliedOperations as $operation) {
            $byKey[(string) $operation['operation_key']] = $operation;
        }

        $projectedControls = [];
        $events = [];
        foreach ($timeline as $event) {
            $projection = $this->replayEventSourceProjection($event);
            $operationKey = ($event['event_type'] ?? '') === 'SIMULATION_OPERATION_APPLIED'
                ? ($event['payload']['operation_key'] ?? null)
                : null;
            if (is_string($operationKey) && isset($byKey[$operationKey])) {
                $operation = $byKey[$operationKey];
                $projectedControls[(string) $operation['input']['target']] = $operation['input']['value'];
                $projection['operation_key'] = $operationKey;
            }
            $projection['state_at_point'] = [
                'projection_scope' => 'GOVERNED_OPERATION_CONTROLS_ONLY',
                'controls' => $projectedControls,
            ];
            $projection['projection_status'] = 'READY';
            $events[] = $projection;
        }

        return [
            'status' => $events === [] ? 'EMPTY' : 'READY',
            'projector' => [
                'availability' => 'READY',
                'grammar_version' => SimulationEnterpriseService::OPERATION_GRAMMAR,
                'semantic_version' => self::REPLAY_PROJECTOR_VERSION,
            ],
            'events' => $events,
            'operation_count' => count($appliedOperations),
            'write_behavior' => 'ZERO_WRITE_PROJECTION',
        ];
    }

    /**
     * @param array<string, mixed> $effective
     * @param array<string, mixed> $replay
     * @return array<string, mixed>
     */
    private function projectAarAnalytics(\stdClass $canonicalResult, array $effective, array $replay): array
    {
        $facts = [];
        if ($effective['outcome'] !== null) {
            $facts[] = [
                'id' => 'effective-outcome',
                'kind' => 'EFFECTIVE_RESULT_FIELD',
                'label_ar' => 'النتيجة الفعالة',
                'value' => $effective['outcome'],
                'source_ref' => 'revision:'.$effective['id'].'/outcome',
            ];
        }
        if ($effective['score'] !== null) {
            $facts[] = [
                'id' => 'effective-score',
                'kind' => 'EFFECTIVE_RESULT_FIELD',
                'label_ar' => 'الدرجة الفعالة',
                'value' => $effective['score'],
                'source_ref' => 'revision:'.$effective['id'].'/score',
            ];
        }

        foreach ($replay['events'] as $event) {
            $facts[] = [
                'id' => 'timeline-'.$event['sequence'],
                'kind' => 'SEALED_TIMELINE_EVENT',
                'label_ar' => 'حدث مختوم',
                'value' => $event['event_type'],
                'source_ref' => $event['source_ref'],
                'sequence' => $event['sequence'],
            ];
        }

        $artifacts = $this->decodeList($canonicalResult->artifacts);
        foreach ($artifacts as $index => $artifact) {
            $artifactMap = is_array($artifact) ? $artifact : [];
            $facts[] = [
                'id' => 'artifact-'.($index + 1),
                'kind' => 'SEALED_ARTIFACT',
                'label_ar' => 'مرجع مادة مختومة',
                'value' => $artifactMap['ref'] ?? $artifactMap['kind'] ?? 'artifact-'.($index + 1),
                'source_ref' => 'result:'.$canonicalResult->id.'/artifacts/'.($index + 1),
            ];
        }

        return [
            'status' => count($facts) >= 3 ? 'READY' : 'PARTIAL_ANALYTICS',
            'facts' => $facts,
            'operation_count' => $replay['operation_count'],
            'sealed_commentary' => $effective['summary_ar'] === null ? null : [
                'value' => $effective['summary_ar'],
                'source_ref' => 'revision:'.$effective['id'].'/summary_ar',
                'classification' => 'SEALED_RESULT_COMMENTARY',
            ],
            'unavailable_sections' => [
                ['key' => 'causal_factors', 'reason' => 'UNAVAILABLE_FROM_SEALED_TRUTH'],
                ['key' => 'lessons', 'reason' => 'UNAVAILABLE_FROM_SEALED_TRUTH'],
                ['key' => 'missed_detections', 'reason' => 'UNAVAILABLE_FROM_SEALED_TRUTH'],
                ['key' => 'derived_metrics', 'reason' => 'UNAVAILABLE_FROM_SEALED_TRUTH'],
            ],
            'source_policy' => 'SEALED_HISTORY_AND_EFFECTIVE_REVISION_ONLY',
            'write_behavior' => 'ZERO_WRITE_PROJECTION',
        ];
    }

    /** @param array<string, mixed> $event @return array<string, mixed> */
    private function replayEventSourceProjection(array $event): array
    {
        return [
            'sequence' => (int) $event['sequence'],
            'event_type' => (string) ($event['event_type'] ?? 'UNKNOWN_EVENT'),
            'actor_id' => (string) ($event['actor_id'] ?? ''),
            'occurred_at' => (string) ($event['occurred_at'] ?? ''),
            'payload' => is_array($event['payload'] ?? null) ? $event['payload'] : [],
            'source_ref' => 'timeline:sequence:'.(int) $event['sequence'],
            'projection_status' => 'UNAVAILABLE',
            'state_at_point' => null,
        ];
    }

    /** @return array{status:string, reason:string} */
    private function unavailableProjection(string $reason): array
    {
        return ['status' => 'UNAVAILABLE', 'reason' => $reason];
    }

    private function verifyRevisionDigest(\stdClass $revision): void
    {
        $effectivePayload = [
            'outcome' => $revision->outcome,
            'score' => $revision->score === null ? null : $revision->score,
            'summary_ar' => $revision->summary_ar,
        ];
        $canonicalized = $this->canonicalizeJson(json_encode($effectivePayload, JSON_THROW_ON_ERROR));
        if (hash('sha256', $canonicalized) !== $revision->revision_digest) {
            throw new InvalidArgumentException('Effective revision digest mismatch or tamper detected.');
        }
    }

    private function normalizeScore(mixed $score): ?string
    {
        if ($score === null) {
            return null;
        }
        $normalized = (string) $score;
        if (str_contains($normalized, '.')) {
            $normalized = rtrim(rtrim($normalized, '0'), '.');
        }

        return $normalized;
    }

    private function valueMatchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'decimal' => is_numeric($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            default => is_string($value) && $value !== '',
        };
    }

    private function comparisonDisplay(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return (string) $value;
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

        // Zero-operation terminal support: if operations list is empty, timeline must not contain applied events.
        // F: Evaluate zero-op branch before iterating over timeline with unknown-key false failure
        $hasAppliedEventAnywhere = false;
        foreach ($timeline as $event) {
            if (($event['event_type'] ?? '') === 'SIMULATION_OPERATION_APPLIED') {
                $hasAppliedEventAnywhere = true;
                break;
            }
        }

        if (empty($operationsList)) {
            if ($hasAppliedEventAnywhere) {
                throw new InvalidArgumentException('Timeline references operations but operations list is empty.');
            }
            return [];
        }

        $applied = [];
        $appliedKeys = [];

        $hasAppliedEvent = false;
        $lastPostStateDigest = null;

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

                // D: Validate operation pre/post state digest correspondence and chain continuity where grammar requires
                $currentPreStateDigest = $event['payload']['pre_state_digest'] ?? null;
                $currentPostStateDigest = $event['payload']['post_state_digest'] ?? null;

                if ($lastPostStateDigest !== null && $currentPreStateDigest !== null) {
                    if ($lastPostStateDigest !== $currentPreStateDigest) {
                        throw new InvalidArgumentException('Timeline chain continuity broken: pre_state_digest does not match previous post_state_digest.');
                    }
                }

                if ($currentPostStateDigest !== null) {
                    $lastPostStateDigest = $currentPostStateDigest;
                }

                $applied[] = $op;
                $appliedKeys[$mapKey] = true;
            }
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
            'score' => $canonicalResult->score === null ? null : $canonicalResult->score,
            'summaryAr' => (string) $canonicalResult->summary_ar,
            'sealedPayload' => $this->decodeJson($canonicalResult->sealed_payload),
            'timeline' => $this->decodeList($canonicalResult->replay_timeline),
            'artifacts' => $this->decodeList($canonicalResult->artifacts),
            'revision' => (int) $canonicalResult->result_revision,
            'provenance' => (string) $canonicalResult->provenance,
            'sourceFixture' => $canonicalResult->source_fixture,
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

        if ($grammar !== SimulationEnterpriseService::OPERATION_GRAMMAR) {
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
        if (!is_bool($input['value'])) {
            throw new InvalidArgumentException('Input value must be strictly boolean per exact baseline contract.');
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
