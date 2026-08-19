<?php

namespace App\Modules\Evidence\IntakeReview\Application;

final class ProvenanceDigest
{
    /** @param array<string|int, mixed> $payload */
    public function digest(array $payload): string
    {
        $canonical = $this->canonicalize($payload);

        return hash(
            'sha256',
            json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        if (array_is_list($value)) {
            $items = array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
            usort($items, static fn (mixed $left, mixed $right): int => strcmp(
                json_encode($left, JSON_THROW_ON_ERROR),
                json_encode($right, JSON_THROW_ON_ERROR),
            ));

            return $items;
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
