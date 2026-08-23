<?php

namespace App\Modules\Curriculum\Application\Visualize;

final class OverlayProjector
{
    /** @var list<string> */
    public const TYPES = ['coverage', 'prerequisite', 'progress', 'evidence', 'mastery'];

    /**
     * @param  array<string, mixed>  $signals
     * @return array{active: null, available: list<string>, layers: array<string, array<string, mixed>>}
     */
    public function project(array $signals): array
    {
        $available = [];
        $layers = [];

        foreach (self::TYPES as $type) {
            $hasObservedData = array_key_exists($type, $signals) && $this->hasObservedData($signals[$type]);

            if ($hasObservedData) {
                $available[] = $type;
                $layers[$type] = [
                    'available' => true,
                    'observations' => $signals[$type],
                    'source' => 'observed_application_state',
                ];

                continue;
            }

            $layers[$type] = [
                'available' => false,
                'reason' => 'no_observed_data',
            ];
        }

        return [
            'active' => null,
            'available' => $available,
            'layers' => $layers,
        ];
    }

    private function hasObservedData(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return ! is_array($value) || $value !== [];
    }
}
