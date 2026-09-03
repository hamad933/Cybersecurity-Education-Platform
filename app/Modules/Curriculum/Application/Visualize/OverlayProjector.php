<?php

namespace App\Modules\Curriculum\Application\Visualize;

final class OverlayProjector
{
    /** @var list<string> */
    public const TYPES = ['coverage', 'prerequisite', 'progress', 'evidence', 'mastery'];

    /** @var list<string> */
    private const VIEWS = ['Tree', 'Path', 'Graph', 'Canvas'];

    /**
     * @param  array<string, mixed>  $signals
     * @param  list<string>  $nodeIds
     * @param  list<string>  $edgeIds
     * @return array{active: null, available: list<string>, layers: array<string, array<string, mixed>>}
     */
    public function project(array $signals, array $nodeIds = [], array $edgeIds = []): array
    {
        $available = [];
        $layers = [];

        foreach (self::TYPES as $type) {
            $signal = $signals[$type] ?? null;
            $validated = $this->validatedSignal($signal, $nodeIds, $edgeIds);

            if ($validated !== null && $validated['observations'] !== []) {
                $available[] = $type;
                $layers[$type] = [
                    'available' => true,
                    'observations' => $validated['observations'],
                    'supported_views' => $validated['supported_views'],
                    'source' => $validated['source'],
                ];

                continue;
            }

            $layers[$type] = [
                'available' => false,
                'observations' => [],
                'supported_views' => [],
                'reason' => $signal === null
                    ? $this->absenceReason($type)
                    : 'INVALID_PROVIDER_SCHEMA',
            ];
        }

        return [
            'active' => null,
            'available' => $available,
            'layers' => $layers,
        ];
    }

    /**
     * @param  list<string>  $nodeIds
     * @param  list<string>  $edgeIds
     * @return array{source: string, supported_views: list<string>, observations: list<array<string, mixed>>}|null
     */
    private function validatedSignal(mixed $signal, array $nodeIds, array $edgeIds): ?array
    {
        if (! is_array($signal) || array_is_list($signal)) {
            return null;
        }

        $source = $signal['source'] ?? null;
        $supportedViews = $signal['supported_views'] ?? null;
        $observations = $signal['observations'] ?? null;
        if (! is_string($source) || $source === ''
            || ! is_array($supportedViews) || ! array_is_list($supportedViews)
            || ! is_array($observations) || ! array_is_list($observations)) {
            return null;
        }

        $supportedViews = array_values(array_unique(array_filter(
            $supportedViews,
            static fn (mixed $view): bool => is_string($view) && in_array($view, self::VIEWS, true),
        )));
        if ($supportedViews === []) {
            return null;
        }

        $validated = [];
        foreach ($observations as $observation) {
            if (! is_array($observation) || array_is_list($observation)) {
                return null;
            }

            $id = $observation['id'] ?? null;
            $target = $observation['target'] ?? null;
            $state = $observation['state'] ?? null;
            $label = $observation['label'] ?? null;
            $views = $observation['supported_views'] ?? null;
            $provenance = $observation['provenance'] ?? null;
            if (! is_string($id) || $id === ''
                || ! is_array($target) || array_is_list($target)
                || ! is_string($state) || $state === ''
                || ! is_string($label) || $label === ''
                || ! is_array($views) || ! array_is_list($views)
                || ! is_array($provenance) || array_is_list($provenance)
                || ! is_string($provenance['source'] ?? null)) {
                return null;
            }

            $targetKind = $target['kind'] ?? null;
            $targetId = $target['id'] ?? null;
            if (! is_string($targetKind) || ! is_string($targetId)
                || ! $this->targetIsInScope($targetKind, $targetId, $nodeIds, $edgeIds)) {
                return null;
            }

            $views = array_values(array_unique(array_filter(
                $views,
                static fn (mixed $view): bool => is_string($view) && in_array($view, $supportedViews, true),
            )));
            if ($views === []) {
                return null;
            }

            $validated[] = [
                'id' => $id,
                'target' => ['kind' => $targetKind, 'id' => $targetId],
                'state' => $state,
                'label' => $label,
                'supported_views' => $views,
                'provenance' => [
                    'source' => $provenance['source'],
                    'version' => is_string($provenance['version'] ?? null)
                        ? $provenance['version']
                        : null,
                ],
            ];
        }

        return [
            'source' => $source,
            'supported_views' => $supportedViews,
            'observations' => $validated,
        ];
    }

    /**
     * @param  list<string>  $nodeIds
     * @param  list<string>  $edgeIds
     */
    private function targetIsInScope(string $kind, string $id, array $nodeIds, array $edgeIds): bool
    {
        return match ($kind) {
            'node' => in_array($id, $nodeIds, true),
            'edge' => in_array($id, $edgeIds, true),
            'map' => false,
            default => false,
        };
    }

    private function absenceReason(string $type): string
    {
        return in_array($type, ['evidence', 'mastery'], true) ? 'NO_AUTHORITY' : 'NO_DATA';
    }
}
