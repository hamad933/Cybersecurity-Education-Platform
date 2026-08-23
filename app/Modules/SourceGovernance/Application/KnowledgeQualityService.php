<?php

namespace App\Modules\SourceGovernance\Application;

use App\Modules\SourceGovernance\Application\ResearchQuality\ResearchQualityWorkbench;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Database\Eloquent\Builder;

final class KnowledgeQualityService
{
    /**
     * @param  list<string>  $claimIds
     * @return list<array<string, mixed>>
     */
    public function sourcesForClaims(array $claimIds): array
    {
        if ($claimIds === []) {
            return [];
        }

        return $this->sourceQuery($claimIds)
            ->get()
            ->map(fn (SourceRecord $source): array => $this->source($source, $claimIds))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $canonicalClaimIds
     * @return array<string, mixed>
     */
    public function workspace(?string $requestedSourceId, array $canonicalClaimIds): array
    {
        $sources = SourceRecord::query()
            ->with(['claims' => fn ($query) => $query->orderBy('claim_id')])
            ->orderBy('title')
            ->get()
            ->map(fn (SourceRecord $source): array => $this->source($source, $canonicalClaimIds))
            ->values()
            ->all();

        $active = null;
        if ($requestedSourceId !== null) {
            foreach ($sources as $source) {
                if (($source['id'] ?? null) === $requestedSourceId) {
                    $active = $source;
                    break;
                }
            }
        }

        if ($active === null && $canonicalClaimIds !== []) {
            foreach ($sources as $source) {
                $claims = $source['claims'] ?? [];
                if (! is_array($claims)) {
                    continue;
                }

                foreach ($claims as $claim) {
                    if (is_array($claim) && ($claim['used_by_active_revision'] ?? false) === true) {
                        $active = $source;
                        break 2;
                    }
                }
            }
        }

        if ($active === null) {
            $active = $sources[0] ?? null;
        }

        return [
            'sources' => $sources,
            'active_source' => $active,
            'canonical_claim_ids' => $canonicalClaimIds,
            'review_semantics' => 'knowledge_quality',
            'analysis' => (new ResearchQualityWorkbench)->analyze($sources, $canonicalClaimIds),
        ];
    }

    /**
     * @param  list<string>  $claimIds
     * @return Builder<SourceRecord>
     */
    private function sourceQuery(array $claimIds): Builder
    {
        return SourceRecord::query()
            ->whereHas('claims', fn (Builder $query) => $query->whereIn('claim_id', $claimIds))
            ->with(['claims' => fn ($query) => $query->whereIn('claim_id', $claimIds)->orderBy('claim_id')])
            ->orderBy('title');
    }

    /**
     * @param  list<string>  $canonicalClaimIds
     * @return array<string, mixed>
     */
    private function source(SourceRecord $source, array $canonicalClaimIds): array
    {
        $metadata = $source->getAttribute('metadata');

        return [
            'id' => (string) $source->id,
            'authority_class' => (string) $source->authority_class,
            'title' => (string) $source->title,
            'exact_url' => $source->exact_url !== null ? (string) $source->exact_url : null,
            'relative_path' => $source->relative_path !== null ? (string) $source->relative_path : null,
            'sha256' => (string) $source->sha256,
            'review_status' => (string) $source->review_status,
            'metadata' => is_array($metadata) ? $metadata : [],
            'claims' => $source->claims->map(fn (SourceClaim $claim): array => [
                'id' => (string) $claim->id,
                'claim_id' => (string) $claim->claim_id,
                'segment_ref' => (string) $claim->segment_ref,
                'supported_scope' => (string) $claim->supported_scope,
                'excluded_semantics' => (string) $claim->excluded_semantics,
                'assessment' => (string) $claim->assessment,
                'used_by_active_revision' => in_array((string) $claim->claim_id, $canonicalClaimIds, true),
            ])->values()->all(),
        ];
    }
}
