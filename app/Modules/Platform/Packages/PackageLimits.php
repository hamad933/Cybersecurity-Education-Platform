<?php

namespace App\Modules\Platform\Packages;

final readonly class PackageLimits
{
    public function __construct(
        public int $maxFiles = 200,
        public int $maxTotalBytes = 52_428_800,
        public int $maxFileBytes = 10_485_760,
        public int $maxDepth = 8,
        public int $maxNameBytes = 200,
        public int $maxCompressionRatio = 100,
    ) {}
}
