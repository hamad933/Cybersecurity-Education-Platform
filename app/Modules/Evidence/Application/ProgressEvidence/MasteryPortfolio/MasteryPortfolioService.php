<?php

namespace App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio;

use App\Modules\Evidence\Application\ProgressEvidenceService;
use App\Modules\Evidence\Models\EvidenceMasteryPolicy;
use App\Modules\Evidence\Models\EvidenceMasteryPolicyRevision;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class MasteryPortfolioService
{
    private const JUDGMENTS = [
        'NOT_EVALUATED',
        'INSUFFICIENT_EVIDENCE',
        'INCONCLUSIVE',
        'NOT_MASTERED',
        'MASTERED',
    ];

    private const FRESHNESS = ['CURRENT', 'REVALIDATION_REQUIRED'];

    private const REVIEW_DECISIONS = [
        'ACCEPT',
        'ACCEPT_WITH_LIMITATIONS',
        'MORE_EVIDENCE_REQUIRED',
        'REJECT',
    ];

    private const CONFLICT_HANDLING = [
        'REQUIRE_INCONCLUSIVE',
        'REQUIRE_MANUAL_REVIEW',
    ];

    public function __construct(private readonly ProgressEvidenceService $progressEvidence) {}

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function createPolicy(
        string $actorId,
        string $capabilityId,
        string $name,
        array $rules,
        string $rationale,
    ): array {
        $capabilityId = $this->text($capabilityId, 100, 'Mastery target');
        $name = $this->text($name, 180, 'Mastery Policy name');
        $rationale = $this->text($rationale, 4000, 'Mastery Policy rationale');
        $normalised = $this->normaliseRules($rules);

        return DB::transaction(function () use ($actorId, $capabilityId, $name, $normalised, $rationale): array {
            $policyId = (string) Str::uuid7();
            $now = now();

            EvidenceMasteryPolicy::query()->create([
                'id' => $policyId,
                'owner_actor_id' => $actorId,
                'target_type' => 'CAPABILITY',
                'target_id' => $capabilityId,
                'name' => $name,
                'current_revision_number' => 0,
                'published_revision_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->insertPolicyRevision($policyId, $actorId, $normalised, $rationale, null, 1);
        });
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function revisePolicy(
        string $policyId,
        string $actorId,
        array $rules,
        string $rationale,
    ): array {
        $normalised = $this->normaliseRules($rules);
        $rationale = $this->text($rationale, 4000, 'Mastery Policy rationale');

        return DB::transaction(function () use ($policyId, $actorId, $normalised, $rationale): array {
            $policy = EvidenceMasteryPolicy::query()->whereKey($policyId)->lockForUpdate()->first();
            $this->ownPolicy($policy, $actorId);

            $current = EvidenceMasteryPolicyRevision::query()
                ->where('policy_id', $policyId)
                ->where('revision', $policy->current_revision_number)
                ->first();

            if ($current && $current->published_at === null) {
                throw new LogicException('Publish the current Draft Mastery Policy Revision before creating another revision.');
            }

            return $this->insertPolicyRevision(
                $policyId,
                $actorId,
                $normalised,
                $rationale,
                $current?->id,
                ((int) $policy->current_revision_number) + 1,
            );
        });
    }

    /** @return array<string, mixed> */
    public function publishPolicyRevision(string $revisionId, string $actorId): array
    {
        return DB::transaction(function () use ($revisionId, $actorId): array {
            $revision = EvidenceMasteryPolicyRevision::query()
                ->where('id', $revisionId)
                ->lockForUpdate()
                ->first();

            if (! $revision) {
                throw new LogicException('Unknown Mastery Policy Revision.');
            }

            $policy = EvidenceMasteryPolicy::query()
                ->whereKey($revision->policy_id)
                ->lockForUpdate()
                ->first();
            $this->ownPolicy($policy, $actorId);

            if ((int) $revision->revision !== (int) $policy->current_revision_number) {
                throw new LogicException('Only the current Mastery Policy Revision can be published.');
            }

            if ($revision->published_at !== null) {
                return $this->policyRevision($revisionId);
            }

            $now = now();
            EvidenceMasteryPolicyRevision::query()->whereKey($revisionId)->update([
                'published_by' => $actorId,
                'published_at' => $now,
                'updated_at' => $now,
            ]);
            EvidenceMasteryPolicy::query()->whereKey($policy->id)->update([
                'published_revision_id' => $revisionId,
                'updated_at' => $now,
            ]);

            return $this->policyRevision($revisionId);
        });
    }

    /**
     * @param  list<string>  $reviewDecisionIds
     * @param  list<string>  $supportingRevisionIds
     * @param  list<string>  $contradictingRevisionIds
     * @return array<string, mixed>
     */
    public function evaluate(
        string $subjectId,
        string $actorId,
        string $policyRevisionId,
        string $judgment,
        string $freshness,
        array $reviewDecisionIds,
        array $supportingRevisionIds,
        array $contradictingRevisionIds,
        string $rationale,
    ): array {
        if ($subjectId !== $actorId) {
            throw new LogicException('Mastery evaluation is outside the authenticated actor boundary.');
        }
        if (! in_array($judgment, self::JUDGMENTS, true) || ! in_array($freshness, self::FRESHNESS, true)) {
            throw new InvalidArgumentException('Invalid Mastery dimensions.');
        }

        $policy = $this->publishedPolicyRevision($policyRevisionId, $actorId);
        $decisions = $this->stringList($reviewDecisionIds, 'Review Decision references');
        $supporting = $this->stringList($supportingRevisionIds, 'supporting Evidence Revision references');
        $contradicting = $this->stringList($contradictingRevisionIds, 'contradicting Evidence Revision references');
        $rationale = $this->text($rationale, 4000, 'Mastery rationale');

        $this->enforcePolicy($policy, $judgment, $freshness, $decisions, $supporting, $contradicting);

        return $this->progressEvidence->evaluateMastery(
            $subjectId,
            $policy['target_id'],
            $actorId,
            $policyRevisionId,
            $judgment,
            $freshness,
            $decisions,
            $supporting,
            $contradicting,
            $rationale,
        );
    }

    /** @return array<string, mixed> */
    public function markRevalidationRequired(
        string $subjectId,
        string $actorId,
        string $policyRevisionId,
        string $reason,
    ): array {
        if ($subjectId !== $actorId) {
            throw new LogicException('Mastery revalidation is outside the authenticated actor boundary.');
        }

        $policy = $this->publishedPolicyRevision($policyRevisionId, $actorId);
        $current = DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.subject_actor_id', $subjectId)
            ->where('s.target_type', 'CAPABILITY')
            ->where('s.target_id', $policy['target_id'])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('evidence_mastery_states as child')
                    ->whereColumn('child.previous_state_id', 's.id');
            })
            ->select(
                's.*',
                'v.review_decision_ids',
                'v.supporting_evidence_revision_ids',
                'v.contradicting_evidence_revision_ids',
            )
            ->first();

        if (! $current) {
            throw new LogicException('Revalidation requires an existing governed Mastery State.');
        }

        return $this->progressEvidence->evaluateMastery(
            $subjectId,
            $policy['target_id'],
            $actorId,
            $policyRevisionId,
            $current->judgment,
            'REVALIDATION_REQUIRED',
            $this->decode($current->review_decision_ids),
            $this->decode($current->supporting_evidence_revision_ids),
            $this->decode($current->contradicting_evidence_revision_ids),
            $this->text($reason, 4000, 'Revalidation reason'),
        );
    }

    /** @return list<array<string, mixed>> */
    public function masteryHistory(string $actorId, string $capabilityId): array
    {
        return DB::table('evidence_mastery_states as s')
            ->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.evaluation_id')
            ->where('s.subject_actor_id', $actorId)
            ->where('s.target_type', 'CAPABILITY')
            ->where('s.target_id', $this->text($capabilityId, 100, 'Mastery target'))
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
            ->map(fn ($row): array => $this->array($row, [
                'review_decision_ids',
                'supporting_evidence_revision_ids',
                'contradicting_evidence_revision_ids',
            ]))
            ->all();
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
        return $this->progressEvidence->createPortfolio(
            $actorId,
            $name,
            $scope,
            $grouping,
            $filters,
            $annotations,
        );
    }

    public function addAcceptedEvidenceToPortfolio(
        string $portfolioId,
        string $evidenceId,
        string $actorId,
        ?string $annotation = null,
        int $sort = 0,
    ): void {
        $this->progressEvidence->addEvidenceToPortfolio(
            $portfolioId,
            $evidenceId,
            $actorId,
            $annotation,
            $sort,
        );
    }

    public function removeEvidenceFromPortfolio(string $portfolioId, string $evidenceId, string $actorId): void
    {
        $this->progressEvidence->removeEvidenceFromPortfolio($portfolioId, $evidenceId, $actorId);
    }

    /** @return array<string, mixed> */
    public function portfolioProjection(string $portfolioId, string $actorId): array
    {
        return $this->progressEvidence->portfolioProjection($portfolioId, $actorId);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function normaliseRules(array $rules): array
    {
        $qualifying = $this->stringList(
            $rules['qualifying_review_decisions'] ?? ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'],
            'qualifying Review Decisions',
        );
        if ($qualifying === [] || array_diff($qualifying, self::REVIEW_DECISIONS) !== []) {
            throw new InvalidArgumentException('Mastery Policy contains an invalid qualifying Review Decision.');
        }

        $conflictHandling = (string) ($rules['conflict_handling'] ?? 'REQUIRE_INCONCLUSIVE');
        if (! in_array($conflictHandling, self::CONFLICT_HANDLING, true)) {
            throw new InvalidArgumentException('Invalid Mastery Policy conflict handling.');
        }

        $diversity = is_array($rules['evidence_diversity'] ?? null) ? $rules['evidence_diversity'] : [];
        $minimumEvidence = max(1, (int) ($diversity['min_distinct_evidence'] ?? 1));
        $confidence = $rules['minimum_attribution_confidence'] ?? null;
        if ($confidence !== null && (! is_numeric($confidence) || (float) $confidence < 0 || (float) $confidence > 1)) {
            throw new InvalidArgumentException('Minimum attribution confidence must be between 0 and 1.');
        }

        $recencyDays = $rules['recency_days'] ?? null;
        if ($recencyDays !== null && (! is_numeric($recencyDays) || (int) $recencyDays <= 0)) {
            throw new InvalidArgumentException('Mastery Policy recency days must be a positive integer.');
        }

        return [
            'required_criteria' => $this->stringList($rules['required_criteria'] ?? [], 'required criteria'),
            'qualifying_review_decisions' => $qualifying,
            'evidence_diversity' => ['min_distinct_evidence' => $minimumEvidence],
            'minimum_attribution_confidence' => $confidence === null ? null : (float) $confidence,
            'conflict_handling' => $conflictHandling,
            'permitted_limitations' => $this->stringList($rules['permitted_limitations'] ?? [], 'permitted limitations'),
            'recency_days' => $recencyDays === null ? null : (int) $recencyDays,
            'freshness_triggers' => $this->stringList($rules['freshness_triggers'] ?? [], 'freshness triggers'),
            'revalidation_conditions' => $this->stringList($rules['revalidation_conditions'] ?? [], 'revalidation conditions'),
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function insertPolicyRevision(
        string $policyId,
        string $actorId,
        array $rules,
        string $rationale,
        ?string $previousRevisionId,
        int $revision,
    ): array {
        $revisionId = (string) Str::uuid7();
        $now = now();
        $body = [
            'policy_id' => $policyId,
            'revision' => $revision,
            'previous_revision_id' => $previousRevisionId,
            ...$rules,
            'rationale' => $rationale,
        ];

        EvidenceMasteryPolicyRevision::query()->insert([
            'id' => $revisionId,
            'policy_id' => $policyId,
            'previous_revision_id' => $previousRevisionId,
            'revision' => $revision,
            'required_criteria' => $this->json($rules['required_criteria']),
            'qualifying_review_decisions' => $this->json($rules['qualifying_review_decisions']),
            'evidence_diversity' => $this->json($rules['evidence_diversity']),
            'minimum_attribution_confidence' => $rules['minimum_attribution_confidence'],
            'conflict_handling' => $rules['conflict_handling'],
            'permitted_limitations' => $this->json($rules['permitted_limitations']),
            'recency_days' => $rules['recency_days'],
            'freshness_triggers' => $this->json($rules['freshness_triggers']),
            'revalidation_conditions' => $this->json($rules['revalidation_conditions']),
            'rationale' => $rationale,
            'authored_by' => $actorId,
            'published_by' => null,
            'published_at' => null,
            'content_digest' => hash('sha256', $this->json($body)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        EvidenceMasteryPolicy::query()->whereKey($policyId)->update([
            'current_revision_number' => $revision,
            'updated_at' => $now,
        ]);

        return $this->policyRevision($revisionId);
    }

    /** @return array<string, mixed> */
    private function publishedPolicyRevision(string $revisionId, string $actorId): array
    {
        $revision = $this->policyRevision($revisionId);
        if ($revision['owner_actor_id'] !== $actorId) {
            throw new LogicException('Mastery Policy is outside the authenticated actor boundary.');
        }
        if ($revision['published_at'] === null) {
            throw new LogicException('Mastery evaluation requires a published Mastery Policy Revision.');
        }

        return $revision;
    }

    /** @return array<string, mixed> */
    private function policyRevision(string $revisionId): array
    {
        $row = EvidenceMasteryPolicyRevision::query()
            ->from('evidence_mastery_policy_revisions as r')
            ->join('evidence_mastery_policies as p', 'p.id', '=', 'r.policy_id')
            ->where('r.id', $revisionId)
            ->select(
                'r.*',
                'p.owner_actor_id',
                'p.target_type',
                'p.target_id',
                'p.name as policy_name',
                'p.current_revision_number',
                'p.published_revision_id',
            )
            ->first();

        if (! $row) {
            throw new LogicException('Unknown Mastery Policy Revision.');
        }

        return $this->array($row, [
            'required_criteria',
            'qualifying_review_decisions',
            'evidence_diversity',
            'permitted_limitations',
            'freshness_triggers',
            'revalidation_conditions',
        ]);
    }

    /**
     * @param  array<string, mixed>  $policy
     * @param  list<string>  $decisions
     * @param  list<string>  $supporting
     * @param  list<string>  $contradicting
     */
    private function enforcePolicy(
        array $policy,
        string $judgment,
        string $freshness,
        array $decisions,
        array $supporting,
        array $contradicting,
    ): void {
        if ($contradicting !== [] && $judgment !== 'INCONCLUSIVE') {
            throw new LogicException(
                $policy['conflict_handling'] === 'REQUIRE_MANUAL_REVIEW'
                    ? 'Conflicting Evidence requires manual review and an INCONCLUSIVE Mastery Judgment until resolved.'
                    : 'Conflicting Evidence requires an INCONCLUSIVE Mastery Judgment.',
            );
        }

        $decisionRows = $decisions === [] ? collect() : DB::table('evidence_review_decisions')
            ->whereIn('id', $decisions)
            ->get(['id', 'decision']);
        if ($decisionRows->count() !== count($decisions)) {
            throw new LogicException('Unknown Review Decision reference.');
        }

        $revisionIds = array_values(array_unique([...$supporting, ...$contradicting]));
        $revisionRows = $revisionIds === [] ? collect() : DB::table('governed_evidence_revisions as r')
            ->join('governed_evidence as e', 'e.id', '=', 'r.evidence_id')
            ->whereIn('r.id', $revisionIds)
            ->get([
                'r.id',
                'r.evidence_id',
                'r.criterion_scope',
                'r.facts',
                'r.sealed_at',
                'e.subject_actor_id',
                'e.capability_id',
            ]);
        if ($revisionRows->count() !== count($revisionIds)) {
            throw new LogicException('Unknown Evidence Revision reference.');
        }

        if ($judgment !== 'MASTERED') {
            return;
        }

        foreach ($decisionRows as $row) {
            if (! in_array($row->decision, $policy['qualifying_review_decisions'], true)) {
                throw new LogicException('MASTERED cannot rely on a Review Decision disallowed by the published Mastery Policy.');
            }
        }

        $supportingRows = $revisionRows->whereIn('id', $supporting);
        $criteria = $supportingRows
            ->flatMap(fn ($row): array => $this->decode($row->criterion_scope))
            ->unique()
            ->values()
            ->all();
        $missingCriteria = array_values(array_diff($policy['required_criteria'], $criteria));
        if ($missingCriteria !== []) {
            throw new LogicException('MASTERED is missing required Mastery Policy criteria: '.implode(', ', $missingCriteria).'.');
        }

        $minimumEvidence = (int) ($policy['evidence_diversity']['min_distinct_evidence'] ?? 1);
        if ($supportingRows->pluck('evidence_id')->unique()->count() < $minimumEvidence) {
            throw new LogicException('MASTERED does not satisfy the Mastery Policy evidence-diversity requirement.');
        }

        $minimumConfidence = $policy['minimum_attribution_confidence'];
        if ($minimumConfidence !== null) {
            foreach ($supportingRows as $row) {
                $facts = $this->decode($row->facts);
                $confidence = $facts['attribution_confidence'] ?? null;
                if (! is_numeric($confidence) || (float) $confidence < (float) $minimumConfidence) {
                    throw new LogicException('MASTERED does not satisfy the Mastery Policy minimum attribution confidence.');
                }
            }
        }

        if ($freshness === 'CURRENT' && $policy['recency_days'] !== null) {
            $cutoff = now()->subDays((int) $policy['recency_days']);
            foreach ($supportingRows as $row) {
                if (CarbonImmutable::parse((string) $row->sealed_at)->lt($cutoff)) {
                    throw new LogicException('CURRENT freshness is not allowed because supporting Evidence exceeds the Mastery Policy recency requirement.');
                }
            }
        }
    }

    private function ownPolicy(?object $policy, string $actorId): void
    {
        if (! $policy || $policy->owner_actor_id !== $actorId) {
            throw new LogicException('Mastery Policy is outside the authenticated actor boundary.');
        }
    }

    private function text(mixed $value, int $max, string $label): string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_strlen($value) > $max) {
            throw new InvalidArgumentException("{$label} is invalid.");
        }

        return $value;
    }

    /** @return list<string> */
    private function stringList(mixed $value, string $label): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("{$label} must be an array.");
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                throw new InvalidArgumentException("{$label} contains an empty value.");
            }
            $items[] = $item;
        }
        $items = array_values(array_unique($items));
        sort($items, SORT_STRING);

        return $items;
    }

    /**
     * @param  list<string>  $jsonFields
     * @return array<string, mixed>
     */
    private function array(object $row, array $jsonFields = []): array
    {
        $result = $row instanceof Model ? $row->attributesToArray() : (array) $row;
        foreach ($jsonFields as $field) {
            $result[$field] = $this->decode($result[$field] ?? null);
        }

        return $result;
    }

    /** @return array<string, mixed>|list<mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
