<?php

namespace App\Modules\Evidence\IntakeReview\Application;

final class ProvenanceDigest
{
    /** @param array<string|int, mixed> $payload */
    public function digest(array $payload): string
    {
        $canonical = $this->canonicalizeDigestValue($payload);

        return hash(
            'sha256',
            json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /** @param array<mixed> $value */
    public function canonicalJson(array $value): string
    {
        return json_encode(
            $this->canonicalizeJsonValue($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function canonicalizeDigestValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        if (array_is_list($value)) {
            $items = array_map(fn (mixed $item): mixed => $this->canonicalizeDigestValue($item), $value);
            usort($items, static fn (mixed $left, mixed $right): int => strcmp(
                json_encode($left, JSON_THROW_ON_ERROR),
                json_encode($right, JSON_THROW_ON_ERROR),
            ));

            return $items;
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeDigestValue($item);
        }

        return $value;
    }

    private function canonicalizeJsonValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalizeJsonValue($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeJsonValue($item);
        }

        return $value;
    }
}
