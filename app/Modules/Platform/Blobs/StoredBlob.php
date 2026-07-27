<?php

namespace App\Modules\Platform\Blobs;

final readonly class StoredBlob
{
    public function __construct(
        public string $key,
        public int $size,
        public string $sha256,
        public ?string $id = null,
    ) {}
}
