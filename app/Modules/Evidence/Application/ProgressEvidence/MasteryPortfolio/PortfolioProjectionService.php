<?php

namespace App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio;

use App\Modules\Evidence\Models\EvidenceEffectiveReviewDecision;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PortfolioProjectionService
{
    public function __construct(private readonly PortfolioGroupingRegistry $groupings) {}

    /** @return array<string, mixed> */
    public function project(string $portfolioId, string $actorId): array
    {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();

        if (! $portfolio) {
            throw new LogicException('Portfolio was not found.');
        }

        return $this->projectRow($portfolio, $actorId);
    }

    /** @return array<string, mixed> */
    public function projectRow(object $portfolio, string $actorId): array
    {
        if ((string) $portfolio->owner_actor_id !== $actorId) {
            throw new LogicException('Portfolio boundary mismatch.');
        }

        $grouping = (string) $portfolio->grouping;
        $this->groupings->assertSupported($grouping);
        $filters = $this->decode($portfolio->filters);

        $items = DB::table('evidence_portfolio_items as item')
            ->join('governed_evidence as evidence', 'evidence.id', '=', 'item.evidence_id')
            ->join('governed_evidence_revisions as revision', function ($join): void {
                $join->on('revision.evidence_id', '=', 'evidence.id')
                    ->on('revision.revision', '=', 'evidence.current_revision_number');
            })
            ->leftJoin('evidence_mastery_states as mastery', 'mastery.id', '=', 'item.mastery_state_id')
            ->where('item.portfolio_id', $portfolio->id)
            ->orderBy('item.sort_order')
            ->orderBy('item.id')
            ->select(
                'item.id',
                'item.id as portfolio_item_id',
                'item.evidence_id',
                'item.mastery_state_id',
                'item.sort_order',
                'item.annotation',
                'evidence.capability_id',
                'evidence.lifecycle_state',
                'evidence.review_status',
                'revision.id as evidence_revision_id',
                'revision.title',
                'revision.summary',
                'revision.source_type',
                'revision.sealed_at',
                'mastery.judgment as mastery_judgment',
                'mastery.freshness_status',
                'mastery.policy_revision_id',
            )
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

        $effectiveByEvidence = $this->effectiveDecisions($items);
        $items = array_values(array_filter(array_map(function (array $item) use ($effectiveByEvidence): array {
            $effective = $effectiveByEvidence[$item['evidence_id']] ?? [];
            $item['effective_review_decisions'] = $effective;
            $item['effective_review_decision'] = $effective[0]['decision'] ?? 'NONE';

            return $item;
        }, $items), fn (array $item): bool => $this->matchesFilters($item, $filters)));

        $projection = (array) $portfolio;
        $projection['filters'] = $filters;
        $projection['annotations'] = $this->decode($portfolio->annotations);
        $projection['items'] = $items;
        $projection['groups'] = $this->groupings->groups($grouping, $items);

        return $projection;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, list<array<string, mixed>>>
     */
    private function effectiveDecisions(array $items): array
    {
        $revisionByEvidence = [];
        foreach ($items as $item) {
            $revisionByEvidence[(string) $item['evidence_id']] = (string) $item['evidence_revision_id'];
        }

        if ($revisionByEvidence === []) {
            return [];
        }

        $grouped = [];
        $rows = EvidenceEffectiveReviewDecision::query()
            ->whereIn('evidence_id', array_keys($revisionByEvidence))
            ->orderByDesc('decided_at')
            ->orderBy('review_scope_key')
            ->get();

        foreach ($rows as $row) {
            $evidenceId = (string) $row->evidence_id;
            if (($revisionByEvidence[$evidenceId] ?? null) !== (string) $row->evidence_revision_id) {
                continue;
            }

            $grouped[$evidenceId][] = $row->attributesToArray();
        }

        return $grouped;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, list<string>>  $filters
     */
    private function matchesFilters(array $item, array $filters): bool
    {
        if (($filters['lifecycle_states'] ?? []) !== []
            && ! in_array($item['lifecycle_state'], $filters['lifecycle_states'], true)) {
            return false;
        }

        if (($filters['capability_ids'] ?? []) !== []
            && ! in_array($item['capability_id'], $filters['capability_ids'], true)) {
            return false;
        }

        if (($filters['review_decisions'] ?? []) !== []) {
            $outcomes = array_column($item['effective_review_decisions'], 'decision');
            if ($outcomes === []) {
                $outcomes = ['NONE'];
            }

            if (array_intersect($outcomes, $filters['review_decisions']) === []) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
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
}
