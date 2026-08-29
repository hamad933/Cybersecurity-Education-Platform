<?php

namespace App\Modules\Evidence\Application\ProgressEvidence\MasteryPortfolio;

use InvalidArgumentException;
use LogicException;

final class PortfolioGroupingRegistry
{
    /**
     * Project and Objective are intentionally absent: W04 has no authoritative
     * relationship that maps canonical Evidence to either semantic dimension.
     *
     * @var array<string, array{field:string,label:string}>
     */
    private const DEFINITIONS = [
        'CAPABILITY' => ['field' => 'capability_id', 'label' => 'Capability'],
        'EVIDENCE_TYPE' => ['field' => 'source_type', 'label' => 'Evidence type'],
        'TIME' => ['field' => 'sealed_at', 'label' => 'Sealed time'],
        'MASTERY_JUDGMENT' => ['field' => 'mastery_judgment', 'label' => 'Mastery judgment'],
        'FRESHNESS_STATUS' => ['field' => 'freshness_status', 'label' => 'Freshness status'],
    ];

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    /** @return array<string, array{field:string,label:string}> */
    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public function assertSupported(string $grouping): void
    {
        if (! array_key_exists($grouping, self::DEFINITIONS)) {
            throw new InvalidArgumentException('Unsupported governed Portfolio grouping.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{grouping:string,key:mixed,items:list<array<string,mixed>>}>
     */
    public function groups(string $grouping, array $items): array
    {
        $this->assertSupported($grouping);
        $field = self::DEFINITIONS[$grouping]['field'];
        $groups = [];

        foreach ($items as $item) {
            if (! array_key_exists($field, $item)) {
                throw new LogicException("Portfolio projection is missing the authoritative {$field} grouping value.");
            }

            $value = $item[$field];
            $index = $value === null ? "\0NULL" : get_debug_type($value).':'.(string) $value;

            if (! isset($groups[$index])) {
                $groups[$index] = [
                    'grouping' => $grouping,
                    'key' => $value,
                    'items' => [],
                ];
            }

            $groups[$index]['items'][] = $item;
        }

        return array_values($groups);
    }
}
