<?php

namespace App\Modules\Platform\Packages;

final readonly class PortablePackage
{
    /**
     * @param  array<string,mixed>  $manifest
     * @param  array<string,string>  $files
     */
    public function __construct(
        public array $manifest,
        public array $files,
        public string $archiveSha256,
        public int $archiveBytes,
    ) {}
}
