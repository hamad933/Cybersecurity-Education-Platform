<?php

namespace App\Modules\Evidence\Application;

use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\PortfolioCurationService;
use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\PortfolioGroupingRegistry;
use App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio\PortfolioProjectionService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceIntakeService;
use App\Modules\Evidence\IntakeReview\Application\EvidenceReviewService;
use App\Modules\Evidence\IntakeReview\Application\ReviewDecisionService;
use App\Modules\Evidence\Models\EvidenceEffectiveReviewDecision;
use App\Modules\Evidence\Models\EvidenceMasteryPolicyRevision;
use App\Modules\Evidence\Models\EvidenceReviewDecisionItem;
use App\Modules\Evidence\Models\EvidenceReviewScopeItem;
use App\Modules\Evidence\Models\EvidenceSourceHandoffReceipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class ProgressEvidenceService
{
    private const GOVERNED_PURPOSES = [
        'FORMAL_CAPABILITY_EVIDENCE' => true,
        'GOVERNED_PROVENANCE_ATTESTATION' => false,
    ];

    private const JUDGMENTS = [
        'NOT_EVALUATED',
        'INSUFFICIENT_EVIDENCE',
        'INCONCLUSIVE',
        'NOT_MASTERED',
        'MASTERED',
    ];

    private const FRESHNESS = ['CURRENT', 'REVALIDATION_REQUIRED'];

    public function __construct(
        private readonly EvidenceIntakeService $intake,
        private readonly EvidenceReviewService $reviews,
        private readonly ReviewDecisionService $decisions,
        private readonly PortfolioCurationService $portfolioCuration,
        private readonly PortfolioProjectionService $portfolioProjection,
        private readonly PortfolioGroupingRegistry $portfolioGroupings,
    ) {}

    /**
     * Trusted application boundary for source-domain Handoff/Submission producers.
     * This method intentionally has no HTTP route.
     *
     * @param  array<string, mixed>  $handoff
     * @return array<string, mixed>
     */
    public function registerSourceHandoffReceipt(string $subjectId, string $registeredBy, array $handoff): array
    {
        foreach ([
            'source_type',
            'source_id',
            'source_revision',
            'source_digest',
            'selected_material_refs',
            'capability_id',
        ] as $key) {
            if (! array_key_exists($key, $handoff) || (is_string($handoff[$key]) && trim($handoff[$key]) === '')) {
                throw new InvalidArgumentException("Missing source handoff field: {$key}.");
            }
        }

        $sourceType = $this->text((string) $handoff['source_type'], 64, 'Source type');
        $sourceId = $this->text((string) $handoff['source_id'], 160, 'Source ID');
        $sourceRevision = $this->text((string) $handoff['source_revision'], 80, 'Source revision');
        $sourceDigest = $this->sourceDigest((string) $handoff['source_digest']);
        $materials = $this->stringList($handoff['selected_material_refs'] ?? null, 'selected material references', true);
        $capabilityId = $this->text((string) $handoff['capability_id'], 100, 'Capability ID');
        $facts = $this->boundedFacts($handoff['facts'] ?? []);
        $metadata = $this->boundedMetadata($handoff['metadata'] ?? []);

        $conflict = EvidenceSourceHandoffReceipt::query()
            ->where('subject_actor_id', $subjectId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('source_revision', $sourceRevision)
            ->where('source_digest', '!=', $sourceDigest)
            ->exists();
        if ($conflict) {
            throw new LogicException('Source Handoff revision conflicts with an immutable source integrity digest.');
        }

        $body = [
            'subject_actor_id' => $subjectId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_revision' => $sourceRevision,
            'source_digest' => $sourceDigest,
            'selected_material_refs' => $materials,
            'capability_id' => $capabilityId,
            'facts' => $facts,
            'metadata' => $metadata,
        ];
        $receiptDigest = $this->digest($body);
        $existing = EvidenceSourceHandoffReceipt::query()
            ->where('subject_actor_id', $subjectId)
            ->where('receipt_digest', $receiptDigest)
            ->first();
        if ($existing) {
            return $this->array($existing, ['selected_material_refs', 'facts', 'metadata']);
        }

        $id = (string) Str::uuid7();
        $now = now();
        EvidenceSourceHandoffReceipt::query()->insert([
            ...$body,
            'id' => $id,
            'registered_by' => $registeredBy,
            'selected_material_refs' => $this->json($materials),
            'facts' => $this->json($facts),
            'metadata' => $this->json($metadata),
            'receipt_digest' => $receiptDigest,
            'registered_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('evidence_source_handoff_receipts', $id, ['selected_material_refs', 'facts', 'metadata']);
    }

    /**
     * @param  array<string, mixed>  $candidateInput
     * @return array<string, mixed>
     */
    public function intakeCandidate(
        string $subjectId,
        string $submittedBy,
        string $handoffReceiptId,
        array $candidateInput,
    ): array {
        return $this->intake->receive($subjectId, $submittedBy, [
            ...$candidateInput,
            'handoff_receipt_id' => $handoffReceiptId,
        ]);
    }

    /** @return array<string, mixed> */
    public function transitionCandidate(string $candidateId, string $actorId, string $state): array
    {
        return $this->intake->transitionCandidate($candidateId, $actorId, $state);
    }

    /** @return array{evidence: array<string, mixed>, revision: array<string, mixed>} */
    public function admitCandidate(string $candidateId, string $actorId): array
    {
        return $this->intake->admitCandidate($candidateId, $actorId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createRevision(string $evidenceId, string $actorId, array $data): array
    {
        return DB::transaction(function () use ($evidenceId, $actorId, $data): array {
            $evidence = $this->lock('governed_evidence', $evidenceId);
            $this->own($evidence, $actorId);

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Only ACTIVE Evidence can receive a superseding revision.');
            }

            $current = DB::table('governed_evidence_revisions')
                ->where('evidence_id', $evidenceId)
                ->where('revision', $evidence->current_revision_number)
                ->first();

            if (! $current) {
                throw new LogicException('Current Evidence Revision is missing.');
            }

            $reason = $this->text((string) ($data['revision_reason'] ?? ''), 1000, 'Revision reason');
            $title = $this->text((string) ($data['title'] ?? ''), 180, 'Revision title');
            $summary = $this->text((string) ($data['summary'] ?? ''), 4000, 'Revision summary');
            $receipt = null;
            if (isset($data['handoff_receipt_id'])) {
                $receipt = EvidenceSourceHandoffReceipt::query()
                    ->whereKey((string) $data['handoff_receipt_id'])
                    ->first();
                if (! $receipt || $receipt->subject_actor_id !== $actorId || $receipt->capability_id !== $evidence->capability_id) {
                    throw new LogicException('Superseding source Handoff receipt is outside the Evidence ownership/capability boundary.');
                }
            }
            $facts = $receipt ? $this->decode($receipt->facts) : $this->decode($current->facts);
            $materials = $receipt
                ? $this->decode($receipt->selected_material_refs)
                : $this->decode($current->selected_material_refs);
            $criteria = array_key_exists('criterion_scope', $data)
                ? $this->stringList($data['criterion_scope'], 'criterion scope', false, 50, 120)
                : $this->decode($current->criterion_scope);
            $this->governedPurpose($evidence->governed_purpose, $criteria);
            $revisionNumber = ((int) $evidence->current_revision_number) + 1;
            $revisionId = (string) Str::uuid7();
            $now = now();
            $body = [
                'id' => $revisionId,
                'evidence_id' => $evidenceId,
                'handoff_receipt_id' => $receipt ? $receipt->id : $current->handoff_receipt_id,
                'revision' => $revisionNumber,
                'previous_revision_id' => $current->id,
                'title' => $title,
                'summary' => $summary,
                'facts' => $facts,
                'selected_material_refs' => $materials,
                'criterion_scope' => $criteria,
                'source_type' => $receipt ? $receipt->source_type : $current->source_type,
                'source_id' => $receipt ? $receipt->source_id : $current->source_id,
                'source_revision' => $receipt ? $receipt->source_revision : $current->source_revision,
                'source_digest' => $receipt ? $receipt->source_digest : $current->source_digest,
                'revision_reason' => $reason,
            ];

            DB::table('governed_evidence_revisions')->insert([
                ...$body,
                'facts' => $this->json($facts),
                'selected_material_refs' => $this->json($materials),
                'criterion_scope' => $this->json($criteria),
                'content_digest' => $this->digest($body),
                'sealed_by' => $actorId,
                'sealed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Scope-specific effective Decisions are a current-revision projection.
            // Immutable prior Decisions remain in their historical lineage tables.
            EvidenceEffectiveReviewDecision::query()
                ->where('evidence_id', $evidenceId)
                ->delete();

            DB::table('governed_evidence')->where('id', $evidenceId)->update([
                'current_revision_number' => $revisionNumber,
                'review_status' => 'UNREVIEWED',
                'effective_review_decision' => 'NONE',
                'effective_review_decision_id' => null,
                'updated_at' => $now,
            ]);

            return $this->revision($evidenceId, $revisionNumber);
        });
    }

    public function transitionLifecycle(string $evidenceId, string $actorId, string $state): void
    {
        if (! in_array($state, ['ACTIVE', 'WITHDRAWN', 'SUPERSEDED'], true)) {
            throw new InvalidArgumentException('Invalid Evidence lifecycle.');
        }

        DB::transaction(function () use ($evidenceId, $actorId, $state): void {
            $evidence = $this->lock('governed_evidence', $evidenceId);
            $this->own($evidence, $actorId);

            if ($evidence->lifecycle_state === $state) {
                return;
            }

            if ($evidence->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Terminal Evidence lifecycle cannot reopen or change terminal state.');
            }

            DB::table('governed_evidence')->where('id', $evidenceId)->update([
                'lifecycle_state' => $state,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function requestReview(string $evidenceId, string $actorId, array $data = []): array
    {
        return $this->reviews->requestCurrentRevisionReview($evidenceId, $actorId, $data);
    }

    /** @return array<string, mixed> */
    public function admitReviewRequest(string $requestId, string $reviewerId): array
    {
        return $this->reviews->startReview($requestId, $reviewerId);
    }

    /**
     * @param  list<string>  $supportingRevisionIds
     * @return array<string, mixed>
     */
    public function recordFinding(
        string $reviewId,
        string $actorId,
        string $criterion,
        string $finding,
        string $statement,
        array $supportingRevisionIds = [],
    ): array {
        return $this->reviews->recordFinding(
            $reviewId,
            $actorId,
            $criterion,
            $finding,
            $statement,
            $supportingRevisionIds,
        );
    }

    /** @return array<string, mixed> */
    public function recordReviewDecision(
        string $reviewId,
        string $actorId,
        string $decision,
        string $rationale,
    ): array {
        return $this->decisions->recordDecision($reviewId, $actorId, $decision, $rationale);
    }

    /**
     * @param  list<string>  $reviewDecisionIds
     * @param  list<string>  $supportingRevisionIds
     * @param  list<string>  $contradictingRevisionIds
     * @return array<string, mixed>
     */
    public function evaluateMastery(
        string $subjectId,
        string $capabilityId,
        string $evaluatorId,
        string $policyRevision,
        string $judgment,
        string $freshness,
        array $reviewDecisionIds,
        array $supportingRevisionIds,
        array $contradictingRevisionIds,
        string $rationale,
    ): array {
        if ($subjectId !== $evaluatorId) {
            throw new LogicException('Mastery evaluation is outside the authenticated actor boundary.');
        }
        if (! in_array($judgment, self::JUDGMENTS, true) || ! in_array($freshness, self::FRESHNESS, true)) {
            throw new InvalidArgumentException('Invalid Mastery dimensions.');
        }

        $capabilityId = $this->text($capabilityId, 100, 'Capability ID');
        $policyRevision = $this->text($policyRevision, 120, 'Mastery Policy Revision');
        $rationale = $this->text($rationale, 4000, 'Mastery rationale');
        $policy = EvidenceMasteryPolicyRevision::query()
            ->from('evidence_mastery_policy_revisions as r')
            ->join('evidence_mastery_policies as p', 'p.id', '=', 'r.policy_id')
            ->where('r.id', $policyRevision)
            ->where('p.owner_actor_id', $subjectId)
            ->where('p.target_type', 'CAPABILITY')
            ->where('p.target_id', $capabilityId)
            ->whereNotNull('r.published_at')
            ->select('r.qualifying_review_decisions')
            ->first();
        if (! $policy) {
            throw new LogicException('A real published Mastery Policy Revision is required for this subject and capability.');
        }
        $qualifyingDecisions = $this->stringList(
            $this->decode($policy->qualifying_review_decisions),
            'Mastery Policy qualifying Review Decisions',
            true,
            2,
            40,
        );
        $decisions = $this->stringList($reviewDecisionIds, 'Review Decision references');
        $supporting = $this->stringList($supportingRevisionIds, 'supporting Evidence Revision references');
        $contradicting = $this->stringList($contradictingRevisionIds, 'contradicting Evidence Revision references');

        if (array_intersect($supporting, $contradicting) !== []) {
            throw new LogicException('An Evidence Revision cannot be both supporting and contradicting in the same Mastery State.');
        }
        if ($judgment === 'MASTERED' && ($supporting === [] || $decisions === [])) {
            throw new LogicException('MASTERED requires exact supporting Evidence Revision and Review Decision provenance.');
        }

        $revisionRows = [];
        foreach (array_values(array_unique([...$supporting, ...$contradicting])) as $revisionId) {
            $row = DB::table('governed_evidence_revisions as r')
                ->join('governed_evidence as e', 'e.id', '=', 'r.evidence_id')
                ->where('r.id', $revisionId)
                ->select(
                    'r.id',
                    'r.evidence_id',
                    'e.subject_actor_id',
                    'e.capability_id',
                    'e.lifecycle_state',
                )
                ->first();

            if (! $row) {
                throw new LogicException('Unknown Evidence Revision reference.');
            }
            if ($row->subject_actor_id !== $subjectId || $row->capability_id !== $capabilityId) {
                throw new LogicException('Evidence Revision reference is outside the Mastery subject/capability boundary.');
            }
            if ($row->lifecycle_state !== 'ACTIVE') {
                throw new LogicException('Only ACTIVE Evidence Revisions can contribute to a new Mastery State.');
            }

            $revisionRows[$revisionId] = $row;
        }

        $decisionRows = [];
        $decisionIdsByRevision = [];
        foreach ($decisions as $decisionId) {
            $row = DB::table('evidence_review_decisions')->where('id', $decisionId)->first();

            if (! $row) {
                throw new LogicException('Unknown Review Decision reference.');
            }

            $items = EvidenceReviewDecisionItem::query()
                ->from('evidence_review_decision_items as item')
                ->join('governed_evidence as evidence', 'evidence.id', '=', 'item.evidence_id')
                ->where('item.decision_id', $decisionId)
                ->toBase()
                ->get([
                    'item.evidence_id',
                    'item.evidence_revision_id',
                    'evidence.subject_actor_id',
                    'evidence.capability_id',
                ]);
            if ($items->isEmpty()) {
                throw new LogicException('Review Decision is missing its exact canonical Evidence scope items.');
            }

            foreach ($items as $item) {
                if ($item->subject_actor_id !== $subjectId || $item->capability_id !== $capabilityId) {
                    throw new LogicException('Review Decision reference is outside the Mastery subject/capability boundary.');
                }
                if (! isset($revisionRows[$item->evidence_revision_id])) {
                    throw new LogicException('Every Review Decision item must reference an Evidence Revision explicitly included in the Mastery evaluation.');
                }

                $effective = EvidenceEffectiveReviewDecision::query()
                    ->where('evidence_id', $item->evidence_id)
                    ->where('review_scope_key', $row->review_scope_key)
                    ->where('evidence_revision_id', $item->evidence_revision_id)
                    ->where('decision_id', $decisionId)
                    ->exists();
                if (! $effective) {
                    throw new LogicException('Superseded Review Decisions cannot be used as effective Mastery provenance.');
                }

                $decisionIdsByRevision[(string) $item->evidence_revision_id][] = $decisionId;
            }
            $decisionRows[$decisionId] = $row;
        }

        foreach (array_keys($revisionRows) as $revisionId) {
            if (($decisionIdsByRevision[$revisionId] ?? []) === []) {
                throw new LogicException("Evidence Revision {$revisionId} is missing its exact effective Review Decision provenance.");
            }
        }

        if ($judgment === 'MASTERED') {
            foreach ($supporting as $revisionId) {
                $qualifies = collect($decisionIdsByRevision[$revisionId] ?? [])
                    ->contains(fn (string $decisionId): bool => in_array(
                        $decisionRows[$decisionId]->decision ?? null,
                        $qualifyingDecisions,
                        true,
                    ));
                if (! $qualifies) {
                    throw new LogicException('MASTERED requires a policy-qualifying effective Review Decision for every supporting Evidence Revision.');
                }
            }
        }

        $body = [
            'subject_actor_id' => $subjectId,
            'target_type' => 'CAPABILITY',
            'target_id' => $capabilityId,
            'policy_revision_id' => $policyRevision,
            'judgment' => $judgment,
            'freshness_status' => $freshness,
            'review_decision_ids' => $decisions,
            'supporting_evidence_revision_ids' => $supporting,
            'contradicting_evidence_revision_ids' => $contradicting,
            'rationale' => $rationale,
        ];

        return DB::transaction(function () use ($body, $evaluatorId): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                $body['subject_actor_id'].'|'.$body['target_id'],
            ]);

            $prior = $this->masteryChainTips($body['subject_actor_id'])
                ->where('current.target_type', 'CAPABILITY')
                ->where('current.target_id', $body['target_id'])
                ->lockForUpdate()
                ->first();
            $evaluationId = (string) Str::uuid7();
            $stateId = (string) Str::uuid7();
            $now = now();

            DB::table('evidence_mastery_evaluations')->insert([
                'id' => $evaluationId,
                'subject_actor_id' => $body['subject_actor_id'],
                'target_type' => $body['target_type'],
                'target_id' => $body['target_id'],
                'policy_revision_id' => $body['policy_revision_id'],
                'judgment' => $body['judgment'],
                'freshness_status' => $body['freshness_status'],
                'review_decision_ids' => $this->json($body['review_decision_ids']),
                'supporting_evidence_revision_ids' => $this->json($body['supporting_evidence_revision_ids']),
                'contradicting_evidence_revision_ids' => $this->json($body['contradicting_evidence_revision_ids']),
                'rationale' => $body['rationale'],
                'evaluator_id' => $evaluatorId,
                'content_digest' => $this->digest($body),
                'evaluated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('evidence_mastery_states')->insert([
                'id' => $stateId,
                'subject_actor_id' => $body['subject_actor_id'],
                'target_type' => $body['target_type'],
                'target_id' => $body['target_id'],
                'judgment' => $body['judgment'],
                'freshness_status' => $body['freshness_status'],
                'policy_revision_id' => $body['policy_revision_id'],
                'evaluation_id' => $evaluationId,
                'previous_state_id' => $prior?->id,
                'reason' => $body['rationale'],
                'evaluated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($body['review_decision_ids'] as $decisionId) {
                DB::table('evidence_mastery_state_decisions')->insert([
                    'mastery_state_id' => $stateId,
                    'review_decision_id' => $decisionId,
                    'created_at' => $now,
                ]);
            }

            foreach ($body['supporting_evidence_revision_ids'] as $revisionId) {
                DB::table('evidence_mastery_state_evidence')->insert([
                    'mastery_state_id' => $stateId,
                    'evidence_revision_id' => $revisionId,
                    'contribution' => 'SUPPORTING',
                    'created_at' => $now,
                ]);
            }

            foreach ($body['contradicting_evidence_revision_ids'] as $revisionId) {
                DB::table('evidence_mastery_state_evidence')->insert([
                    'mastery_state_id' => $stateId,
                    'evidence_revision_id' => $revisionId,
                    'contribution' => 'CONTRADICTING',
                    'created_at' => $now,
                ]);
            }

            return $this->masteryState($stateId);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $annotations
     * @return array<string, mixed>
     */
    public function createPortfolio(
        string $actorId,
        string $name,
        ?string $scope,
        string $grouping,
        array $filters = [],
        array $annotations = [],
    ): array {
        return $this->portfolioCuration->create(
            $actorId,
            $name,
            $scope,
            $grouping,
            $filters,
            $annotations,
        );
    }

    public function addEvidenceToPortfolio(
        string $portfolioId,
        string $evidenceId,
        string $actorId,
        ?string $annotation = null,
        int $sort = 0,
    ): void {
        $this->portfolioCuration->addAcceptedEvidence(
            $portfolioId,
            $evidenceId,
            $actorId,
            $annotation,
            $sort,
        );
    }

    public function removeEvidenceFromPortfolio(string $portfolioId, string $evidenceId, string $actorId): void
    {
        $this->portfolioCuration->removeEvidence($portfolioId, $evidenceId, $actorId);
    }

    /** @return array<string, mixed> */
    public function portfolioProjection(string $portfolioId, string $actorId): array
    {
        return $this->portfolioProjection->project($portfolioId, $actorId);
    }

    /** @return array<string, mixed> */
    public function workspace(string $actorId): array
    {
        $handoffReceipts = EvidenceSourceHandoffReceipt::query()
            ->where('subject_actor_id', $actorId)
            ->latest('registered_at')
            ->select(
                'id',
                'source_type',
                'source_id',
                'source_revision',
                'source_digest',
                'selected_material_refs',
                'capability_id',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, ['selected_material_refs']))
            ->all();

        $candidates = DB::table('evidence_candidates')
            ->where('subject_actor_id', $actorId)
            ->latest()
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'selected_material_refs',
                'criterion_scope',
                'proposed_facts',
                'metadata',
            ]))
            ->all();

        $evidence = DB::table('governed_evidence as e')
            ->join('governed_evidence_revisions as r', fn ($join) => $join
                ->on('r.evidence_id', '=', 'e.id')
                ->on('r.revision', '=', 'e.current_revision_number'))
            ->where('e.subject_actor_id', $actorId)
            ->latest('e.admitted_at')
            ->select(
                'e.*',
                'r.id as current_revision_id',
                'r.previous_revision_id',
                'r.title',
                'r.summary',
                'r.facts',
                'r.selected_material_refs',
                'r.criterion_scope',
                'r.source_type',
                'r.source_id',
                'r.source_revision',
                'r.source_digest',
                'r.revision_reason',
                'r.content_digest',
                'r.sealed_at',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']))
            ->all();

        foreach ($evidence as &$record) {
            $record['revisions'] = DB::table('governed_evidence_revisions')
                ->where('evidence_id', $record['id'])
                ->orderBy('revision')
                ->get()
                ->map(fn ($row) => $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']))
                ->all();
            $record['effective_review_decisions'] = EvidenceEffectiveReviewDecision::query()
                ->where('evidence_id', $record['id'])
                ->where('evidence_revision_id', $record['current_revision_id'])
                ->orderBy('review_scope_key')
                ->get()
                ->map(static fn (EvidenceEffectiveReviewDecision $decision): array => $decision->attributesToArray())
                ->all();
        }
        unset($record);

        $evidenceIds = array_column($evidence, 'id');
        $requests = $evidenceIds === [] ? [] : DB::table('evidence_review_requests')
            ->whereIn('evidence_id', $evidenceIds)
            ->latest('requested_at')
            ->get()
            ->map(fn ($row) => $this->array($row, ['criterion_refs']))
            ->all();
        foreach ($requests as &$reviewRequest) {
            $reviewRequest['scope_items'] = EvidenceReviewScopeItem::query()
                ->where('review_request_id', $reviewRequest['id'])
                ->orderBy('ordinal')
                ->get()
                ->map(static fn (EvidenceReviewScopeItem $row): array => $row->attributesToArray())
                ->all();
        }
        unset($reviewRequest);
        $reviews = $evidenceIds === [] ? [] : DB::table('evidence_reviews')
            ->whereIn('evidence_id', $evidenceIds)
            ->latest('started_at')
            ->get()
            ->map(fn ($row) => $this->array($row, ['criterion_refs']))
            ->all();

        foreach ($reviews as &$review) {
            $review['findings'] = DB::table('evidence_review_findings')
                ->where('review_id', $review['id'])
                ->orderBy('recorded_at')
                ->get()
                ->map(fn ($row) => $this->array($row, ['supporting_evidence_revision_ids']))
                ->all();
            $decision = DB::table('evidence_review_decisions')->where('review_id', $review['id'])->first();
            $review['decision'] = $decision ? [
                ...(array) $decision,
                'items' => EvidenceReviewDecisionItem::query()
                    ->where('decision_id', $decision->id)
                    ->orderBy('ordinal')
                    ->get()
                    ->map(static fn (EvidenceReviewDecisionItem $row): array => $row->attributesToArray())
                    ->all(),
            ] : null;
        }
        unset($review);

        $history = DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.subject_actor_id', $actorId)
            ->orderByDesc('s.evaluated_at')
            ->select(
                's.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'review_decision_ids',
                'supporting_evidence_revision_ids',
                'contradicting_evidence_revision_ids',
            ]))
            ->all();
        $mastery = $this->masteryChainTips($actorId)
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 'current.evaluation_id')
            ->select(
                'current.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->get()
            ->map(fn ($row) => $this->array($row, [
                'review_decision_ids',
                'supporting_evidence_revision_ids',
                'contradicting_evidence_revision_ids',
            ]))
            ->all();
        $masteryPolicies = EvidenceMasteryPolicyRevision::query()
            ->from('evidence_mastery_policy_revisions as r')
            ->join('evidence_mastery_policies as p', 'p.id', '=', 'r.policy_id')
            ->whereNotNull('r.published_at')
            ->orderBy('p.name')
            ->orderByDesc('r.revision')
            ->select('r.*', 'p.name as policy_key', 'p.target_type', 'p.target_id')
            ->get()
            ->map(fn ($row) => $this->array($row, ['qualifying_review_decisions']))
            ->all();

        $portfolios = DB::table('evidence_portfolios')
            ->where('owner_actor_id', $actorId)
            ->latest('updated_at')
            ->get()
            ->map(fn (object $row): array => $this->portfolioProjection->projectRow($row, $actorId))
            ->all();

        $openCandidateStates = [
            'RECEIVED',
            'DRAFT',
            'PREPARED',
            'SUBMITTED_FOR_INTAKE',
            'RETURNED_FOR_CONTEXT',
        ];

        return [
            'summary' => [
                'candidate_count' => count(array_filter(
                    $candidates,
                    fn ($candidate) => in_array($candidate['state'], $openCandidateStates, true),
                )),
                'evidence_count' => count($evidence),
                'review_in_progress_count' => count(array_filter(
                    $reviews,
                    fn ($review) => in_array($review['status'], ['IN_REVIEW', 'READY_FOR_DECISION'], true),
                )),
                'mastery_count' => count($mastery),
                'portfolio_count' => count($portfolios),
            ],
            'handoff_receipts' => $handoffReceipts,
            'candidates' => $candidates,
            'evidence' => $evidence,
            'review_requests' => $requests,
            'reviews' => $reviews,
            'mastery' => $mastery,
            'mastery_history' => $history,
            'mastery_policies' => $masteryPolicies,
            'portfolios' => $portfolios,
            'portfolio_groupings' => $this->portfolioGroupings->definitions(),
        ];
    }

    private function masteryChainTips(string $subjectId): Builder
    {
        return DB::table('evidence_mastery_states as current')
            ->where('current.subject_actor_id', $subjectId)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('evidence_mastery_states as successor')
                    ->whereColumn('successor.previous_state_id', 'current.id');
            });
    }

    /** @return array<string, mixed> */
    private function masteryState(string $stateId): array
    {
        $row = DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.id', $stateId)
            ->select(
                's.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
                'v.rationale',
                'v.content_digest',
            )
            ->first();

        if (! $row) {
            throw new LogicException('Mastery State missing.');
        }

        return $this->array($row, [
            'review_decision_ids',
            'supporting_evidence_revision_ids',
            'contradicting_evidence_revision_ids',
        ]);
    }

    private function lock(string $table, string $id): object
    {
        $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
        if (! $row) {
            throw new InvalidArgumentException("{$table} record not found.");
        }

        return $row;
    }

    private function own(object $row, string $actorId): void
    {
        if (($row->subject_actor_id ?? null) !== $actorId) {
            throw new LogicException('Record is outside actor boundary.');
        }
    }

    /**
     * @param  list<string>  $json
     * @return array<string, mixed>
     */
    private function row(string $table, string $id, array $json = []): array
    {
        $row = DB::table($table)->where('id', $id)->first();
        if (! $row) {
            throw new InvalidArgumentException("{$table} record not found.");
        }

        return $this->array($row, $json);
    }

    /** @return array<string, mixed> */
    private function revision(string $evidenceId, int $revision): array
    {
        $row = DB::table('governed_evidence_revisions')
            ->where('evidence_id', $evidenceId)
            ->where('revision', $revision)
            ->first();

        if (! $row) {
            throw new LogicException('Evidence Revision missing.');
        }

        return $this->array($row, ['facts', 'selected_material_refs', 'criterion_scope']);
    }

    /**
     * @param  list<string>  $json
     * @return array<string, mixed>
     */
    private function array(object $row, array $json = []): array
    {
        $out = $row instanceof Model ? $row->attributesToArray() : (array) $row;
        foreach ($json as $key) {
            $out[$key] = $this->decode($out[$key] ?? null);
        }

        return $out;
    }

    /** @return array<mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : [];

        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<string> */
    private function stringList(
        mixed $value,
        string $name,
        bool $required = false,
        int $maxItems = 50,
        int $maxLength = 240,
    ): array {
        if (! is_array($value)) {
            if ($required) {
                throw new InvalidArgumentException("{$name} must be a non-empty array.");
            }

            return [];
        }

        $normalized = [];
        foreach ($value as $entry) {
            if (! is_string($entry) || trim($entry) === '') {
                throw new InvalidArgumentException("{$name} contains an invalid reference.");
            }
            $entry = trim($entry);
            if (mb_strlen($entry) > $maxLength) {
                throw new InvalidArgumentException("{$name} contains an overlong reference.");
            }
            $normalized[] = $entry;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);

        if ($required && $normalized === []) {
            throw new InvalidArgumentException("{$name} must contain at least one reference.");
        }
        if (count($normalized) > $maxItems) {
            throw new InvalidArgumentException("{$name} exceeds the maximum item count.");
        }

        return $normalized;
    }

    /** @param list<string> $criteria */
    private function governedPurpose(string $purpose, array $criteria): string
    {
        $purpose = $this->text($purpose, 180, 'Governed Evidence purpose');
        if (! array_key_exists($purpose, self::GOVERNED_PURPOSES)) {
            throw new InvalidArgumentException('Unsupported governed Evidence purpose.');
        }
        if (self::GOVERNED_PURPOSES[$purpose] && $criteria === []) {
            throw new LogicException('Formal capability Evidence requires a governed criterion scope.');
        }

        return $purpose;
    }

    /** @return list<array{key: string, value: string}> */
    private function boundedFacts(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > 50) {
            throw new InvalidArgumentException('Source Handoff facts must be a list of at most 50 key/value facts.');
        }

        $facts = [];
        foreach ($value as $fact) {
            if (! is_array($fact) || array_diff(array_keys($fact), ['key', 'value']) !== []) {
                throw new InvalidArgumentException('Each source Handoff fact must contain only key and value.');
            }
            $facts[] = [
                'key' => $this->text((string) ($fact['key'] ?? ''), 80, 'Source Handoff fact key'),
                'value' => $this->text((string) ($fact['value'] ?? ''), 2000, 'Source Handoff fact value'),
            ];
        }

        return $facts;
    }

    /** @return array<string, bool|float|int|string|null> */
    private function boundedMetadata(mixed $value): array
    {
        if (! is_array($value) || (array_is_list($value) && $value !== []) || count($value) > 20) {
            throw new InvalidArgumentException('Source Handoff metadata must be a bounded key/value object.');
        }

        $metadata = [];
        foreach ($value as $key => $entry) {
            if (! is_string($key) || preg_match('/^[A-Za-z0-9_.:-]{1,80}$/', $key) !== 1) {
                throw new InvalidArgumentException('Source Handoff metadata contains an invalid key.');
            }
            if (! is_string($entry) && ! is_bool($entry) && ! is_int($entry) && ! is_float($entry) && $entry !== null) {
                throw new InvalidArgumentException('Source Handoff metadata contains an unsupported value.');
            }
            if (is_string($entry) && mb_strlen($entry) > 500) {
                throw new InvalidArgumentException('Source Handoff metadata contains an overlong value.');
            }
            $metadata[$key] = $entry;
        }

        ksort($metadata, SORT_STRING);

        return $metadata;
    }

    private function text(string $value, int $max, string $name): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException("{$name} is required.");
        }
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException("{$name} exceeds the maximum length.");
        }

        return $value;
    }

    private function sourceDigest(string $value): string
    {
        $digest = strtolower(trim($value));
        if (! preg_match('/^[a-f0-9]{64}$/', $digest)) {
            throw new InvalidArgumentException('Source digest must be SHA-256 hex.');
        }

        return $digest;
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<mixed> $value */
    private function digest(array $value): string
    {
        return hash('sha256', $this->json($value));
    }
}
