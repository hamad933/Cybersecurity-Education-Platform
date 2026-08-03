<?php

namespace App\Modules\Platform\Packages;

use InvalidArgumentException;

final class PackagePathGuard
{
    public function normalize(string $path, PackageLimits $limits): string
    {
        if ($path === '' || strlen($path) > $limits->maxNameBytes || str_contains($path, "\0") || str_contains($path, '\\')) {
            throw new InvalidArgumentException('Package path is empty, oversized, or non-portable.');
        }
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1 || str_contains($path, '//')) {
            throw new InvalidArgumentException('Absolute or malformed package path rejected.');
        }
        $segments = explode('/', $path);
        if (count($segments) > $limits->maxDepth || array_filter($segments, fn (string $part): bool => $part === '' || $part === '.' || $part === '..') !== []) {
            throw new InvalidArgumentException('Unsafe package traversal or depth rejected.');
        }
        foreach ($segments as $segment) {
            if (preg_match('/[\x00-\x1F\x7F]/', $segment) === 1) {
                throw new InvalidArgumentException('Control characters in package path rejected.');
            }
        }

        return implode('/', $segments);
    }
}
