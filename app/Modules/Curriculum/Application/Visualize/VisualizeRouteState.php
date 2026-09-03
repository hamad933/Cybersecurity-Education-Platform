<?php

namespace App\Modules\Curriculum\Application\Visualize;

final class VisualizeRouteState
{
    /** @var list<string> */
    private const FILTERS = ['all', 'knowledge', 'structure'];

    /**
     * @param  array<string, string|null>  $requested
     * @param  array<string, mixed>  $visualization
     * @return array<string, mixed>
     */
    public function normalize(array $requested, array $visualization): array
    {
        $implemented = $visualization['view']['implemented'] ?? [];
        $mapDefault = $visualization['map']['default_view'] ?? null;
        $requestedView = $requested['view'] ?? null;
        $view = is_string($requestedView) && in_array($requestedView, $implemented, true)
            ? $requestedView
            : (is_string($mapDefault) && in_array($mapDefault, $implemented, true) ? $mapDefault : 'Tree');

        $filter = $requested['filter'] ?? null;
        $filter = is_string($filter) && in_array($filter, self::FILTERS, true) ? $filter : 'all';
        $selection = $this->selection($requested['selection'] ?? null, $visualization, $view, $filter);

        $overlay = $requested['overlay'] ?? null;
        $layers = $visualization['overlay']['layers'] ?? [];
        $layer = is_string($overlay) && is_array($layers[$overlay] ?? null) ? $layers[$overlay] : null;
        $overlay = $layer !== null
            && ($layer['available'] ?? false) === true
            && in_array($view, is_array($layer['supported_views'] ?? null) ? $layer['supported_views'] : [], true)
                ? $overlay
                : null;

        $requestedMap = $requested['map'] ?? null;
        $resolvedMap = ($visualization['map']['saved'] ?? false) === true
            && is_string($requestedMap)
            && $requestedMap === ($visualization['map']['id'] ?? null)
                ? $requestedMap
                : null;
        $notice = null;
        if (is_string($requestedMap) && $requestedMap !== '' && $resolvedMap === null) {
            $notice = 'تعذّر استعادة معرّف الخريطة دون عقد حفظ وملكية مصرح به.';
        } elseif (is_string($requested['overlay'] ?? null) && $overlay === null) {
            $notice = 'الطبقة المطلوبة غير متاحة أو غير مدعومة في طريقة العرض الحالية.';
        } elseif (is_string($requested['selection'] ?? null) && $selection === null) {
            $notice = 'تمت إزالة تحديد غير صالح أو خارج مجموعة العرض الحالية.';
        }

        return [
            'map' => $resolvedMap,
            'view' => $view,
            'overlay' => $overlay,
            'filter' => $filter,
            'selection' => $selection,
            'notice' => $notice,
        ];
    }

    /**
     * @param  array<string, mixed>  $visualization
     * @return array{kind: string, id: string}|null
     */
    private function selection(?string $token, array $visualization, string $view, string $filter): ?array
    {
        if (! is_string($token) || preg_match('/^(node|edge):(.+)$/', $token, $matches) !== 1) {
            return null;
        }

        $nodes = is_array($visualization['graph']['nodes'] ?? null) ? $visualization['graph']['nodes'] : [];
        $edges = is_array($visualization['graph']['edges'] ?? null) ? $visualization['graph']['edges'] : [];
        $visibleNodeIds = [];
        foreach ($nodes as $node) {
            if (! is_array($node) || ! is_string($node['id'] ?? null)) {
                continue;
            }
            if ($this->nodeMatchesFilter((string) ($node['kind'] ?? ''), $filter)) {
                $visibleNodeIds[] = $node['id'];
            }
        }

        $kind = $matches[1];
        $id = $matches[2];
        if ($kind === 'node') {
            return in_array($id, $visibleNodeIds, true) ? ['kind' => 'node', 'id' => $id] : null;
        }

        foreach ($edges as $edge) {
            if (! is_array($edge)
                || ($edge['id'] ?? null) !== $id
                || ! in_array($view, is_array($edge['supported_views'] ?? null) ? $edge['supported_views'] : [], true)
                || ! in_array($edge['from'] ?? null, $visibleNodeIds, true)
                || ! in_array($edge['to'] ?? null, $visibleNodeIds, true)) {
                continue;
            }

            return ['kind' => 'edge', 'id' => $id];
        }

        return null;
    }

    private function nodeMatchesFilter(string $kind, string $filter): bool
    {
        return match ($filter) {
            'knowledge' => $kind === 'knowledge_unit',
            'structure' => in_array($kind, ['domain', 'capability_cluster', 'capability'], true),
            default => true,
        };
    }
}
