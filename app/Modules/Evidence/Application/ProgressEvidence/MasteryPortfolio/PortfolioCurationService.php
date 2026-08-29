<?php

namespace App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio;

use App\Modules\Evidence\Models\EvidenceEffectiveReviewDecision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class PortfolioCurationService
{
    private const REVIEW_DECISIONS = [
        'ACCEPT',
        'ACCEPT_WITH_LIMITATIONS',
        'MORE_EVIDENCE_REQUIRED',
        'REJECT',
    ];

    public function __construct(private readonly PortfolioGroupingRegistry $groupings) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $annotations
     * @return array<string, mixed>
     */
    public function create(
        string $actorId,
        string $name,
        ?string $scope,
        string $grouping,
        array $filters = [],
        array $annotations = [],
    ): array {
        $this->groupings->assertSupported($grouping);
        $id = (string) Str::uuid7();
        $now = now();

        DB::table('evidence_portfolios')->insert([
            'id' => $id,
            'owner_actor_id' => $actorId,
            'name' => $this->text($name, 180, 'Portfolio View name'),
            'view_scope' => $scope === null || trim($scope) === ''
                ? null
                : $this->text($scope, 120, 'Portfolio View scope'),
            'grouping' => $grouping,
            'filters' => $this->json($this->filters($filters)),
            'annotations' => $this->json($this->annotations($annotations)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $portfolio = DB::table('evidence_portfolios')->where('id', $id)->firstOrFail();
        $result = (array) $portfolio;
        $result['filters'] = $this->decode($portfolio->filters);
        $result['annotations'] = $this->decode($portfolio->annotations);

        return $result;
    }

    public function addAcceptedEvidence(
        string $portfolioId,
        string $evidenceId,
        string $actorId,
        ?string $annotation = null,
        int $sort = 0,
    ): void {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();
        $evidence = DB::table('governed_evidence as evidence')
            ->join('governed_evidence_revisions as revision', function ($join): void {
                $join->on('revision.evidence_id', '=', 'evidence.id')
                    ->on('revision.revision', '=', 'evidence.current_revision_number');
            })
            ->where('evidence.id', $evidenceId)
            ->select('evidence.*', 'revision.id as current_revision_id')
            ->first();

        if (! $portfolio || (string) $portfolio->owner_actor_id !== $actorId
            || ! $evidence || (string) $evidence->subject_actor_id !== $actorId) {
            throw new LogicException('Portfolio boundary mismatch.');
        }
        if ((string) $evidence->lifecycle_state !== 'ACTIVE') {
            throw new LogicException('Portfolio can reference only ACTIVE canonical Evidence.');
        }

        $accepted = EvidenceEffectiveReviewDecision::query()
            ->where('evidence_id', $evidenceId)
            ->where('evidence_revision_id', $evidence->current_revision_id)
            ->whereIn('decision', ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'])
            ->exists();
        if (! $accepted) {
            throw new LogicException('Portfolio can curate only Evidence with an effective accepted Review Decision.');
        }

        $mastery = DB::table('evidence_mastery_states as current')
            ->where('current.subject_actor_id', $actorId)
            ->where('current.target_type', 'CAPABILITY')
            ->where('current.target_id', $evidence->capability_id)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('evidence_mastery_states as successor')
                    ->whereColumn('successor.previous_state_id', 'current.id');
            })
            ->first();
        $existing = DB::table('evidence_portfolio_items')
            ->where('portfolio_id', $portfolioId)
            ->where('evidence_id', $evidenceId)
            ->first();
        $now = now();
        $values = [
            'mastery_state_id' => $mastery?->id,
            'sort_order' => max(0, $sort),
            'annotation' => $annotation === null || trim($annotation) === ''
                ? null
                : $this->text($annotation, 4000, 'Portfolio item annotation'),
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('evidence_portfolio_items')->where('id', $existing->id)->update($values);

            return;
        }

        DB::table('evidence_portfolio_items')->insert([
            ...$values,
            'id' => (string) Str::uuid7(),
            'portfolio_id' => $portfolioId,
            'evidence_id' => $evidenceId,
            'created_at' => $now,
        ]);
    }

    public function removeEvidence(string $portfolioId, string $evidenceId, string $actorId): void
    {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();
        if (! $portfolio || (string) $portfolio->owner_actor_id !== $actorId) {
            throw new LogicException('Portfolio boundary mismatch.');
        }

        DB::table('evidence_portfolio_items')
            ->where('portfolio_id', $portfolioId)
            ->where('evidence_id', $evidenceId)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, list<string>>
     */
    private function filters(array $filters): array
    {
        if (array_diff(array_keys($filters), ['lifecycle_states', 'review_decisions', 'capability_ids']) !== []) {
            throw new InvalidArgumentException('Portfolio filters contain an unsupported field.');
        }

        $normalised = [];
        if (array_key_exists('lifecycle_states', $filters)) {
            $values = $this->stringList($filters['lifecycle_states'], 'Portfolio lifecycle filters', 3, 24);
            if (array_diff($values, ['ACTIVE', 'WITHDRAWN', 'SUPERSEDED']) !== []) {
                throw new InvalidArgumentException('Portfolio lifecycle filter is invalid.');
            }
            $normalised['lifecycle_states'] = $values;
        }
        if (array_key_exists('review_decisions', $filters)) {
            $values = $this->stringList($filters['review_decisions'], 'Portfolio Review Decision filters', 5, 40);
            if (array_diff($values, ['NONE', ...self::REVIEW_DECISIONS]) !== []) {
                throw new InvalidArgumentException('Portfolio Review Decision filter is invalid.');
            }
            $normalised['review_decisions'] = $values;
        }
        if (array_key_exists('capability_ids', $filters)) {
            $normalised['capability_ids'] = $this->stringList(
                $filters['capability_ids'],
                'Portfolio Capability filters',
                20,
                100,
            );
        }

        return $normalised;
    }

    /**
     * @param  array<string, mixed>  $annotations
     * @return array<string, string>
     */
    private function annotations(array $annotations): array
    {
        if (array_diff(array_keys($annotations), ['purpose', 'audience']) !== []) {
            throw new InvalidArgumentException('Portfolio annotations contain an unsupported field.');
        }

        $normalised = [];
        foreach ($annotations as $key => $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException('Portfolio annotations must be text.');
            }
            $normalised[$key] = $this->text($value, 500, "Portfolio {$key}");
        }

        return $normalised;
    }

    /** @return list<string> */
    private function stringList(mixed $value, string $label, int $maxItems, int $maxLength): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $maxItems) {
            throw new InvalidArgumentException("{$label} must be a bounded list.");
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '' || mb_strlen(trim($item)) > $maxLength) {
                throw new InvalidArgumentException("{$label} contains an invalid value.");
            }
            $items[] = trim($item);
        }
        $items = array_values(array_unique($items));
        sort($items, SORT_STRING);

        return $items;
    }

    private function text(string $value, int $max, string $label): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $max) {
            throw new InvalidArgumentException("{$label} is invalid.");
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
