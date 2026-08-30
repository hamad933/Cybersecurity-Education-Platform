<?php

namespace App\Modules\SourceGovernance\Application\ResearchQuality;

final class ResearchQualityWorkbench
{
    /**
     * @param  list<array<string, mixed>>  $sources
     * @param  list<string>  $canonicalClaimIds
     * @return array<string, mixed>
     */
    public function analyze(array $sources, array $canonicalClaimIds): array
    {
        $conflicts = $this->conflicts($sources);
        $claimSources = $this->claimSources($sources);
        $canonicalClaimIds = array_values(array_unique($canonicalClaimIds));
        $resolvedClaimIds = array_values(array_intersect($canonicalClaimIds, array_keys($claimSources)));
        $unresolvedClaimIds = array_values(array_diff($canonicalClaimIds, $resolvedClaimIds));

        return [
            'comparison' => [
                'rows' => array_map(
                    fn (array $source): array => $this->comparisonRow($source, $canonicalClaimIds),
                    $sources,
                ),
                'meaning' => 'descriptive_source_comparison_not_truth_ranking',
            ],
            'provenance' => [
                'sources' => array_map(fn (array $source): array => $this->provenanceRow($source), $sources),
                'meaning' => 'traceability_inspection_not_truth_decision',
            ],
            'conflicts' => $conflicts,
            'reconciliation' => [
                'pending_conflict_count' => count($conflicts),
                'human_judgment_required' => $conflicts !== [],
                'system_truth_decision' => null,
                'allowed_next_tools' => ['compare', 'inspect_provenance', 'draft_ephemeral_reconciliation_note'],
                'persistence_boundary' => [
                    'state' => 'RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED',
                    'durable_write_authorized' => false,
                    'persistent_owner' => null,
                    'decision_record' => null,
                    'current_experience' => 'read_only_analysis_with_ephemeral_human_note',
                ],
            ],
            'revision_reasoning' => [
                'canonical_claim_ids' => $canonicalClaimIds,
                'resolved_claim_ids' => $resolvedClaimIds,
                'unresolved_claim_ids' => $unresolvedClaimIds,
                'claim_sources' => array_intersect_key($claimSources, array_flip($canonicalClaimIds)),
                'meaning' => 'revision_provenance_reasoning_not_mastery_or_evidence_review',
            ],
            'review' => [
                'kind' => 'knowledge_quality',
                'decision_authority' => 'human',
                'system_may_decide_truth' => false,
                'evidence_review' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $canonicalClaimIds
     * @return array<string, mixed>
     */
    private function comparisonRow(array $source, array $canonicalClaimIds): array
    {
        $claims = $this->claims($source);
        $claimIds = array_values(array_unique(array_filter(
            array_map(static fn (array $claim): mixed => $claim['claim_id'] ?? null, $claims),
            'is_string',
        )));

        return [
            'source_id' => (string) ($source['id'] ?? ''),
            'title' => (string) ($source['title'] ?? ''),
            'authority_class' => (string) ($source['authority_class'] ?? ''),
            'review_status' => (string) ($source['review_status'] ?? ''),
            'claim_count' => count($claimIds),
            'active_revision_claim_count' => count(array_intersect($claimIds, $canonicalClaimIds)),
            'anchor_count' => count(array_filter(
                $claims,
                static fn (array $claim): bool => is_string($claim['segment_ref'] ?? null) && $claim['segment_ref'] !== '',
            )),
            'has_integrity_digest' => is_string($source['sha256'] ?? null) && $source['sha256'] !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function provenanceRow(array $source): array
    {
        $anchors = [];
        foreach ($this->claims($source) as $claim) {
            $claimId = $claim['claim_id'] ?? null;
            $segmentRef = $claim['segment_ref'] ?? null;
            if (! is_string($claimId) || ! is_string($segmentRef) || $segmentRef === '') {
                continue;
            }

            $anchors[] = [
                'claim_id' => $claimId,
                'segment_ref' => $segmentRef,
            ];
        }

        return [
            'source_id' => (string) ($source['id'] ?? ''),
            'title' => (string) ($source['title'] ?? ''),
            'locator' => $source['exact_url'] ?? $source['relative_path'] ?? null,
            'sha256' => (string) ($source['sha256'] ?? ''),
            'anchors' => $anchors,
            'traceability_state' => $anchors === [] ? 'unanchored' : 'anchored',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @return list<array<string, mixed>>
     */
    private function conflicts(array $sources): array
    {
        /** @var array<string, list<array<string, mixed>>> $variantsByClaim */
        $variantsByClaim = [];

        foreach ($sources as $source) {
            foreach ($this->claims($source) as $claim) {
                $claimId = $claim['claim_id'] ?? null;
                if (! is_string($claimId) || $claimId === '') {
                    continue;
                }

                $variantsByClaim[$claimId][] = [
                    'source_id' => (string) ($source['id'] ?? ''),
                    'source_title' => (string) ($source['title'] ?? ''),
                    'segment_ref' => (string) ($claim['segment_ref'] ?? ''),
                    'supported_scope' => (string) ($claim['supported_scope'] ?? ''),
                    'excluded_semantics' => (string) ($claim['excluded_semantics'] ?? ''),
                    'assessment' => (string) ($claim['assessment'] ?? ''),
                ];
            }
        }

        $conflicts = [];
        foreach ($variantsByClaim as $claimId => $variants) {
            if (count($variants) < 2) {
                continue;
            }

            $fingerprints = array_unique(array_map(
                static fn (array $variant): string => json_encode([
                    $variant['supported_scope'],
                    $variant['excluded_semantics'],
                    $variant['assessment'],
                ], JSON_THROW_ON_ERROR),
                $variants,
            ));

            if (count($fingerprints) < 2) {
                continue;
            }

            $conflicts[] = [
                'claim_id' => $claimId,
                'status' => 'requires_human_reconciliation',
                'variants' => $variants,
                'preferred_source_id' => null,
                'system_truth_decision' => null,
            ];
        }

        return $conflicts;
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @return array<string, list<string>>
     */
    private function claimSources(array $sources): array
    {
        $claimSources = [];

        foreach ($sources as $source) {
            $sourceId = (string) ($source['id'] ?? '');
            foreach ($this->claims($source) as $claim) {
                $claimId = $claim['claim_id'] ?? null;
                if (! is_string($claimId) || $claimId === '') {
                    continue;
                }

                $claimSources[$claimId][] = $sourceId;
                $claimSources[$claimId] = array_values(array_unique($claimSources[$claimId]));
            }
        }

        return $claimSources;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return list<array<string, mixed>>
     */
    private function claims(array $source): array
    {
        $claims = $source['claims'] ?? [];
        if (! is_array($claims)) {
            return [];
        }

        return array_values(array_filter($claims, 'is_array'));
    }
}
