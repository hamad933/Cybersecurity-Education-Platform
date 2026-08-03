<?php

namespace App\Modules\Platform\Blobs;

interface BlobStore
{
    /** @param resource $stream */
    public function writeStream(
        $stream,
        ?string $mediaType = null,
        string $ownerModule = 'MOD-PLT',
        string $purpose = 'generic',
        ?string $ownerIdentifier = null,
        string $status = 'ready',
    ): StoredBlob;

    /** @return resource */
    public function readStream(string $key);

    /** @param resource $stream */
    public function restoreStream(string $key, $stream, string $expectedSha256, int $expectedBytes): void;

    public function verify(string $key, string $expectedSha256, int $expectedBytes): bool;

    public function delete(string $key): void;
}
