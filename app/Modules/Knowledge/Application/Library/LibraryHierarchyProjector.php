<?php

namespace App\Modules\Knowledge\Application\Library;

final class LibraryHierarchyProjector
{
    /**
     * @param  list<array<string, mixed>>  $catalog
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $capabilityContexts
     * @return array<string, mixed>
     */
    public function project(array $catalog, array $placements, array $capabilityContexts): array
    {
        $catalogById = [];
        foreach ($catalog as $item) {
            $id = $item['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $catalogById[$id] = $item;
            }
        }

        $contextByCapability = [];
        foreach ($capabilityContexts as $context) {
            $normalized = $this->normalizeContext($context);
            if ($normalized !== null) {
                $contextByCapability[$normalized['capability_id']] = $normalized;
            }
        }

        $latestPlacements = [];
        foreach ($placements as $placement) {
            $capabilityId = $placement['capability_id'] ?? null;
            $knowledgeUnitId = $placement['knowledge_unit_id'] ?? null;
            if (
                ! is_string($capabilityId)
                || $capabilityId === ''
                || ! is_string($knowledgeUnitId)
                || ! isset($catalogById[$knowledgeUnitId])
            ) {
                continue;
            }

            $key = $capabilityId."\0".$knowledgeUnitId;
            $revision = is_int($placement['revision'] ?? null) ? $placement['revision'] : 0;
            $currentRevision = is_int($latestPlacements[$key]['revision'] ?? null)
                ? $latestPlacements[$key]['revision']
                : -1;

            if (! isset($latestPlacements[$key]) || $revision > $currentRevision) {
                $latestPlacements[$key] = $placement;
            }
        }
        ksort($latestPlacements);

        $domains = [];
        $unresolved = [];
        $placedUnitIds = [];

        foreach ($latestPlacements as $placement) {
            $capabilityId = (string) $placement['capability_id'];
            $knowledgeUnitId = (string) $placement['knowledge_unit_id'];
            $placedUnitIds[$knowledgeUnitId] = true;
            $item = $this->projectionItem($catalogById[$knowledgeUnitId], $placement);
            $context = $contextByCapability[$capabilityId] ?? null;

            if ($context === null) {
                if (! isset($unresolved[$capabilityId])) {
                    $unresolved[$capabilityId] = [
                        'capability_id' => $capabilityId,
                        'integration_state' => 'missing_hierarchy_context',
                        'items' => [],
                    ];
                }
                $unresolved[$capabilityId]['items'][] = $item;

                continue;
            }

            $domainId = $context['domain_id'];
            $clusterId = $context['capability_cluster_id'];

            if (! isset($domains[$domainId])) {
                $domains[$domainId] = [
                    'id' => $domainId,
                    'title_ar' => $context['domain_title_ar'],
                    'title_en' => $context['domain_title_en'],
                    'clusters' => [],
                ];
            }

            if (! isset($domains[$domainId]['clusters'][$clusterId])) {
                $domains[$domainId]['clusters'][$clusterId] = [
                    'id' => $clusterId,
                    'title_ar' => $context['capability_cluster_title_ar'],
                    'title_en' => $context['capability_cluster_title_en'],
                    'capabilities' => [],
                ];
            }

            if (! isset($domains[$domainId]['clusters'][$clusterId]['capabilities'][$capabilityId])) {
                $domains[$domainId]['clusters'][$clusterId]['capabilities'][$capabilityId] = [
                    'id' => $capabilityId,
                    'title_ar' => $context['capability_title_ar'],
                    'title_en' => $context['capability_title_en'],
                    'items' => [],
                ];
            }

            $domains[$domainId]['clusters'][$clusterId]['capabilities'][$capabilityId]['items'][] = $item;
        }

        $domainList = $this->finalizeDomains($domains);
        $unresolvedList = array_values($unresolved);
        usort(
            $unresolvedList,
            static fn (array $left, array $right): int => strcmp(
                (string) $left['capability_id'],
                (string) $right['capability_id'],
            ),
        );

        $unplaced = [];
        foreach ($catalogById as $knowledgeUnitId => $item) {
            if (! isset($placedUnitIds[$knowledgeUnitId])) {
                $unplaced[] = $this->projectionItem($item, null);
            }
        }
        usort(
            $unplaced,
            static fn (array $left, array $right): int => strcmp(
                (string) $left['canonical_ref']['id'],
                (string) $right['canonical_ref']['id'],
            ),
        );

        return [
            'domains' => $domainList,
            'unresolved_capabilities' => $unresolvedList,
            'unplaced' => $unplaced,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string|null>|null
     */
    private function normalizeContext(array $context): ?array
    {
        foreach ([
            'domain_id',
            'domain_title_ar',
            'capability_cluster_id',
            'capability_cluster_title_ar',
            'capability_id',
            'capability_title_ar',
        ] as $required) {
            if (! is_string($context[$required] ?? null) || trim((string) $context[$required]) === '') {
                return null;
            }
        }

        return [
            'domain_id' => trim((string) $context['domain_id']),
            'domain_title_ar' => trim((string) $context['domain_title_ar']),
            'domain_title_en' => $this->optionalString($context['domain_title_en'] ?? null),
            'capability_cluster_id' => trim((string) $context['capability_cluster_id']),
            'capability_cluster_title_ar' => trim((string) $context['capability_cluster_title_ar']),
            'capability_cluster_title_en' => $this->optionalString($context['capability_cluster_title_en'] ?? null),
            'capability_id' => trim((string) $context['capability_id']),
            'capability_title_ar' => trim((string) $context['capability_title_ar']),
            'capability_title_en' => $this->optionalString($context['capability_title_en'] ?? null),
        ];
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>|null  $placement
     * @return array<string, mixed>
     */
    private function projectionItem(array $item, ?array $placement): array
    {
        return [
            'canonical_ref' => [
                'kind' => 'knowledge_unit',
                'id' => (string) $item['id'],
            ],
            'title_ar' => (string) ($item['title_ar'] ?? ''),
            'title_en' => (string) ($item['title_en'] ?? ''),
            'latest_revision' => is_int($item['latest_revision'] ?? null) ? $item['latest_revision'] : null,
            'latest_state' => is_string($item['latest_state'] ?? null) ? $item['latest_state'] : null,
            'projection_reason' => $placement === null ? 'unplaced_canonical_object' : 'curriculum_placement',
            'placement' => $placement === null ? null : [
                'id' => is_string($placement['id'] ?? null) ? $placement['id'] : null,
                'revision' => is_int($placement['revision'] ?? null) ? $placement['revision'] : null,
                'lifecycle' => is_array($placement['lifecycle'] ?? null) ? $placement['lifecycle'] : [],
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $domains
     * @return list<array<string, mixed>>
     */
    private function finalizeDomains(array $domains): array
    {
        ksort($domains);

        foreach ($domains as &$domain) {
            $clusters = is_array($domain['clusters'] ?? null) ? $domain['clusters'] : [];
            ksort($clusters);

            foreach ($clusters as &$cluster) {
                $capabilities = is_array($cluster['capabilities'] ?? null) ? $cluster['capabilities'] : [];
                ksort($capabilities);

                foreach ($capabilities as &$capability) {
                    $items = is_array($capability['items'] ?? null) ? $capability['items'] : [];
                    usort(
                        $items,
                        static fn (array $left, array $right): int => strcmp(
                            (string) $left['canonical_ref']['id'],
                            (string) $right['canonical_ref']['id'],
                        ),
                    );
                    $capability['items'] = $items;
                }
                unset($capability);

                $cluster['capabilities'] = array_values($capabilities);
            }
            unset($cluster);

            $domain['clusters'] = array_values($clusters);
        }
        unset($domain);

        return array_values($domains);
    }
}
